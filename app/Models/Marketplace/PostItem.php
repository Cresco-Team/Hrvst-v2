<?php

namespace App\Models\Marketplace;

use App\Enums\PostItemStatus;
use App\Enums\PostPriceFlag;
use App\Models\Product\Variety;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'post_id',
        'variety_id',
        'quantity_kg',
        'unit_price',
        'price_flag',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity_kg' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'price_flag' => PostPriceFlag::class,
            'status' => PostItemStatus::class,
        ];
    }

    /* ---------- relationships ---------- */

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function variety(): BelongsTo
    {
        return $this->belongsTo(Variety::class);
    }

    /* ---------- scopes ---------- */

    public function scopeOngoing(Builder $query): Builder
    {
        return $query->where('status', PostItemStatus::Ongoing);
    }

    public function scopeFulfilled(Builder $query): Builder
    {
        return $query->where('status', PostItemStatus::Fulfilled);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', PostItemStatus::Archived);
    }

    public function scopeOfStatus(Builder $query, PostItemStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /* ---------- lifecycle ---------- */

    public function markAsFulfilled(): void
    {
        $this->status = PostItemStatus::Fulfilled;
        $this->save();
    }

    public function markAsArchived(): void
    {
        $this->status = PostItemStatus::Archived;
        $this->save();
    }
}
