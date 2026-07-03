<?php

namespace App\Http\Controllers\Dealer;

use App\Actions\Demand\CreateDemandAction;
use App\Actions\Demand\DeleteDemandAction;
use App\Actions\Demand\UpdateDemandAction;
use App\Data\Post\DealerDemandData;
use App\Enums\PostItemStatus;
use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\StoreDemandRequest;
use App\Http\Requests\Dealer\UpdateDemandRequest;
use App\Models\Marketplace\Post;
use App\Services\Dealer\DemandService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DemandController extends Controller
{
    public function __construct(
        private DemandService $demandService
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Post::class);

        $userId = $request->user()->id;

        return Inertia::render('dealer/demands/Index', [
            'varietyOptions' => Inertia::defer(fn () => $this->demandService->varietyOptions()),
            'demands' => Inertia::defer(fn () => DealerDemandData::collect(
                $this->demandService->paginated(userId: $userId, status: PostItemStatus::Ongoing)
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

        return Inertia::render('dealer/demands/Archived', [
            'filters' => ['status' => $status->value],
            'demands' => Inertia::defer(fn () => DealerDemandData::collect(
                $this->demandService->paginated(userId: $userId, status: $status)
            )),
        ]);
    }

    public function store(StoreDemandRequest $request, CreateDemandAction $action): RedirectResponse
    {
        Gate::authorize('create', [Post::class, PostType::Demand]);

        $action->handle(
            dealer: $request->user()->dealerProfile,
            validated: $request->validated(),
        );

        return redirect()->route('dealer.demands.index')
            ->with('flash', ['type' => 'success', 'message' => 'Request posted successfully!']);
    }

    public function update(UpdateDemandRequest $request, Post $demand, UpdateDemandAction $action): RedirectResponse
    {
        Gate::authorize('update', $demand);

        $action->handle(post: $demand, validated: $request->validated());

        return redirect()->route('dealer.demands.index')
            ->with('flash', ['type' => 'success', 'message' => 'Demand updated successfully!']);
    }

    public function destroy(Post $demand, DeleteDemandAction $action): RedirectResponse
    {
        Gate::authorize('delete', $demand);
        $action->handle($demand);

        return redirect()->route('dealer.demands.index')
            ->with('flash', ['type' => 'success', 'message' => 'Demand deleted.']);
    }
}
