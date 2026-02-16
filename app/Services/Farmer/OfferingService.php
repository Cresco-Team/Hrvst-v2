<?php

namespace App\Services\Farmer;

use App\FarmerOfferingStatus;
use App\FarmerPriceFlag;
use App\Models\Marketplace\FarmerOffering;
use App\Models\Product\Variety;
use App\Services\Media\ImageUploadService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class OfferingService
{
    public function __construct(
        private ImageUploadService $imageService
    ) {}

    public static function summary(?int $farmerId = null): array
    {
        $query = FarmerOffering::query();
        
        if ($farmerId) {
            $query->where('farmer_id', $farmerId);
        }

        $totalAvailable = (clone $query)->where('status', FarmerOfferingStatus::Available)->count();
        $totalArchived = (clone $query)->where('status', FarmerOfferingStatus::Archived)->count();
        $expiringThisWeek = (clone $query)
            ->where('status', FarmerOfferingStatus::Available)
            ->whereBetween('expiration_date', [now(), now()->addWeek()])
            ->count();

        $totalValue = (clone $query)
            ->where('status', FarmerOfferingStatus::Available)
            ->selectRaw('SUM(weight_kg * asking_price) as total')
            ->value('total') ?? 0;

        return [
            'total_available' => $totalAvailable,
            'total_archived' => $totalArchived,
            'expiring_this_week' => $expiringThisWeek,
            'total_value' => round($totalValue, 2),
        ];
    }

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

        if ($status) {
            match($status) {
                'available' => $query->available(),
                'archived   ' => $query->archived(),
                default => $query->where('status', $status),
            };
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->through(fn($offering) => [
                'id' => $offering->id,
                'farmer' => [
                    'id' => $offering->farmer->id,
                    'name' => $offering->farmer->user->name,
                ],
                'variety' => [
                    'id' => $offering->variety->id,
                    'name' => $offering->variety->name,
                    'vegetable' => $offering->variety->vegetable->name,
                ],
                'image_url' => $offering->image_url,
                'weight_kg' => (float) $offering->weight_kg,
                'asking_price' => (float) $offering->asking_price,
                'expiration_date' => $offering->expiration_date->format('M d, Y'),
                'days_until_expiration' => $offering->days_until_expiration,
                'status' => $offering->status,
                'created_at_human' => $offering->created_at->diffForHumans(),
            ]);
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
                    'weeks_to_harvest' => $variety->weeks_to_harvest,
                ])->values()->toArray())
                ->toArray()
        );
    }

    public function create(int $farmerId, array $validated, UploadedFile $image): FarmerOffering
    {
        $imagePath = $this->imageService->uploadVarietyImage($image);
        $variety = Variety::with('latestPrice')->find($validated['variety_id']);

        return FarmerOffering::create([
            'farmer_id' => $farmerId,
            'variety_id' => $validated['variety_id'],
            'image_path' => $imagePath,
            'weight_kg' => $validated['weight_kg'],
            'asking_price' => $validated['asking_price'],
            'expiration_date' => $validated['expiration_date'],
            'price_flag' => self::calculatePriceFlag(
                $validated['asking_price'],
                $variety->latestPrice,
            )
        ]);
    }

    public function update(FarmerOffering $offering, array $validated, ?UploadedFile $image = null): FarmerOffering
    {
        if ($offering->status !== FarmerOfferingStatus::Available) {
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
        return $offering->update(['status' => FarmerOfferingStatus::Archived]);
    }

    public function delete(FarmerOffering $offering): bool
    {
        return DB::transaction(function () use ($offering) {
            $this->imageService->deleteVarietyImage($offering->image_path);
            return $offering->delete();
        });
    }

    public static function expireOldOfferings(): int
    {
        return FarmerOffering::where('status', 'active')
            ->where('expiration_date', '<', now())
            ->update(['status' => 'expired']);
    }

    public function calculatePriceFlag(float $askingPrice, ?object $variety)
    {
        $priceMin = (float) $variety->price_min;
        $priceMax = (float) $variety->price_max;

        if ($askingPrice < $priceMin) return FarmerPriceFlag::Lean;
        if ($askingPrice > $priceMax) return FarmerPriceFlag::High;
        return FarmerPriceFlag::Fair;
    }
}
