<?php

namespace App\Http\Controllers\Dealer;

use App\Actions\Demand\ArchiveDemandAction;
use App\Actions\Demand\CreateDemandAction;
use App\Actions\Demand\DeleteDemandAction;
use App\Actions\Demand\FulfillDemandAction;
use App\Actions\Demand\UpdateDemandAction;
use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\StoreDemandRequest;
use App\Http\Requests\Dealer\UpdateDemandRequest;
use App\Http\Resources\Marketplace\DealerDemandResource;
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
        $status = PostStatus::tryFrom($request->query('status', PostStatus::Ongoing->value));

        return Inertia::render('dealer/demands/Index', [
            'filters' => ['status' => $status],
            'summary' => Inertia::defer(fn () => $this->demandService->summary($userId)),
            'vegetableOptions' => Inertia::defer(fn () => $this->demandService->vegetableOptions()),
            'varietyOptions' => Inertia::defer(fn () => $this->demandService->varietyOptions()),
            'demands' => Inertia::defer(fn () => DealerDemandResource::collection(
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

    public function archive(Post $demand, ArchiveDemandAction $action): RedirectResponse
    {
        Gate::authorize('archive', $demand);
        $action->handle($demand);

        return redirect()->route('dealer.demands.index')
            ->with('flash', ['type' => 'success', 'message' => 'Demand archived.']);
    }

    public function fulfill(Post $demand, FulfillDemandAction $action): RedirectResponse
    {
        Gate::authorize('fulfill', $demand);
        $action->handle($demand);

        return redirect()->route('dealer.demands.index')
            ->with('flash', ['type' => 'success', 'message' => 'Demand marked as fulfilled!']);
    }

    public function destroy(Post $demand, DeleteDemandAction $action): RedirectResponse
    {
        Gate::authorize('delete', $demand);
        $action->handle($demand);

        return redirect()->route('dealer.demands.index')
            ->with('flash', ['type' => 'success', 'message' => 'Demand deleted.']);
    }
}
