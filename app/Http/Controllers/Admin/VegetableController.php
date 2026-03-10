<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Product\CreateVarietyAction;
use App\Actions\Product\DeleteVarietyAction;
use App\Actions\Product\UpdateVarietyAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\StoreVarietyRequest;
use App\Http\Requests\Admin\Product\UpdateVarietyRequest;
use App\Http\Resources\Product\VarietyResource;
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
            'summary' => Inertia::defer(fn () => $this->varietyService->summary()),
            'filters' => [
                'price_filter' => $request->query('price_filter', null),
                'search'       => $request->query('search', null),
            ],
             'varieties' => Inertia::defer(fn () => VarietyResource::collection(
                $this->varietyService->paginated(
                    perPage: 20,
                    priceFilter: $request->query('price_filter', null),
                    search: $request->query('search', null),
                )
            )),
            'vegetableOptions' => Inertia::defer(fn () => $this->varietyService->vegetableOptions()),
        ]);
    }

    public function details(Variety $variety): JsonResponse
    {
        return response()->json(
            (new VarietyResource($this->varietyService->detailed($variety)))->resolve()
        );
    }

    public function store(StoreVarietyRequest $request, CreateVarietyAction $createVariety)
    {
        $createVariety->handle(
            validated: $request->safe()->except('image'),
            image: $request->file('image')
        );

        return redirect()->route('admin.vegetables.index')
            ->with('flash', ['type' => 'success', 'message' => 'Variety created successfully.']);
    }

    public function update(UpdateVarietyRequest $request, Variety $variety, UpdateVarietyAction $updateVariety)
    {
        $updateVariety->handle(
            variety: $variety,
            validated: $request->safe()->except('image'),
            image: $request->file('image')
        );

        return redirect()->route('admin.vegetables.index')
            ->with('flash', ['type' => 'success', 'message' => 'Variety updated successfully.']);
    }

    public function destroy(Variety $variety, DeleteVarietyAction $deleteVariety)
    {
        $deleteVariety->handle($variety);

        return redirect()->route('admin.vegetables.index')
            ->with('flash', ['type' => 'success', 'message' => 'Variety deleted successfully.']);
    }
}
