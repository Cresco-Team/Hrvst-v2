<?php

namespace App\Http\Controllers\Concerns;

use App\Data\Vegetable\VegetableDetailData;
use App\Enums\Analytics\VarietyViewerRole;
use App\Models\Product\Vegetable;
use App\Services\Product\VegetableDetailService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

trait RendersVegetableShow
{
    private function renderVegetableShow(
        Request $request,
        Vegetable $vegetable,
        VegetableDetailService $vegetableDetailService,
    ): Response {
        $vegetable->loadMissing('category');

        $validated = $request->validate([
            'year' => ['sometimes', 'integer', 'min:2020', 'max:2035'],
            'month' => ['sometimes', 'integer', 'min:1', 'max:12'],
        ]);

        $year = (int) ($validated['year'] ?? now()->year);
        $month = (int) ($validated['month'] ?? now()->month);

        $role = match (true) {
            $request->user()->hasRole('admin') => VarietyViewerRole::Admin,
            $request->user()->hasRole('farmer') => VarietyViewerRole::Farmer,
            $request->user()->hasRole('dealer') => VarietyViewerRole::Dealer,
            default => throw new \RuntimeException('User has no recognized role for variety viewer context.'),
        };

        return Inertia::render('shared/vegetables/Show', [
            'vegetable' => Inertia::defer(
                fn () => VegetableDetailData::fromModel(
                    $vegetableDetailService->show($vegetable, $year, $month, $role)
                )
            ),
            'calendarFilters' => ['year' => $year, 'month' => $month],
            'meta' => [
                'vegetableId' => $vegetable->id,
                'vegetableLabel' => $vegetable->display_name,
                'categoryName' => $vegetable->category->name,
                'categorySlug' => $vegetable->category->slug,
            ],
        ]);
    }
}
