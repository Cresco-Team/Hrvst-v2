<?php

namespace App\Actions\Supply;

use App\Enums\PostPriceFlag;
use App\Enums\PostStatus;
use App\Models\Marketplace\FarmerSupply;
use App\Models\Product\Variety;
use Illuminate\Http\UploadedFile;

final class UpdateSupplyAction
{
    public function __invoke(FarmerSupply $supply, array $validated, ?UploadedFile $image = null): FarmerSupply
    {
        $post = $supply->post;

        if ($post->status !== PostStatus::Ongoing) {
            throw new \LogicException('Only ongoing supplies can be updated.');
        }

        $supplyFields = array_intersect_key($validated, array_flip(['expiration_date']));

        if ($image !== null) {
            // singleFile() on the collection ensures the old media is replaced automatically.
            // The previous Storage::delete($supply->image_path) with no null-guard is gone.
            $supply->addMedia($image)->toMediaCollection('supply_image');
        }

        $postFields = array_intersect_key($validated, array_flip([
            'title', 'variety_id', 'quantity_kg', 'offered_price',
        ]));

        if (isset($postFields['offered_price'])) {
            $variety = Variety::with('latestPrice')->findOrFail($postFields['variety_id'] ?? $post->variety_id);
            $postFields['price_flag'] = PostPriceFlag::fromMarketPrice($postFields['offered_price'], $variety->latestPrice);
        }

        if (! empty($supplyFields)) {
            $supply->update($supplyFields);
        }

        if (! empty($postFields)) {
            $post->update($postFields);
        }

        return $supply->fresh('post');
    }
}
