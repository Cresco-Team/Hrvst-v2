<?php

namespace App\Http\Controllers\Dealer;

use App\Actions\PostItem\ArchivePostItemAction;
use App\Actions\PostItem\FulfillPostItemAction;
use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Models\Marketplace\PostItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PostItemController extends Controller
{
    public function fulfill(Request $request, PostItem $postItem, FulfillPostItemAction $action): RedirectResponse
    {
        abort_if(
            $postItem->post->type !== PostType::Demand || $postItem->post->user_id !== $request->user()->id,
            403
        );

        $action->handle($postItem);

        return redirect()->route('dealer.demands.index', ['status' => 'ongoing'])
            ->with('flash', ['type' => 'success', 'message' => 'Item marked as fulfilled.']);
    }

    public function archive(Request $request, PostItem $postItem, ArchivePostItemAction $action): RedirectResponse
    {
        abort_if(
            $postItem->post->type !== PostType::Demand || $postItem->post->user_id !== $request->user()->id,
            403
        );

        $action->handle($postItem);

        return redirect()->route('dealer.demands.index', ['status' => 'ongoing'])
            ->with('flash', ['type' => 'success', 'message' => 'Item archived.']);
    }

    public function destroy(Request $request, PostItem $postItem): RedirectResponse
    {
        abort_if(
            $postItem->post->type !== PostType::Demand || $postItem->post->user_id !== $request->user()->id,
            403
        );

        $postItem->delete();

        return redirect()->route('dealer.demands.index', ['status' => 'ongoing'])
            ->with('flash', ['type' => 'success', 'message' => 'Item deleted.']);
    }
}
