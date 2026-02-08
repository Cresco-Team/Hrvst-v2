<?php

namespace App\Services\Farmer;

use App\Models\Announcement\FarmerOffering;
use App\Models\Product\Variety;
use App\Services\Media\ImageUploadService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class FarmerOfferingService
{
    public function __construct(
        private ImageUploadService $imageService
    ) {}

    public static function paginated(?int $farmerId = null, ?string $status = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = FarmerOffering::with([
            'farmer.user',
            'farmer.municipality',
            'variety.vegetable.category',
        ]);

        if ($farmerId) {
            $query->where('farmer_id', $farmerId);
        }

        if ($status && $status !== 'all') {
            match($status) {
                'active' => $query->active(),
                'expired' => $query->expired(),
                'archived' => $query->archived(),
                default => $query->where('status', $status),
            };
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->through(fn($offering) => [
                'id' => $offering->id,
                'farmer' => [
                    'id' => $offering->farmer->id,
                    'name' => $offering->farmer->user->name,
                    'location' => $offering->farmer->municipality->name,
                ],
                'variety' => [
                    'name' => $offering->variety->vegetable->name . ' ' . $offering->variety->name,
                    'category' => $offering->variety->vegetable->category->name,
                ],
                'image_url' => $offering->image_url,
                'quantity_kg' => (float) $offering->quantity_kg,
                'price_asking' => (float) $offering->price_asking,
                'expiration_date' => $offering->expiration_date->format('M d, Y'),
                'days_until_expiration' => $offering->days_until_expiration,
                'status' => $offering->status,
                'created_at_human' => $offering->created_at->diffForHumans(),
            ]);
    }

    public function create(int $farmerId, array $validated, UploadedFile $image): FarmerOffering
    {
        $imagePath = $this->imageService->uploadVarietyImage($image);

        return FarmerOffering::create([
            'farmer_id' => $farmerId,
            'variety_id' => $validated['variety_id'],
            'image_path' => $imagePath,
            'quantity_kg' => $validated['quantity_kg'],
            'price_asking' => $validated['price_asking'],
            'expiration_date' => $validated['expiration_date'],
            'status' => 'active',
        ]);
    }

    public function update(FarmerOffering $offering, array $validated, ?UploadedFile $image = null): FarmerOffering
    {
        if ($offering->status !== 'active') {
            throw new \LogicException('Only active offerings can be updated.');
        }

        if ($image) {
            $this->imageService->deleteVarietyImage($offering->image_path);
            $validated['image_path'] = $this->imageService->uploadVarietyImage($image);
        }

        $offering->update($validated);
        return $offering->fresh();
    }

    public function archive(FarmerOffering $offering): bool
    {
        return $offering->update(['status' => 'archived']);
    }

    public function delete(FarmerOffering $offering): bool
    {
        return DB::transaction(function () use ($offering) {
            $this->imageService->deleteVarietyImage($offering->image_path);
            return $offering->delete();
        });
    }

    public static function varietyOptions(): array
    {
        return cache()->remember('farmer_offering_variety_options', 3600, fn() =>
            Variety::with('vegetable.category')
                ->orderBy('name')
                ->get()
                ->groupBy(fn($variety) => $variety->vegetable->category->name)
                ->map(fn($varieties) => $varieties->map(fn($variety) => [
                    'id' => $variety->id,
                    'name' => $variety->vegetable->name . ' ' . $variety->name,
                ])->values()->toArray())
                ->toArray()
        );
    }

    public static function summary(?int $farmerId = null): array
    {
        $query = FarmerOffering::query();
        
        if ($farmerId) {
            $query->where('farmer_id', $farmerId);
        }

        $totalActive = (clone $query)->where('status', 'active')->count();
        $totalExpired = (clone $query)->where('status', 'expired')->count();
        $totalArchived = (clone $query)->where('status', 'archived')->count();
        
        $expiringThisWeek = (clone $query)
            ->where('status', 'active')
            ->whereBetween('expiration_date', [now(), now()->addWeek()])
            ->count();

        $totalValue = (clone $query)
            ->where('status', 'active')
            ->selectRaw('SUM(quantity_kg * price_asking) as total')
            ->value('total') ?? 0;

        return [
            'total_active' => $totalActive,
            'total_expired' => $totalExpired,
            'total_archived' => $totalArchived,
            'expiring_this_week' => $expiringThisWeek,
            'total_value' => round($totalValue, 2),
        ];
    }

    public static function expireOldOfferings(): int
    {
        return FarmerOffering::where('status', 'active')
            ->where('expiration_date', '<', now())
            ->update(['status' => 'expired']);
    }
}
