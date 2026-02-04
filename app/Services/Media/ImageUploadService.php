<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageUploadService
{
    private ImageManager $imageManager;
    
    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * Upload and optimize a variety image
     * 
     * @param UploadedFile $file
     * @param string|null $oldPath - Path to delete if replacing
     * @return string - Relative path to stored image
     */
    public function uploadVarietyImage(UploadedFile $file, ?string $oldPath = null): string
    {
        // Delete old image if exists
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        // Generate unique filename
        $filename = Str::uuid() . '.webp';
        $path = "varieties/{$filename}";

        // Load and optimize image
        $image = $this->imageManager->read($file->getRealPath());
        
        // Resize to max dimensions while maintaining aspect ratio
        $image->scale(width: 800, height: 800);
        
        // Convert to WebP with 85% quality
        $encoded = $image->toWebp(quality: 85);

        // Store optimized image
        Storage::disk('public')->put($path, $encoded);

        return $path;
    }

    /**
     * Delete variety image
     */
    public function deleteVarietyImage(string $path): bool
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }

    /**
     * Get full URL for a variety image path
     */
    public function getImageUrl(?string $path): ?string
        {
            if (!$path) {
                return null;
            }

            /** @var FilesystemAdapter $disk */
            $disk = Storage::disk('public');
            
            return $disk->url($path);
        }

    /**
     * Validate uploaded image file
     */
    public function validateImage(UploadedFile $file): array
    {
        $errors = [];

        // Check file type
        if (!in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])) {
            $errors[] = 'File must be an image (JPEG, PNG, or WebP)';
        }

        // Check file size (max 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            $errors[] = 'File size must not exceed 5MB';
        }

        // Check image dimensions
        try {
            $image = $this->imageManager->read($file->getRealPath());
            $width = $image->width();
            $height = $image->height();

            if ($width < 200 || $height < 200) {
                $errors[] = 'Image must be at least 200x200 pixels';
            }
        } catch (\Exception $e) {
            $errors[] = 'Invalid image file';
        }

        return $errors;
    }
}