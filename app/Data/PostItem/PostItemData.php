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
        public string $image_url, // vegetable image — see naming-collision note up top
        public string $name,
        public int $post_id,
        public PostItemStatus $status,
        public VarietyData|Lazy $variety,
        public int $variety_id,
        public string $variety_name,
        public int $vegetable_id,
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
        $variety = $item->variety;
        $vegetable = $variety->vegetable;

        return new self(
            id: $item->id,
            image_url: $vegetable->getFirstMediaUrl('vegetable_image'),
            name: $vegetable->name.' '.$variety->name,
            post_id: $post->id,
            status: $item->status,
            variety: Lazy::whenLoaded('variety', $item, fn () => VarietyData::fromModel($variety)),
            variety_id: $item->variety_id,
            variety_name: $variety->name,
            vegetable_id: $vegetable->id,
            vegetable_name: $vegetable->name,
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