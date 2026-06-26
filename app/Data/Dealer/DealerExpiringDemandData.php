<?php

namespace App\Data\Dealer;

use App\Enums\PostItemStatus;
use App\Models\Marketplace\PostItem;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class DealerExpiringDemandData extends Data
{
    public function __construct(
        public int $id,
        public PostItemStatus $status,
        public int $variety_id,
        public string $variety_name,
        public string $vegetable_name,
        public float $quantity_kg,
        public ?string $scheduled_date,
        public ?string $time_slot,
        public ?string $time_slot_label,
        public ?int $days_until_transaction,
        public string $created_at,
        public string $created_at_human,
    ) {}

    public static function fromModel(PostItem $item): self
    {
        $post = $item->post;

        return new self(
            id: $item->id,
            status: $item->status,
            variety_id: $item->variety_id,
            variety_name: $item->variety_name,
            vegetable_name: $item->vegetable_name,
            quantity_kg: (float) $item->quantity_kg,
            scheduled_date: $post->scheduled_date?->format('M d, Y'),
            time_slot: $post->time_slot?->value,
            time_slot_label: $post->time_slot?->label(),
            days_until_transaction: $post->scheduled_date
                ? (int) now()->diffInDays($post->scheduled_date, false)
                : null,
            created_at: $item->created_at->format('M d, Y'),
            created_at_human: $item->created_at->diffForHumans(),
        );
    }
}