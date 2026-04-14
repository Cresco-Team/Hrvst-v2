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
        return response()->json($action->handle($request->user(), $variety));
    }
}
