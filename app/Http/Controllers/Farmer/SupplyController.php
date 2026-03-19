<?php

namespace App\Http\Controllers\Farmer;

use App\Actions\Supply\ArchiveSupplyAction;
use App\Actions\Supply\CreateSupplyAction;
use App\Actions\Supply\DeleteSupplyAction;
use App\Actions\Supply\FulfillSupplyAction;
use App\Actions\Supply\UpdateSupplyAction;
use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Http\Controllers\Controller;
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
        $status = PostStatus::tryFrom($request->query('status', PostStatus::Ongoing->value));

        return Inertia::render('farmer/supplies/Index', [
            'filters' => ['status' => $status],
            'summary' => Inertia::defer(fn () => $this->supplyService->summary($userId)),
            'varietyOptions' => Inertia::defer(fn () => $this->supplyService->varietyOptions()),
            'supplies' => Inertia::defer(fn () => FarmerSupplyResource::collection(
                $this->supplyService->paginated(userId: $userId, status: $status)
            )),
        ]);
    }

    public function store(StoreSupplyRequest $request, CreateSupplyAction $createSupply): RedirectResponse
    {
        Gate::authorize('create', [Post::class, PostType::Supply]);

        $createSupply->handle(
            farmer: $request->user()->farmerProfile,
            validated: $request->validated(),
            image: $request->file('image')
        );

        return redirect()->route('farmer.supplies.index')
            ->with('flash', ['type' => 'success', 'message' => 'Supply posted successfully!']);
    }

    public function update(UpdateSupplyRequest $request, Post $supply, UpdateSupplyAction $updateSupply): RedirectResponse
    {
        Gate::authorize('update', $supply);

        $updateSupply->handle(
            post: $supply,
            validated: $request->validated(),
            image: $request->file('image')
        );

        return redirect()->route('farmer.supplies.index')
            ->with('flash', ['type' => 'success', 'message' => 'Post updated successfully!']);
    }

    public function archive(Post $supply, ArchiveSupplyAction $archiveSupply): RedirectResponse
    {
        Gate::authorize('archive', $supply);
        $archiveSupply->handle($supply);

        return redirect()->route('farmer.supplies.index')
            ->with('flash', ['type' => 'success', 'message' => 'Post archived.']);
    }

    public function fulfill(Post $supply, FulfillSupplyAction $fulfillSupply): RedirectResponse
    {
        Gate::authorize('fulfill', $supply);
        $fulfillSupply->handle($supply);

        return redirect()->route('farmer.supplies.index')
            ->with('flash', ['type' => 'success', 'message' => 'Post marked as fulfilled!']);
    }

    public function destroy(Post $supply, DeleteSupplyAction $deleteSupply): RedirectResponse
    {
        Gate::authorize('delete', $supply);
        $deleteSupply->handle($supply);

        return redirect()->route('farmer.supplies.index')
            ->with('flash', ['type' => 'success', 'message' => 'Post deleted.']);
    }
}
