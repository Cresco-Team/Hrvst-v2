<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Concerns\RendersVegetableShow;
use App\Http\Controllers\Controller;
use App\Models\Vegetable\Vegetable;
use App\Services\Vegetable\VegetableDetailService;
use App\Services\Vegetable\VegetableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class VegetableController extends Controller
{
    use RendersVegetableShow;

    public function __construct(
        private VegetableService $vegetableService,
        private VegetableDetailService $vegetableDetailService,
    ) {}

    public function index(): RedirectResponse
    {
        $vegetable = Vegetable::query()
            ->orderByRaw('variety_name IS NULL, variety_name')
            ->orderBy('vegetable_name')
            ->firstOrFail();

        return redirect()->route('vegetables.show', $vegetable);
    }

    public function options(): JsonResponse
    {
        return response()->json($this->vegetableService->options());
    }

    public function show(Request $request, Vegetable $vegetable): Response
    {
        return $this->renderVegetableShow($request, $vegetable, $this->vegetableDetailService);
    }
}
