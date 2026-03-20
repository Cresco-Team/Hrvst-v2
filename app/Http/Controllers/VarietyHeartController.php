<?php

namespace App\Http\Controllers;

use App\Actions\Product\ToggleVarietyHeartAction;
use App\Models\Product\Variety;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VarietyHeartController extends Controller
{
    public function toggle(Request $request, Variety $variety, ToggleVarietyHeartAction $action): JsonResponse
    {
        $user = $request->user();

        $canHeart = $user->farmerProfile?->is_approved === true
            || $user->dealerProfile?->is_approved === true;

        if (! $canHeart) {
            abort(403, 'Only approved farmers and dealers can react to varieties.');
        }

        return response()->json($action->handle($user, $variety));
    }
}
