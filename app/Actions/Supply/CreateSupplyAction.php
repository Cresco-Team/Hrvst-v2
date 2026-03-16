<?php

namespace App\Actions\Supply;

use App\Enums\PostPriceFlag;
use App\Enums\PostStatus;
use App\Models\Marketplace\FarmerSupply;
use App\Models\Product\Variety;
use App\Models\Profiles\FarmerProfile;
use Illuminate\Http\UploadedFile;

final class CreateSupplyAction
{
    public function __invoke(FarmerProfile $farmer, array $validated, ?UploadedFile $image = null): FarmerSupply
    {
        $variety = Variety::with('latestPrice')->findOrFail($validated['variety_id']);

        $supply = FarmerSupply::create([
            'farmer_id'       => $farmer->id,
            'expiration_date' => $validated['expiration_date'],
        ]);

        if ($image !== null) {
            $supply->addMedia($image)->toMediaCollection('supply_image');
        }

        $supply->post()->create([
            'user_id'       => $farmer->user_id,
            'variety_id'    => $validated['variety_id'],
            'quantity_kg'   => $validated['quantity_kg'],
            'offered_price' => $validated['offered_price'],
            'price_flag'    => PostPriceFlag::fromMarketPrice($validated['offered_price'], $variety->latestPrice),
            'status'        => PostStatus::Ongoing,
        ]);

        return $supply->load('post');
    }
}
