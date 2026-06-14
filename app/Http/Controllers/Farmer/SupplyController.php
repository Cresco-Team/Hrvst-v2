<?php

namespace App\Http\Controllers\Farmer;

use App\Actions\Supply\CreateSupplyAction;
use App\Actions\Supply\DeleteSupplyAction;
use App\Actions\Supply\HarvestSupplyAction;
use App\Actions\Supply\UpdateSupplyAction;
use App\Enums\PostItemStatus;
use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\HarvestSupplyRequest;
use App\Http\Requests\Farmer\StoreSupplyRequest;
use App\Http\Requests\Farmer\UpdateSupplyRequest;
use App\Http\Resources\Marketplace\FarmerSupplyResource;
use App\Http\Resources\Marketplace\PostItemResource;
use App\Models\Marketplace\Post;
use App\Services\Farmer\SupplyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SupplyController extends Controller
{
    public function __construct(
        private SupplyService $supplyService
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Post::class);

        $userId = $request->user()->id;
        $rawStatus = $request->query('status', 'growing');
        $isGrowing = $rawStatus === 'growing';
        $postItemStatus = ! $isGrowing
            ? PostItemStatus::tryFrom($rawStatus) ?? PostItemStatus::Ongoing
            : null;

        return Inertia::render('farmer/supplies/Index', [
            'filters' => ['status' => $rawStatus],
            'summary' => Inertia::defer(fn () => $this->supplyService->summary($userId)),
            'vegetableOptions' => Inertia::defer(fn () => $this->supplyService->vegetableOptions()),

            'varietyOptions' => $isGrowing
                ? Inertia::defer(fn () => $this->supplyService->varietyOptions())
                : $this->supplyService->varietyOptions(),

            'growingPosts' => $isGrowing
                ? Inertia::defer(fn () => FarmerSupplyResource::collection(
                    $this->supplyService->paginatedGrowing(userId: $userId)
                ))
                : null,
            'harvestedItems' => ! $isGrowing
                ? Inertia::defer(fn () => PostItemResource::collection(
                    $this->supplyService->paginatedHarvested(userId: $userId, status: $postItemStatus)
                ))
                : null,
        ]);
    }

    public function store(StoreSupplyRequest $request, CreateSupplyAction $action): RedirectResponse
    {
        Gate::authorize('create', [Post::class, PostType::Supply]);

        $action->handle(
            farmer: $request->user()->farmerProfile,
            validated: $request->validated(),
        );

        return redirect()->route('farmer.supplies.index')
            ->with('flash', ['type' => 'success', 'message' => 'Supply posted successfully!']);
    }

    public function update(UpdateSupplyRequest $request, Post $supply, UpdateSupplyAction $action): RedirectResponse
    {
        Gate::authorize('update', $supply);

        $action->handle(
            post: $supply,
            validated: $request->validated(),
        );

        return redirect()->route('farmer.supplies.index')
            ->with('flash', ['type' => 'success', 'message' => 'Supply updated successfully!']);
    }

    public function harvest(HarvestSupplyRequest $request, Post $supply, HarvestSupplyAction $action): RedirectResponse
    {
        Gate::authorize('harvest', $supply);

        $action->handle(post: $supply, validated: $request->validated());

        return redirect()->route('farmer.supplies.index')
            ->with('flash', ['type' => 'success', 'message' => 'Harvest recorded! Supply is now live.']);
    }

    public function destroy(Post $supply, DeleteSupplyAction $action): RedirectResponse
    {
        Gate::authorize('delete', $supply);
        $action->handle($supply);

        return redirect()->route('farmer.supplies.index')
            ->with('flash', ['type' => 'success', 'message' => 'Supply deleted.']);
    }
}
