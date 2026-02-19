<?php

namespace App\Notifications\Announcement;

use App\Models\Marketplace\FarmerOffering;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MatchingOfferingAvailable extends Notification
{
    use Queueable;

    public function __construct(
        public FarmerOffering $offering
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'matching_offering',
            'offering_id' => $this->offering->id,
            'farmer_id' => $this->offering->farmer_id,
            'farmer_name' => $this->offering->farmer->user->name,
            'variety_name' => $this->offering->variety->vegetable->name . ' ' . $this->offering->variety->name,
            'variety_id' => $this->offering->variety_id,
            'weight_kg' => (float) $this->offering->weight_kg,
            'asking_price' => (float) $this->offering->asking_price,
            'expiration_date' => $this->offering->expiration_date->toDateString(),
            'days_until_expiration' => $this->offering->days_until_expiration,
            'image_url' => $this->offering->image_url,
            'message' => "New {$this->offering->variety->vegetable->name} offering available: {$this->offering->quantity_kg}kg at ₱{$this->offering->price_asking}/kg",
        ];
    }
}
