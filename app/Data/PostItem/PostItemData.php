<?php

namespace App\Data\PostItem;

use App\Data\Variety\VarietyData;
use App\Enums\PostItemStatus;
use App\Models\Marketplace\PostItem;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PostItemData extends Data
{
    public function __construct(
        public int $id,
        public string $image_url,
        public string $name,
        public int $post_id,
        public PostItemStatus $status,
        public int $vegetable_id,
        public string $variety_name,
        public string $vegetable_name,
        public string $category_name,
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
            name: $vegetable->variety_name ? "{$vegetable->vegetable_name} {$vegetable->variety_name}" : $vegetable->vegetable_name,
            post_id: $post->id,
            status: $item->status,
            vegetable_id: $item->vegetable_id,
            variety_name: $vegetable->variety_name ?? '',
            vegetable_name: $vegetable->vegetable_name,
            category_name: $vegetable->category->name,
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