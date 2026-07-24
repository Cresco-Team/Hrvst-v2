<?php

namespace App\Http\Controllers\Farmer;

use App\Actions\Post\CreatePostAction;
use App\Actions\Post\DeletePostAction;
use App\Actions\Supply\UpdateSupplyAction;
use App\Data\Post\FarmerSupplyData;
use App\Enums\PostItemStatus;
use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\StoreSupplyRequest;
use App\Http\Requests\Farmer\UpdateSupplyRequest;
use App\Models\Marketplace\Post;
use App\Services\Farmer\SupplyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SupplyController extends Controller
{
    public function __construct(private SupplyService $supplyService) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Post::class);

        $userId = $request->user()->id;

        return Inertia::render('farmer/supplies/Index', [
            'varietyOptions' => Inertia::defer(fn () => $this->supplyService->varietyOptions()),
            'needsAction' => Inertia::defer(fn () => FarmerSupplyData::collect(
                $this->supplyService->needsAction($userId)
            )),
            'supplies' => Inertia::defer(fn () => FarmerSupplyData::collect(
                $this->supplyService->paginatedSupply(userId: $userId, status: PostItemStatus::Ongoing)
            )),
        ]);
    }

    public function archived(Request $request): Response
    {
        Gate::authorize('viewAny', Post::class);

        $userId = $request->user()->id;
        $status = PostItemStatus::tryFrom($request->query('status', PostItemStatus::Expired->value));

        if (! $status || $status === PostItemStatus::Ongoing) {
            $status = PostItemStatus::Expired;
        }

        return Inertia::render('farmer/supplies/Archived', [
            'filters' => ['status' => $status->value],
            'supplies' => Inertia::defer(fn () => FarmerSupplyData::collect(
                $this->supplyService->paginatedSupply(userId: $userId, status: $status)
            )),
        ]);
    }

    public function store(StoreSupplyRequest $request, CreatePostAction $action): RedirectResponse
    {
        Gate::authorize('create', [Post::class, PostType::Supply]);

        $action->handle(
            userId: $request->user()->id,
            type: PostType::Supply,
            validated: $request->validated()
        );

        return back(fallback: route('farmer.supplies.index'))
            ->with('flash', ['type' => 'success', 'message' => 'Supply posted successfully!']);
    }

    public function update(UpdateSupplyRequest $request, Post $supply, UpdateSupplyAction $action): RedirectResponse
    {
        Gate::authorize('update', $supply);

        $action->handle(post: $supply, validated: $request->validated());

        return back(fallback: route('farmer.supplies.index'))
            ->with('flash', ['type' => 'success', 'message' => 'Supply updated successfully!']);
    }

    public function destroy(Post $supply, DeletePostAction $action): RedirectResponse
    {
        Gate::authorize('delete', $supply);
        $action->handle($supply);

        return back(fallback: route('farmer.supplies.index'))
            ->with('flash', ['type' => 'success', 'message' => 'Supply deleted.']);
    }
}
