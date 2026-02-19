<?php

namespace App\Services\Dealer;

use App\DealerPriceFlag;
use App\Enums\DealerDemandStatus;
use App\Models\Marketplace\DealerDemand;
use App\Models\Product\Variety;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DemandService
{
    public static function summary(?int $dealerId = null): array
    {
        $query = DealerDemand::query();
        
        if ($dealerId) {
            $query->where('dealer_id', $dealerId);
        }

        $totalOpen = (clone $query)->where('status', DealerDemandStatus::Open)->count();
        $totalFulfilled = (clone $query)->where('status', DealerDemandStatus::Fulfilled)->count();
        $totalExpired = (clone $query)->where('status', DealerDemandStatus::Expired)->count();
        
        $upcomingTransactions = (clone $query)
            ->where('status', DealerDemandStatus::Open)
            ->whereBetween('transaction_date', [now(), now()->addWeek()])
            ->count();

        return [
            'total_open' => $totalOpen,
            'total_fulfilled' => $totalFulfilled,
            'total_expired' => $totalExpired,
            'upcoming_transactions' => $upcomingTransactions,
        ];
    }

    public static function paginated(?int $dealerId = null, DealerDemandStatus $status, int $perPage = 20): LengthAwarePaginator
    {
        $query = DealerDemand::with([
            'dealer.user',
            'variety.vegetable.category',
            'variety.latestPrice',
        ]);

        if ($dealerId) {
            $query->where('dealer_id', $dealerId);
        }

        if ($status) {
            $status === DealerDemandStatus::Open 
                ? $query->open()
                : $query->where('status', $status->value);
        }

        return $query
            ->orderBy('transaction_date', 'desc')
            ->paginate($perPage)
            ->through(fn($request) => [
                'id' => $request->id,
                'dealer' => [
                    'id' => $request->dealer->id,
                    'name' => $request->dealer->user->name,
                ],
                'variety' => [
                    'id' => $request->variety->id,
                    'name' => $request->variety->name,
                    'vegetable' => $request->variety->vegetable->name,
                    'image_url' => $request->variety->image_url,
                ],
                'quantity_kg' => (float) $request->quantity_kg,
                'price_offered' => (float) $request->price_offered,
                'transaction_date' => $request->transaction_date->format('M d, Y'),
                'days_until_transaction' => $request->days_until_transaction,
                'status' => $request->status,
                'created_at_human' => $request->created_at->diffForHumans(),
            ]);
    }

    public static function varietyOptions(): array
    {
        return cache()->remember('dealer_request_variety_options', 3600, fn() =>
            Variety::with('vegetable.category', 'latestPrice')
                ->get()
                ->groupBy(fn($variety) => $variety->vegetable->category->name)
                ->map(fn($varieties) => $varieties->map(fn($variety) => [
                    'id' => $variety->id,
                    'name' => $variety->vegetable->name . ' ' . $variety->name,
                    'current_price' => $variety->latestPrice ? [
                        'min' => (float) $variety->latestPrice->price_min,
                        'max' => (float) $variety->latestPrice->price_max,
                    ] : null,
                ])->values()->toArray())
                ->toArray()
        );
    }

    public function create(int $dealerId, array $validated)
    {
        $variety = Variety::with('latestPrice')->find($validated['variety_id']);

        return DealerDemand::create([
            'dealer_id' => $dealerId,
            'variety_id' => $validated['variety_id'],
            'quantity_kg' => $validated['quantity_kg'],
            'price_offered'=> $validated['price_offered'],
            'transaction_date' => $validated['transaction_date'],
            'price_flag' => self::calculatePriceFlag(
                $validated['price_offered'],
                $variety->latestPrice,
            )
        ]);
    }

    public function update(DealerDemand $request, array $validated): DealerDemand
    {
        if ($request->status !== DealerDemandStatus::Open) {
            throw new \LogicException('Only open requests can be updated.');
        }

        $request->update($validated);
        return $request->fresh();
    }

    public function expire(DealerDemand $request): bool
    {
        return $request->update(['status' => DealerDemandStatus::Expired]);
    }

    public function markAsFulfilled(DealerDemand $request): bool
    {
        return $request->update(['status' => DealerDemandStatus::Fulfilled]);
    }

    public function delete(DealerDemand $request): bool
    {
        return $request->delete();
    }

    public static function expireOldRequests(): int
    {
        return DealerDemand::where('status', DealerDemandStatus::Open)
            ->update(['status' => DealerDemandStatus::Expired]);
    }

    private static function calculatePriceFlag(float $priceOffered, ?object $marketPrice): DealerPriceFlag
    {
        $priceMin = (float) $marketPrice->price_min;
        $priceMax = (float) $marketPrice->price_max;

        if ($priceOffered < $priceMin) return DealerPriceFlag::Low;
        if ($priceOffered > $priceMax) return DealerPriceFlag::Premium;
        return DealerPriceFlag::Fair;
    }
}
