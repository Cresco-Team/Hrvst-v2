<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\StoreVarietyRequest;
use App\Http\Requests\Admin\Product\UpdateVarietyRequest;
use App\Models\Product\Variety;
use App\Services\Product\VarietyService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VarietyController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/vegetables-varieties/Index', [
            'varieties' => VarietyService::paginated(),
            'summary' => VarietyService::summary(),
            'vegetableOptions' => VarietyService::vegetableOptions(),
        ]);
    }

    public function store(StoreVarietyRequest $request)
    {
        VarietyService::create($request->validated());

        return redirect()->route('admin.vegetables-varieties.index')
            ->with('flash', ['type' => 'success', 'message' => 'Variety created successfully.']);
    }

    public function update(UpdateVarietyRequest $request, Variety $variety)
    {
        VarietyService::update($variety, $request->validated());

        return redirect()->route('admin.vegetables-varieties.index')
            ->with('flash', ['type' => 'success', 'message' => 'Variety updated successfully.']);
    }

    public function destroy(Variety $variety)
    {
        $deleted = VarietyService::delete($variety);

        if (! $deleted) {
            return redirect()->route('admin.vegetables-varieties.index')
                ->with('flash', ['type' => 'error', 'message' => 'Cannot delete this variety.']);
        }

        return redirect()->route('admin.vegetables-varieties.index')
            ->with('flash', ['type' => 'success', 'message' => 'Variety deleted successfully.']);
    }
}