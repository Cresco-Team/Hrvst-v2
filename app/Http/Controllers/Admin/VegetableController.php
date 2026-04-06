<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vegetable\StoreVegetableRequest;
use App\Http\Requests\Vegetable\UpdateVegetableRequest;
use App\Models\Product\Vegetable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class VegetableController extends Controller
{
    public function store(StoreVegetableRequest $request): RedirectResponse
    {
        Gate::authorize('create', Vegetable::class);

        Vegetable::create($request->validated());

        return redirect()->route('admin.vegetables.varieties.index')
            ->with('flash', ['type' => 'success', 'message' => 'Vegetable created successfully.']);
    }

    public function update(UpdateVegetableRequest $request, Vegetable $vegetable): RedirectResponse
    {
        Gate::authorize('update', $vegetable);

        $vegetable->update($request->validated());

        return redirect()->route('admin.vegetables.varieties.index')
            ->with('flash', ['type' => 'success', 'message' => 'Vegetable updated successfully.']);
    }

    public function destroy(Vegetable $vegetable): RedirectResponse
    {
        Gate::authorize('delete', $vegetable);

        $vegetable->delete();

        return redirect()->route('admin.vegetables.varieties.index')
            ->with('flash', ['type' => 'success', 'message' => 'Vegetable deleted successfully.']);
    }
}
