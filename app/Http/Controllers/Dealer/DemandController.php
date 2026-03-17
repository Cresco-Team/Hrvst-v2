<?php

namespace App\Http\Controllers\Dealer;

use App\Actions\Demand\ArchiveDemandAction;
use App\Actions\Demand\CreateDemandAction;
use App\Actions\Demand\DeleteDemandAction;
use App\Actions\Demand\FulfillDemandAction;
use App\Actions\Demand\UpdateDemandAction;
use App\Enums\PostStatus;
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
    public function __construct (
        private DemandService $demandService
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Post::class);

        $userId = $request->user()->id;
        $status = PostStatus::tryFrom($request->query('status', PostStatus::Ongoing->value));

        return Inertia::render('dealer/demands/Index', [
            'filters'        => ['status' => $status],
            'summary'        => Inertia::defer(fn () => $this->demandService->summary($userId)),
            'varietyOptions' => Inertia::defer(fn () => $this->demandService->varietyOptions()),
            'demands'        => Inertia::defer(fn () => DealerDemandResource::collection(
                $this->demandService->paginated(userId: $userId, status: $status)
            )),
        ]);
    }

    public function store(StoreDemandRequest $request, CreateDemandAction $createDemand): RedirectResponse
    {
        Gate::authorize('create', Post::class);

        $createDemand->handle(
            dealer:    $request->user()->dealerProfile,
            validated: $request->validated(),
        );

        return redirect()->route('dealer.demands.index')
            ->with('flash', ['type' => 'success', 'message' => 'Request posted successfully!']);
    }

    public function update(UpdateDemandRequest $request, Post $demand, UpdateDemandAction $updateDemand): RedirectResponse
    {
        Gate::authorize('update', $demand);

        $updateDemand->handle(
            post: $demand, 
            validated: $request->validated()
        );

        return redirect()->route('dealer.demands.index')
            ->with('flash', ['type' => 'success', 'message' => 'Post updated successfully!']);
    }

    public function archive(Post $demand, ArchiveDemandAction $archiveDemand): RedirectResponse
    {
        Gate::authorize('archive', $demand);
        $archiveDemand->handle($demand);

        return redirect()->route('dealer.demands.index')
            ->with('flash', ['type' => 'success', 'message' => 'Post archived.']);
    }

    public function fulfill(Post $demand, FulfillDemandAction $fulfillDemand): RedirectResponse
    {
        Gate::authorize('fulfill', $demand);
        $fulfillDemand->handle($demand);

        return redirect()->route('dealer.demands.index')
            ->with('flash', ['type' => 'success', 'message' => 'Post marked as fulfilled!']);
    }

    public function destroy(Post $demand, DeleteDemandAction $deleteDemand): RedirectResponse
    {
        Gate::authorize('delete', $demand);
        $deleteDemand->handle($demand);

        return redirect()->route('dealer.demands.index')
            ->with('flash', ['type' => 'success', 'message' => 'Post deleted.']);
    }
}
