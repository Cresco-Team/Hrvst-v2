<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\StoreVarietyRequest;
use App\Http\Requests\Admin\Product\UpdateVarietyRequest;
use App\Models\Product\Variety;
use App\Services\Product\VarietyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VegetableController extends Controller
{
    public function __construct(
        private VarietyService $varietyService
    ) {}

    public function index(Request $request)
    {
        return Inertia::render('admin/vegetables/Index', [
            'summary' => Inertia::defer(fn () => VarietyService::summary()),
            'filters' => [
                'price_filter' => $request->query('price_filter', null),
            ],
            'varieties' => Inertia::defer(fn () => VarietyService::paginated(
                perPage: 20,
                priceFilter: $request->query('price_filter', null)
            )),
            'categoryOptions' => Inertia::defer(fn () => VarietyService::categoryOptions()),
        ]);
    }

    public function details(Variety $variety): JsonResponse
    {
        return response()->json(VarietyService::detailed($variety));
    }

    public function store(StoreVarietyRequest $request)
    {
        $this->varietyService->create(
            $request->safe()->except('image'),
            $request->file('image')
        );

        return redirect()->route('admin.vegetables.index')
            ->with('flash', ['type' => 'success', 'message' => 'Variety created successfully.']);
    }

    public function update(UpdateVarietyRequest $request, Variety $variety)
    {
        $this->varietyService->update(
            $variety,
            $request->safe()->except('image'),
            $request->file('image')
        );

        return redirect()->route('admin.vegetables.index')
            ->with('flash', ['type' => 'success', 'message' => 'Variety updated successfully.']);
    }

    public function destroy(Variety $variety)
    {
        $deleted = $this->varietyService->delete($variety);

        if (! $deleted) {
            return redirect()->route('admin.vegetables.index')
                ->with('flash', ['type' => 'error', 'message' => 'Cannot delete variety with existing plantings.']);
        }

        return redirect()->route('admin.vegetables.index')
            ->with('flash', ['type' => 'success', 'message' => 'Variety deleted successfully.']);
    }
}
