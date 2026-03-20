<?php

namespace App\Actions\Supply;

use App\Enums\PostPriceFlag;
use App\Enums\PostType;
use App\Models\Marketplace\Post;
use App\Models\Product\Variety;
use App\Models\Profiles\FarmerProfile;
use Illuminate\Http\UploadedFile;

final class CreateSupplyAction
{
    public function handle(FarmerProfile $farmer, array $validated, ?UploadedFile $image = null): Post
    {
        $variety = Variety::with('latestPrice')->findOrFail($validated['variety_id']);

        /** @var Post $post */
        $post = Post::create([
            'user_id' => $farmer->user_id,
            'variety_id' => $validated['variety_id'],
            'type' => PostType::Supply,
            'quantity_kg' => $validated['quantity_kg'],
            'offered_price' => $validated['offered_price'],
            'price_flag' => PostPriceFlag::fromMarketPrice($validated['offered_price'], $variety->latestPrice),
            'scheduled_date' => $validated['scheduled_date'],
            'time_slot' => $validated['time_slot'],
        ]);

        if ($image !== null) {
            $post->addMedia($image)->toMediaCollection('post_image');
        }

        return $post->load('variety');
    }
}
