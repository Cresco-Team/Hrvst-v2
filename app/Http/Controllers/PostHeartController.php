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
        return response()->json($action->handle($request->user, $post));
    }
}
