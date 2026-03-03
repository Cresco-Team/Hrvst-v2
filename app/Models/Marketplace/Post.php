<?php

namespace App\Models\Marketplace;

use App\Enums\PostPriceFlag;
use App\Enums\PostStatus;
use App\Models\Interaction\Comment;
use App\Models\Interaction\PostFlag;
use App\Models\Interaction\Reaction;
use App\Models\Product\Variety;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Post extends Model
{
    protected $fillable = [
        'user_id',
        'variety_id',
        'postable_id',
        'postable_type',
        'title',
        'quantity_kg',
        'offered_price',
        'price_flag',
    ];

    protected function casts(): array
    {
        return [
            'quantity_kg' => 'int',
            'offered_price' => 'decimal:2',
            'price_flag' => PostPriceFlag::class,
            'status' => PostStatus::class,
        ];
    }

    /* ---------- relationships ---------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function variety(): BelongsTo
    {
        return $this->belongsTo(Variety::class);
    }

    public function postable(): MorphTo
    {   // Magic link to FarmerSupply and DealerDemand
        return $this->morphTo();
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function flags(): HasMany
    {
        return $this->hasMany(PostFlag::class);
    }

    /* ---------- scopes ---------- */

    public function scopeOngoing(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Ongoing);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Archived);
    }

    public function scopeFulfilled(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Fulfilled);
    }

    /* ---------- actions ---------- */

    public function markAsArchived(): void
    {
        $this->status = PostStatus::Archived;
        $this->save();
    }

    public function markAsFulfilled(): void
    {
        $this->status = PostStatus::Fulfilled;
        $this->save();
    }

    /* ---------- accessors ---------- */

    public function daysUntilArchive(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->status !== PostStatus::Ongoing) {
                    return null;
                }
            }
        );
    }

    /* public function reactionCounts(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'thumbs_up' => $this->reactions->where('reaction_type', 'thumbs_up')->count(),
                'thumbs_down' => $this->reactions->where('reaction_type', 'thumbs_down')->count(),
            ]
        );
    } */
}
