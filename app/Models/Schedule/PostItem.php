<?php

namespace App\Models\Schedule;

use App\Enums\PostItemStatus;
use App\Models\Vegetable\Vegetable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['post_id', 'vegetable_id', 'quantity_kg', 'status'])]
class PostItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'quantity_kg' => 'decimal:2',
            'status' => PostItemStatus::class,
        ];
    }

    /* ---------- relationships ---------- */

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function vegetable(): BelongsTo
    {
        return $this->belongsTo(Vegetable::class);
    }

    /* ---------- scopes ---------- */

    public function scopeOngoing(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), PostItemStatus::Ongoing);
    }

    public function scopeFulfilled(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), PostItemStatus::Fulfilled);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), PostItemStatus::Expired);
    }

    public function scopeOfStatus(Builder $query, PostItemStatus $status): Builder
    {
        return $query->where($this->qualifyColumn('status'), $status);
    }

    /* ---------- accessors ---------- */

    public function vegetableName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->relationLoaded('vegetable')
                ? $this->vegetable?->vegetable_name
                : null
        );
    }

    public function varietyName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->relationLoaded('vegetable')
                ? $this->vegetable?->variety_name
                : null
        );
    }

    public function localName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->relationLoaded('vegetable')
                ? $this->vegetable?->local_name
                : null
        );
    }

    public function displayName(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->relationLoaded('vegetable')) {
                    return null;
                }

                $local = $this->local_name ? " ({$this->local_name})" : '';

                return $this->variety_name
                    ? "{$this->vegetable_name}: {$this->variety_name}{$local}"
                    : "{$this->vegetable_name}{$local}";
            }
        );
    }

    public function vegetableImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->relationLoaded('vegetable')
                ? $this->vegetable?->imageUrl
                : null
        );
    }

    /* ---------- lifecycle ---------- */

    public function markAsFulfilled(): void
    {
        $this->status = PostItemStatus::Fulfilled;
        $this->save();
    }

    public function markAsExpired(): void
    {
        if ($this->status === PostItemStatus::Expired) {
            return;
        }

        $this->status = PostItemStatus::Expired;
        $this->save();
    }
}
