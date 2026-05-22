<?php

namespace App\Actions\Post;

use App\Models\Interaction\PostHeart;
use App\Models\Marketplace\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class TogglePostHeartAction
{
    public function handle(User $user, Post $post): array
    {
        return DB::transaction(function () use ($user, $post): array {
            $heart = PostHeart::where('user_id', $user->id)
                ->where('post_id', $post->id)
                ->lockForUpdate()
                ->first();

            if ($heart) {
                $heart->delete();
                DB::table('posts')
                    ->where('id', $post->id)
                    ->update(['hearts_count' => DB::raw('CASE WHEN hearts_count > 0 THEN hearts_count - 1 ELSE 0 END')]);
                $post->refresh();

                return [
                    'hearted' => false,
                    'hearts_count' => $post->hearts_count,
                ];
            }

            PostHeart::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
            ]);

            $post->increment('hearts_count');

            return [
                'hearted' => true,
                'hearts_count' => $post->hearts_count,
            ];
        });
    }
}
