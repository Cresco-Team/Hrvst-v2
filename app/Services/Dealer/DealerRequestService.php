<?php

namespace App\Services\Dealer;

use App\Models\Announcement\DealerRequest;
use App\Models\Announcement\DealerRequestItem;
use App\Models\Product\Variety;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DealerRequestService
{
    public static function paginated(?int $dealerId = null, ?string $status = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = DealerRequest::with([
            'dealer.user',
            'items.variety.vegetable.category',
            'items.variety.latestPrice',
        ]);

        if ($dealerId) {
            $query->where('dealer_id', $dealerId);
        }

        if ($status && $status !== 'all') {
            $status === 'active' 
                ? $query->active()
                : $query->where('status', $status);
        }

        return $query
            ->orderBy('transaction_date', 'desc')
            ->paginate($perPage)
            ->through(fn($request) => [
                'id' => $request->id,
                'dealer' => [
                    'id' => $request->dealer->id,
                    'name' => $request->dealer->user->name,
                    'user_image' => $request->dealer->user->user_image,
                ],
                'transaction_date' => $request->transaction_date->format('M d, Y'),
                'status' => $request->status,
                'items' => $request->items->map(fn($item) => [
                    'variety' => [
                        'name' => $item->variety->vegetable->name . ' ' . $item->variety->name,
                        'category' => $item->variety->vegetable->category->name,
                    ],
                    'quantity_kg' => (float) $item->quantity_kg,
                    'price_offered' => (float) $item->price_offered,
                    'price_flag' => self::calculatePriceFlag($item->price_offered, $item->variety->latestPrice),
                ])->toArray(),
                'total_quantity' => (float) $request->items->sum('quantity_kg'),
            ]);
    }

    public function create(int $dealerId, array $requestData, array $items): DealerRequest
    {
        return DB::transaction(function () use ($dealerId, $requestData, $items) {
            $request = DealerRequest::create([
                'dealer_id' => $dealerId,
                'transaction_date' => $requestData['transaction_date'],
                'status' => 'open',
            ]);

            foreach ($items as $item) {
                DealerRequestItem::create([
                    'dealer_request_id' => $request->id,
                    'variety_id' => $item['variety_id'],
                    'quantity_kg' => $item['quantity_kg'],
                    'price_offered' => $item['price_offered'],
                ]);
            }

            return $request->load('items.variety');
        });
    }

    public function update(DealerRequest $request, array $requestData, array $items): DealerRequest
    {
        if ($request->status !== 'open') {
            throw new \LogicException('Only open requests can be updated.');
        }

        return DB::transaction(function () use ($request, $requestData, $items) {
            $request->update(['transaction_date' => $requestData['transaction_date']]);
            $request->items()->delete();

            foreach ($items as $item) {
                DealerRequestItem::create([
                    'dealer_request_id' => $request->id,
                    'variety_id' => $item['variety_id'],
                    'quantity_kg' => $item['quantity_kg'],
                    'price_offered' => $item['price_offered'],
                ]);
            }

            return $request->fresh()->load('items.variety');
        });
    }

    public function markAsFulfilled(DealerRequest $request): bool
    {
        return $request->status === 'open' && $request->update(['status' => 'fulfilled']);
    }

    public function delete(DealerRequest $request): bool
    {
        return $request->delete();
    }

    public static function varietyOptions(): array
    {
        return cache()->remember('dealer_request_variety_options', 3600, fn() =>
            Variety::with('vegetable.category', 'latestPrice')
                ->get()
                ->groupBy('vegetable.category.name')
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

    public static function summary(?int $dealerId = null): array
    {
        $query = DealerRequest::query();
        
        if ($dealerId) {
            $query->where('dealer_id', $dealerId);
        }

        $totalOpen = (clone $query)->where('status', 'open')->count();
        $totalFulfilled = (clone $query)->where('status', 'fulfilled')->count();
        $totalExpired = (clone $query)->where('status', 'expired')->count();
        
        $upcomingTransactions = (clone $query)
            ->where('status', 'open')
            ->whereBetween('transaction_date', [now(), now()->addWeek()])
            ->count();

        return [
            'total_open' => $totalOpen,
            'total_fulfilled' => $totalFulfilled,
            'total_expired' => $totalExpired,
            'upcoming_transactions' => $upcomingTransactions,
        ];
    }

    private static function calculatePriceFlag(float $priceOffered, ?object $marketPrice): string
    {
        if (!$marketPrice) return 'unknown';

        $marketMin = (float) $marketPrice->price_min;
        $marketMax = (float) $marketPrice->price_max;

        if ($priceOffered < $marketMin) return 'low';
        if ($priceOffered > $marketMax) return 'premium';
        return 'fair';
    }

    public static function expireOldRequests(): int
    {
        return DealerRequest::where('status', 'open')
            ->where('transaction_date', '<', now())
            ->update(['status' => 'expired']);
    }
}
