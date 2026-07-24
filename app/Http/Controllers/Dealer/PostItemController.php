<?php

namespace App\Http\Controllers\Dealer;

use App\Actions\PostItem\ExpirePostItemAction;
use App\Actions\PostItem\FulfillPostItemAction;
use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Models\Marketplace\PostItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PostItemController extends Controller
{
    public function fulfill(PostItem $postItem, FulfillPostItemAction $action): RedirectResponse
    {
        $postItem->load('post');
        Gate::authorize('fulfill', $postItem);
        abort_if($postItem->post->type !== PostType::Demand, 403);

        $action->handle($postItem);

        return back(fallback: route('dealer.demands.index'))
            ->with('flash', ['type' => 'success', 'message' => 'Item marked as fulfilled.']);
    }

    public function expire(PostItem $postItem, ExpirePostItemAction $action): RedirectResponse
    {
        $postItem->load('post');
        Gate::authorize('expire', $postItem);
        abort_if($postItem->post->type !== PostType::Demand, 403);

        $action->handle($postItem);

        return back(fallback: route('dealer.demands.index'))
            ->with('flash', ['type' => 'success', 'message' => 'Item marked as expired.']);
    }
}
