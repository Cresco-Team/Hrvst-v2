<?php

namespace App\Services\Admin;

use App\Enums\DealerDemandStatus;
use App\Models\Marketplace\DealerDemand;
use App\Models\Profiles\DealerProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DealerService
{
    public static function summary(): array
    {
        $totalDealers = DealerProfile::where('is_approved', true)
            ->count();
        $newDealersThisMonth = DealerProfile::where('is_approved', true)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
        $newDemandsThisMonth = DealerDemand::where('created_at', now()->startOfMonth())
            ->count();

        return [
            'total_dealers' => $totalDealers,
            'new_dealers_this_month' => $newDealersThisMonth,
            'total_demands' => DealerDemand::count(),
            'new_demands_this_month' => $newDemandsThisMonth,
        ];
    }

    public static function paginated(int $perPage = 20, ?int $page = null): LengthAwarePaginator
    {
        return DealerProfile::with([
            'user',
            'demands' => fn($query) => $query->where('status', DealerDemandStatus::Open)
                ->with(['variety.vegetable.category'])
                ->orderBy('transaction_date', 'asc')
            ])
            ->where('is_approved', true)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], $page)
            ->through(function ($dealer) {
                return [
                    'id' => $dealer->id,
                    'user' => [
                        'id' => $dealer->user->id,
                        'name' => $dealer->user->name,
                        'email' => $dealer->user->email,
                        'phone_number' => $dealer->user->phone_number,
                        'image_path' => $dealer->user->image_path,
                    ],
                    'document_image' => $dealer->document_image,
                    'open_demands_count' => $dealer->demands->count(),
                    'open_demands' => $dealer->demands->map(fn($demand) => [
                        'id' => $demand->id,
                        'variety' => [
                            'id' => $demand->variety->id,
                            'name' => $demand->variety->name,
                            'category' => $demand->variety->vegetable->category->name,
                            'image_url' => $demand->variety->image_url,
                        ],
                        'quantity_kg' => $demand->quantity_kg,
                        'transaction_date' => $demand->transaction_date->format('M d, Y'),
                    ]),
                    'joined_at' => $dealer->created_at->format('M d, Y'),
                    'joined_at_human' => $dealer->created_at->diffForHumans(),
                ];
            });
    }

    public static function find(int $dealerId): ?array
    {
        $dealer = DealerProfile::with([
            'user',
            'demands' => fn($query) => $query->with(['variety.vegetable.category'])
                ->orderBy('created_at', 'desc'),
        ])
            ->where('is_approved', true)
            ->find($dealerId);

        if (!$dealer) {
            return null;
        }

        return [
            'id' => $dealer->id,
            'user' => [
                'id' => $dealer->user->id,
                'name' => $dealer->user->name,
                'email' => $dealer->user->email,
                'phone_number' => $dealer->user->phone_number,
                'image_path' => $dealer->user->image_path,
            ],
            'document_image' => $dealer->document_image,
            'demands' => $dealer->demands->map(fn($demand) => [
                'id' => $demand->id,
                'variety' => [
                    'id' => $demand->variety->id,
                    'name' => $demand->variety->vegetable->name . ' ' . $demand->variety->name,
                    'category' => $demand->variety->vegetable->category->name,
                    'image_path' => $demand->variety->image_path,
                ],
                'quantity_kg' => $demand->quantity_kg,
                'created_at' => $demand->created_at->format('M d, Y'),
                'transaction_date' => $demand->transaction_date->format('M d, Y'),
                'status' => $demand->status,
            ]),
            'joined_at' => $dealer->created_at->format('M d, Y'),
            'joined_at_human' => $dealer->created_at->diffForHumans(),
        ];
    }

    public static function pending(): array
    {
        return DealerProfile::with(['user'])
            ->where('is_approved', false)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($dealer) => [
                'id' => $dealer->id,
                'user' => [
                    'id' => $dealer->user->id,
                    'name' => $dealer->user->name,
                    'email' => $dealer->user->email,
                    'phone_number' => $dealer->user->phone_number,
                    'image_path' => $dealer->user->image_path,
                ],
                'document_image' => $dealer->document_image,
                'submitted_at' => $dealer->created_at->format('M d, Y g:i A'),
                'submitted_at_human' => $dealer->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    public static function approve(int $dealerId): bool
    {
        $dealer = DealerProfile::where('is_approved', false)->find($dealerId);

        if (!$dealer) return false;

        return $dealer->update(['is_approved' => true]);
    }

    public static function reject(int $dealerId): bool
    {
        $dealer = DealerProfile::where('is_approved', false)->find($dealerId);

        if (!$dealer) return false;

        $user = $dealer->user;
        $dealer->delete();
        $user->delete();

        return true;
    }
}
