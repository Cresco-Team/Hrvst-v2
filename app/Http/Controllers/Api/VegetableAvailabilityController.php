<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vegetable\Vegetable;
use App\Services\Product\VegetableAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VegetableAvailabilityController extends Controller
{
    public function __construct(private VegetableAvailabilityService $service) {}

    public function slotSummary(Request $request, Vegetable $vegetable): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'time_slot' => ['nullable', Rule::in(['morning', 'afternoon', 'evening'])],
        ]);

        return response()->json(
            $this->service->slotSummary(
                vegetableId: $vegetable->id,
                date: $validated['date'],
                timeSlot: $validated['time_slot'] ?? null,
            )
        );
    }
}
