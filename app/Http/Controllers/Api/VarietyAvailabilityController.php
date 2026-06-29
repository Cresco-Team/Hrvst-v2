<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product\Variety;
use App\Services\Product\VarietyAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VarietyAvailabilityController extends Controller
{
    public function __construct(private VarietyAvailabilityService $service) {}

    public function slotSummary(Request $request, Variety $variety): JsonResponse
    {
        $validated = $request->validate([
            'date'      => ['required', 'date_format:Y-m-d'],
            'time_slot' => ['nullable', Rule::in(['morning', 'afternoon', 'evening'])],
        ]);

        return response()->json(
            $this->service->slotSummary(
                varietyId: $variety->id,
                date:      $validated['date'],
                timeSlot:  $validated['time_slot'] ?? null,
            )
        );
    }
}
