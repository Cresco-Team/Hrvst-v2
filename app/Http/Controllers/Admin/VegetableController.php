<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\StoreVegetableRequest;
use App\Http\Requests\Admin\Product\UpdateVegetableRequest;
use App\Models\Product\Vegetable;
use App\Services\VegetableService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VegetableController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/vegetables/Index', [
            'vegetables' => VegetableService::paginated(),
            'summary' => VegetableService::summary(),
            'categoryOptions' => VegetableService::categoryOptions(),
        ]);
    }

    public function store(StoreVegetableRequest $request)
    {
        VegetableService::create($request->validated());

        return redirect()->route('admin.vegetables.index')
            ->with('flash', ['type' => 'success', 'message' => 'Vegetable created successfully.']);
    }

    public function update(UpdateVegetableRequest $request, Vegetable $vegetable)
    {
        VegetableService::update($vegetable, $request->validated());

        return redirect()->route('admin.vegetables.index')
            ->with('flash', ['type' => 'success', 'message' => 'Vegetable updated successfully.']);
    }

    public function destroy(Vegetable $vegetable)
    {
        $deleted = VegetableService::delete($vegetable);

        if (! $deleted) {
            return redirect()->route('admin.vegetables.index')
                ->with('flash', ['type' => 'error', 'message' => 'Cannot delete this vegetable — it still has varieties.']);
        }

        return redirect()->route('admin.vegetables.index')
            ->with('flash', ['type' => 'success', 'message' => 'Vegetable deleted successfully.']);
    }
}
