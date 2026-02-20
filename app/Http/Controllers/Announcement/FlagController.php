<?php

namespace App\Http\Controllers\Announcement;

use App\Http\Controllers\Controller;
use App\Services\Announcement\FlagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlagController extends Controller
{
    public function __construct(
        private FlagService $service
    ) {}

    /**
     * Flag content as inappropriate
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'flaggable_type' => ['required', 'in:DealerDemand,FarmerOffering,AnnouncementComment'],
            'flaggable_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $flag = $this->service->flag(
                user: $request->user(),
                flaggableType: $validated['flaggable_type'],
                flaggableId: $validated['flaggable_id'],
                reason: $validated['reason'],
                description: $validated['description'] ?? null
            );

            return response()->json([
                'message' => 'Content flagged for review. Our team will investigate.',
                'flag_id' => $flag->id,
            ], 201);
        } catch (\LogicException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
