<?php

namespace App\Http\Controllers;

use App\Actions\Post\TogglePostHeartAction;
use App\Models\Marketplace\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostHeartController extends Controller
{
    public function toggle(Request $request, Post $post, TogglePostHeartAction $action): JsonResponse
    {
        $user = $request->user();

        $canHeart = $user->farmerProfile?->is_approved === true
            || $user->dealerProfile?->is_approved === true;

        if (! $canHeart) {
            abort(403, 'Only approved farmers and dealers can react to posts.');
        }

        return response()->json($action->handle($user, $post));
    }
}
