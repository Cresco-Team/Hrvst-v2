<?php

namespace App\Data\PostItem;

use App\Enums\PostItemStatus;
use App\Models\Schedule\PostItem;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PostItemData extends Data
{
    public function __construct(
        public int $id,
        public string $image_url,
        public int $post_id,
        public PostItemStatus $status,
        public int $vegetable_id,
        public string $display_name,
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
        $vegetable = $item->vegetable;

        return new self(
            id: $item->id,
            image_url: $vegetable->getFirstMediaUrl('vegetable_image'),
            post_id: $post->id,
            status: $item->status,
            vegetable_id: $item->vegetable_id,
            display_name: $vegetable->display_name,
            quantity_kg: (float) $item->quantity_kg,
            scheduled_date: $post->scheduled_date?->format('M d, Y'),
            time_slot: $post->time_slot?->value,
            time_slot_label: $post->time_slot?->label(),
            days_until_transaction: $post->scheduled_date
                ? (int) now()->diffInDays($post->scheduled_date, false)
                : null,
            created_at: $post->created_at->format('M d, Y'),
            created_at_human: $post->created_at->diffForHumans(),
        );
    }
}
