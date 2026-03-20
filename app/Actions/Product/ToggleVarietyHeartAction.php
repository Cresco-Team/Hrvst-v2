<?php

namespace App\Actions\Product;

use App\Models\Interaction\VarietyHeart;
use App\Models\Product\Variety;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ToggleVarietyHeartAction
{
    public function handle(User $user, Variety $variety): array
    {
        return DB::transaction(function () use ($user, $variety): array {
            $heart = VarietyHeart::where('user_id', $user->id)
                ->where('variety_id', $variety->id)
                ->lockForUpdate()
                ->first();

            if ($heart) {
                $heart->delete();
                $variety->decrement('hearts_count');

                return [
                    'hearted' => false,
                    'hearts_count' => max(0, $variety->hearts_count),
                ];
            }

            VarietyHeart::create([
                'user_id' => $user->id,
                'variety_id' => $variety->id,
            ]);

            $variety->increment('hearts_count');

            return [
                'hearted' => true,
                'hearts_count' => $variety->hearts_count,
            ];
        });
    }
}
