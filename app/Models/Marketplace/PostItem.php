<?php

namespace App\Models\Marketplace;

use App\Enums\PostItemStatus;
use App\Models\Product\Variety;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['post_id', 'variety_id', 'quantity_kg', 'status'])]
#[SoftDeletes()]
class PostItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'variety_id',
        'quantity_kg',
        'status',
    ];

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

    public function variety(): BelongsTo
    {
        return $this->belongsTo(Variety::class);
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

    public function scopeUnsettled(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), PostItemStatus::Unsettled);
    }

    public function scopeOfStatus(Builder $query, PostItemStatus $status): Builder
    {
        return $query->where($this->qualifyColumn('status'), $status);
    }

    /* ---------- lifecycle ---------- */

    public function markAsFulfilled(): void
    {
        $this->status = PostItemStatus::Fulfilled;
        $this->save();
    }

    public function markAsUnsettled(): void
    {
        $this->status = PostItemStatus::Unsettled;
        $this->save();
    }
}
