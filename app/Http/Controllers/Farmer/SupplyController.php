<?php

namespace App\Http\Controllers\Farmer;

use App\Actions\Supply\ArchiveSupplyAction;
use App\Actions\Supply\CreateSupplyAction;
use App\Actions\Supply\DeleteSupplyAction;
use App\Actions\Supply\FulfillSupplyAction;
use App\Actions\Supply\HarvestSupplyAction;
use App\Actions\Supply\UpdateSupplyAction;
use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\HarvestSupplyRequest;
use App\Http\Requests\Farmer\StoreSupplyRequest;
use App\Http\Requests\Farmer\UpdateSupplyRequest;
use App\Http\Resources\Marketplace\FarmerSupplyResource;
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
        $status = PostStatus::tryFrom($request->query('status', PostStatus::Growing->value));

        return Inertia::render('farmer/supplies/Index', [
            'filters' => ['status' => $status],
            'summary' => Inertia::defer(fn () => $this->supplyService->summary($userId)),
            'vegetableOptions' => Inertia::defer(fn () => $this->supplyService->vegetableOptions()),
            'varietyOptions' => Inertia::defer(fn () => $this->supplyService->varietyOptions()),
            'supplies' => Inertia::defer(fn () => FarmerSupplyResource::collection(
                $this->supplyService->paginated(userId: $userId, status: $status)
            )),
        ]);
    }

    public function store(StoreSupplyRequest $request, CreateSupplyAction $action): RedirectResponse
    {
        Gate::authorize('create', [Post::class, PostType::Supply]);

        $action->handle(
            farmer: $request->user()->farmerProfile,
            validated: $request->validated(),
            image: $request->file('image')
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
            image: $request->file('image')
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

    public function archive(Post $supply, ArchiveSupplyAction $action): RedirectResponse
    {
        Gate::authorize('archive', $supply);
        $action->handle($supply);

        return redirect()->route('farmer.supplies.index')
            ->with('flash', ['type' => 'success', 'message' => 'Supply archived.']);
    }

    public function fulfill(Post $supply, FulfillSupplyAction $action): RedirectResponse
    {
        Gate::authorize('fulfill', $supply);
        $action->handle($supply);

        return redirect()->route('farmer.supplies.index')
            ->with('flash', ['type' => 'success', 'message' => 'Supply marked as fulfilled!']);
    }

    public function destroy(Post $supply, DeleteSupplyAction $action): RedirectResponse
    {
        Gate::authorize('delete', $supply);
        $action->handle($supply);

        return redirect()->route('farmer.supplies.index')
            ->with('flash', ['type' => 'success', 'message' => 'Supply deleted.']);
    }
}
