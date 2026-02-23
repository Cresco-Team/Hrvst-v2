<?php

namespace App\Actions\Dealer;

use App\Enums\PostStatus;
use App\Models\Marketplace\DealerDemand;
use App\Models\Profiles\DealerProfile;

final class CreatePostAction
{
    public function execute(DealerProfile $dealer, array $data): DealerDemand
    {
        $demand = DealerDemand::create([
            'dealer_id'        => $dealer->id,
            'transaction_date' => $data['transaction_date'],
        ]);

        $demand->post()->create([
            'user_id'       => $dealer->user_id,
            'variety_id'    => $data['variety_id'],
            'title'         => $data['title'],
            'quantity_kg'   => $data['quantity_kg'],
            'offered_price' => $data['offered_price'],
            'price_flag'    => $data['price_flag'],
            'status'        => PostStatus::Ongoing,
        ]);

        return $demand->load('post');
    }
}