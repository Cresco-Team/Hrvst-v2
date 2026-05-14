<?php

namespace App\Http\Controllers\Dealer;

use App\Actions\PostItem\ArchivePostItemAction;
use App\Actions\PostItem\FulfillPostItemAction;
use App\Actions\PostItem\UpdatePostItemAction;
use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\UpdatePostItemRequest;
use App\Models\Marketplace\PostItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PostItemController extends Controller
{
    public function update(UpdatePostItemRequest $request, PostItem $postItem, UpdatePostItemAction $action): RedirectResponse
    {
        $postItem->load('post');
        Gate::authorize('update', $postItem);

        abort_if($postItem->post->type !== PostType::Demand, 403);

        $action->handle($postItem, $request->validated());

        return redirect()->route('dealer.demands.index', ['status' => $postItem->status->value])
            ->with('flash', ['type' => 'success', 'message' => 'Item updated.']);
    }

    public function fulfill(Request $request, PostItem $postItem, FulfillPostItemAction $action): RedirectResponse
    {
        $postItem->load('post');
        Gate::authorize('fulfill', $postItem);

        abort_if($postItem->post->type !== PostType::Demand, 403);

        $action->handle($postItem);

        return redirect()->route('dealer.demands.index', ['status' => 'archived'])
            ->with('flash', ['type' => 'success', 'message' => 'Item marked as fulfilled.']);
    }

    public function archive(Request $request, PostItem $postItem, ArchivePostItemAction $action): RedirectResponse
    {
        $postItem->load('post');
        Gate::authorize('archive', $postItem);

        abort_if($postItem->post->type !== PostType::Demand, 403);

        $action->handle($postItem);

        return redirect()->route('dealer.demands.index', ['status' => 'archived'])
            ->with('flash', ['type' => 'success', 'message' => 'Item archived.']);
    }

    public function destroy(Request $request, PostItem $postItem): RedirectResponse
    {
        $postItem->load('post');
        Gate::authorize('delete', $postItem);

        abort_if($postItem->post->type !== PostType::Demand, 403);

        $postItem->delete();

        return redirect()->route('dealer.demands.index', ['status' => 'ongoing'])
            ->with('flash', ['type' => 'success', 'message' => 'Item deleted.']);
    }
}
