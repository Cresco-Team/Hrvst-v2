<?php

namespace App\Http\Controllers\Admin\Vegetable;

use App\Actions\Product\AddVarietyPriceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StorePriceHistoryRequest;
use App\Models\Product\Variety;
use Illuminate\Http\RedirectResponse;

class VarietyPriceController extends Controller
{
    public function store(StorePriceHistoryRequest $request, Variety $variety, AddVarietyPriceAction $addPrice): RedirectResponse
    {
        $addPrice->handle(
            variety: $variety,
            priceMin: (float) $request->validated('price_min'),
            priceMax: (float) $request->validated('price_max'),
        );

        return redirect()->back()
            ->with('flash', ['type' => 'success', 'message' => 'Price updated successfully.']);
    }
}
