<?php

namespace App\Services\Product;

use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use App\Services\Media\ImageUploadService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;

class VarietyService
{
    public function __construct(
        private ImageUploadService $imageService
    ) {}

    public static function paginated(int $perPage = 20, ?string $priceFilter = null): LengthAwarePaginator
    {
        $query = Variety::with([
            'vegetable.category',
            'latestPrice'
        ]);

        // Apply price freshness filter
        if ($priceFilter) {
            $query->whereHas('latestPrice', function (Builder $q) use ($priceFilter) {
                match ($priceFilter) {
                    'week' => $q->where('recorded_at', '>=', now()->subWeek()),
                    'month' => $q->where('recorded_at', '>=', now()->subMonth()),
                    'stale' => $q->where('recorded_at', '<', now()->subMonth()),
                    default => null,
                };
            });
        }

        return $query->orderBy('name')
            ->paginate($perPage)
            ->through(function ($variety) {
                // Add image URL
                $variety->image_url = app(ImageUploadService::class)->getImageUrl($variety->image_path);
                
                if ($variety->latestPrice) {
                    $variety->price_updated_human = $variety->latestPrice->recorded_at->diffForHumans();
                    $variety->price_updated_date = $variety->latestPrice->recorded_at->format('M d, Y');
                    
                    $daysOld = $variety->latestPrice->recorded_at->diffInDays(now());
                    $variety->price_freshness = match (true) {
                        $daysOld <= 3 => 'fresh',
                        $daysOld <= 7 => 'recent',
                        $daysOld <= 14 => 'okay',
                        $daysOld <= 30 => 'aging',
                        default => 'stale',
                    };
                }
                return $variety;
            });
    }

    public static function summary(): array
    {
        $now = now();
        $oneWeekAgo = $now->copy()->subWeek();
        $oneMonthAgo = $now->copy()->subMonth();

        return [
            'total_varieties' => Variety::count(),
            'total_vegetables' => Vegetable::count(),
            'average_weeks_to_harvest' => round(Variety::avg('weeks_to_harvest'), 1),
            'price_stats' => [
                'updated_week' => Variety::whereHas('latestPrice', fn($q) => 
                    $q->where('recorded_at', '>=', $oneWeekAgo)
                )->count(),
                'updated_month' => Variety::whereHas('latestPrice', fn($q) => 
                    $q->where('recorded_at', '>=', $oneMonthAgo)
                )->count(),
                'stale' => Variety::whereHas('latestPrice', fn($q) => 
                    $q->where('recorded_at', '<', $oneMonthAgo)
                )->count(),
                'no_price' => Variety::doesntHave('latestPrice')->count(),
            ],
        ];
    }

    public static function vegetableOptions(): array
    {
        return Vegetable::with('category')
            ->get()
            ->groupBy('category.name')
            ->map(function ($vegetables) {
                return $vegetables->pluck('name', 'id')->toArray();
            })
            ->toArray();
    }

    public function create(array $validated, ?UploadedFile $image = null): Variety
    {
        // Handle image upload
        if ($image) {
            $validated['image_path'] = $this->imageService->uploadVarietyImage($image);
        }

        return Variety::create($validated);
    }

    public function update(Variety $variety, array $validated, ?UploadedFile $image = null): Variety
    {
        // Handle image upload and replace old one
        if ($image) {
            $validated['image_path'] = $this->imageService->uploadVarietyImage(
                $image, 
                $variety->image_path
            );
        }

        $variety->update($validated);

        return $variety;
    }

    public function delete(Variety $variety): bool
    {
        // Check for plantings
        if ($variety->plantings()->exists()) {
            return false;
        }

        // Delete image file
        if ($variety->image_path) {
            $this->imageService->deleteVarietyImage($variety->image_path);
        }

        $variety->delete();

        return true;
    }
}