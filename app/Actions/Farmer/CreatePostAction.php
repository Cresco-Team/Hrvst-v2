<?php

namespace App\Actions\Farmer;

use App\Enums\PostStatus;
use App\Models\Marketplace\FarmerOffering;
use App\Models\Profiles\FarmerProfile;

final class CreatePostAction
{
    public function execute(FarmerProfile $farmer, array $data): FarmerOffering
    {
        // Create the offering first — Post needs postable_id, so offering must exist first
        $offering = FarmerOffering::create([
            'farmer_id'       => $farmer->id,
            'expiration_date' => $data['expiration_date'] ?? null,
            'image_path'      => $data['image_path'] ?? null,
        ]);

        $offering->post()->create([
            'user_id'       => $farmer->user_id,
            'variety_id'    => $data['variety_id'],
            'title'         => $data['title'],
            'quantity_kg'   => $data['quantity_kg'],
            'offered_price' => $data['offered_price'],
            'price_flag'    => $data['price_flag'],
            'status'        => PostStatus::Ongoing,
        ]);

        return $offering->load('post');
    }
}
