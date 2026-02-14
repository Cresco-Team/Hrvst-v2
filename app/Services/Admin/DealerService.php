<?php

namespace App\Services\Admin;

use App\DealerRequestStatus;
use App\Models\Marketplace\DealerRequest;
use App\Models\Profiles\DealerProfile;
use Carbon\Carbon;
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
        $newRequestsThisMonth = DealerRequest::where('created_at', now()->startOfMonth())
            ->count();

        return [
            'total_dealers' => $totalDealers,
            'new_dealers_this_month' => $newDealersThisMonth,
            'total_requests' => DealerRequest::count(),
            'new_requests_this_month' => $newRequestsThisMonth,
        ];
    }

    public static function paginated(int $perPage = 20, ?int $page = null): LengthAwarePaginator
    {
        return DealerProfile::with([
            'user',
            'requests' => fn($query) => $query->where('status', DealerRequestStatus::Open)
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
                    'open_requests_count' => $dealer->requests->count(),
                    'open_requests' => $dealer->requests->map(fn($request) => [
                        'id' => $request->id,
                        'variety' => [
                            'id' => $request->variety->id,
                            'name' => $request->variety->name,
                            'category' => $request->variety->vegetable->category->name,
                            'image_url' => $request->variety->image_url,
                        ],
                        'quantity_kg' => $request->quantity_kg,
                        'transaction_date' => $request->transaction_date->format('M d, Y'),
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
            'requests' => fn($query) => $query->with(['variety.vegetable.category'])
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
            'requests' => $dealer->requests->map(fn($request) => [
                'id' => $request->id,
                'variety' => [
                    'id' => $request->variety->id,
                    'name' => $request->variety->vegetable->name . ' ' . $request->variety->name,
                    'category' => $request->variety->vegetable->category->name,
                    'image_path' => $request->variety->image_path,
                ],
                'quantity_kg' => $request->quantity_kg,
                'created_at' => $request->created_at->format('M d, Y'),
                'transaction_date' => $request->transaction_date->format('M d, Y'),
                'status' => $request->status,
            ]),
            'joined_at' => $dealer->created_at->format('M d, Y'),
            'joined_at_human' => $dealer->created_at->diffForHumans(),
        ];
    }

    public static function pending(): array
    {
        return DealerProfile::with([
            'user'
        ])
            ->where('is_approved', false)
            ->order_by('created_at', 'asc')
            ->get()
            ->map(fn($dealer) => [
                'id' => $dealer->idate,
                'user' => [
                    'id' => $dealer->user->id,
                    'name' => $dealer->user->name,
                    'email' => $dealer->user->email,
                    'phone_number' => $dealer->user->phone_number,
                    'user_image' => $dealer->user->user_image,
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
