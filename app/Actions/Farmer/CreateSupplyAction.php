<?php

namespace App\Actions\Farmer;

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
        $variety = Variety::with('latestPrice')->findOrFail(['variety_id']);
        $imagePath = $image?->store('supply', 'public');
        $supply = FarmerSupply::create([
            'farmer_id'       => $farmer->id,
            'expiration_date' => $validated['expiration_date'],
            'image_path'      => $validated['image_path'] ?? null,
        ]);

        $supply->post()->create([
            'user_id'       => $farmer->user_id,
            'variety_id'    => $validated['variety_id'],
            'title'         => $validated['title'],
            'quantity_kg'   => $validated['quantity_kg'],
            'offered_price' => $validated['offered_price'],
            'price_flag'    => PostPriceFlag::fromMarketPrice($validated['offered_price'], $variety->latestPrice),
            'status'        => PostStatus::Ongoing,
        ]);

        return $supply->load('post');
    }
}
