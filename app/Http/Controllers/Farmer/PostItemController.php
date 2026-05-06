<?php

namespace App\Http\Controllers\Farmer;

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
            $postItem->post->type !== PostType::Supply || $postItem->post->user_id !== $request->user()->id,
            403
        );

        $action->handle($postItem);

        return redirect()->route('farmer.supplies.index', ['status' => 'ongoing'])
            ->with('flash', ['type' => 'success', 'message' => 'Item marked as fulfilled.']);
    }

    public function archive(Request $request, PostItem $postItem, ArchivePostItemAction $action): RedirectResponse
    {
        abort_if(
            $postItem->post->type !== PostType::Supply || $postItem->post->user_id !== $request->user()->id,
            403
        );

        $action->handle($postItem);

        return redirect()->route('farmer.supplies.index', ['status' => 'ongoing'])
            ->with('flash', ['type' => 'success', 'message' => 'Item archived.']);
    }

    public function destroy(Request $request, PostItem $postItem): RedirectResponse
    {
        abort_if(
            $postItem->post->type !== PostType::Supply || $postItem->post->user_id !== $request->user()->id,
            403
        );

        $postItem->delete();

        return redirect()->route('farmer.supplies.index', ['status' => 'ongoing'])
            ->with('flash', ['type' => 'success', 'message' => 'Item deleted.']);
    }
}
