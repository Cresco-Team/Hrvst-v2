<?php

namespace App\Actions\Supply;

use App\Enums\PostPriceFlag;
use App\Models\Marketplace\Post;
use App\Models\Product\Variety;
use Illuminate\Http\UploadedFile;

final class UpdateSupplyAction
{
    public function handle(Post $post, array $validated, ?UploadedFile $image = null): Post
    {
        $fields = array_intersect_key($validated, array_flip([
            'variety_id', 'quantity_kg', 'offered_price', 'scheduled_date',
        ]));

        if (isset($fields['offered_price'])) {
            $variety = Variety::with('latestPrice')
                ->findOrFail($fields['variety_id'] ?? $post->variety_id);
            $fields['price_flag'] = PostPriceFlag::fromMarketPrice($fields['offered_price'], $variety->latestPrice);
        }

        $post->update($fields);

        if ($image !== null) {
            // Spatie replaces the existing file in a singleFile() collection automatically.
            $post->addMedia($image)->toMediaCollection('post_image');
        }

        return $post->fresh(['variety', 'media']);
    }
}
