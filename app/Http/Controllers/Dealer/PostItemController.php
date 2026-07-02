<?php

namespace App\Http\Controllers\Dealer;

use App\Actions\PostItem\FulfillPostItemAction;
use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Models\Marketplace\PostItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PostItemController extends Controller
{
    public function fulfill(Request $request, PostItem $postItem, FulfillPostItemAction $action): RedirectResponse
    {
        $postItem->load('post');
        Gate::authorize('fulfill', $postItem);

        abort_if($postItem->post->type !== PostType::Demand, 403);

        $action->handle($postItem);

        return redirect()->route('dealer.demands.index', ['status' => 'ongoing'])
            ->with('flash', ['type' => 'success', 'message' => 'Item marked as fulfilled.']);
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
