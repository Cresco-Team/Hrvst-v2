<?php

namespace App\Http\Controllers\Admin\Vegetable;

use App\Actions\Product\CreateVarietyAction;
use App\Actions\Product\DeleteVarietyAction;
use App\Actions\Product\UpdateVarietyAction;
use App\Data\Variety\VarietyDetailData;
use App\Enums\Analytics\VarietyViewerRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\StoreVarietyRequest;
use App\Http\Requests\Admin\Product\UpdateVarietyRequest;
use App\Http\Resources\Product\VarietyDetailResource;
use App\Models\Product\Variety;
use App\Services\Product\VarietyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VarietyController extends Controller
{
    public function __construct(
        private VarietyService $varietyService
    ) {}

    public function show(Request $request, Variety $variety): Response
    {
        $validated = $request->validate([
            'year' => ['sometimes', 'integer', 'min:2020', 'max:2035'],
            'month' => ['sometimes', 'integer', 'min:1', 'max:12'],
        ]);

        $year = (int) ($validated['year'] ?? now()->year);
        $month = (int) ($validated['month'] ?? now()->month);

        return Inertia::render('admin/vegetables/Show', [
            'variety' => Inertia::defer(
                fn () => VarietyDetailData::fromModel(
                    $this->varietyService->show($variety, $year, $month, VarietyViewerRole::Admin)
                )
            ),
            'calendarFilters' => [
                'year' => $year,
                'month' => $month,
            ],
            'meta' => [
                'varietyId' => $variety->id,
                'varietyLabel' => "{$variety->vegetable->name}: {$variety->name}",
                'categoryName' => $variety->vegetable->category->name,
                'categorySlug' => $variety->vegetable->category->slug,
            ],
        ]);
    }

    public function store(StoreVarietyRequest $request, CreateVarietyAction $createVariety): RedirectResponse
    {
        $createVariety->handle(validated: $request->validated());

        return redirect()->back()
            ->with('flash', ['type' => 'success', 'message' => 'Variety created successfully.']);
    }

    public function update(UpdateVarietyRequest $request, Variety $variety, UpdateVarietyAction $updateVariety): RedirectResponse
    {
        $updateVariety->handle(
            variety: $variety,
            validated: $request->validated(),
        );

        return redirect()->back()
            ->with('flash', ['type' => 'success', 'message' => 'Variety updated successfully.']);
    }

    public function destroy(Variety $variety, DeleteVarietyAction $deleteVariety): RedirectResponse
    {
        $deleteVariety->handle($variety);

        return redirect()->back()
            ->with('flash', ['type' => 'success', 'message' => 'Variety deleted successfully.']);
    }
}
