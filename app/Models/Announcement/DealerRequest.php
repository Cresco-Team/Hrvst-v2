<?php

namespace App\Models\Announcement;

use App\Models\Profiles\DealerProfile;
use App\Models\Product\Variety;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DealerRequest extends Model
{
    protected $fillable = [
        'dealer_id',
        'transaction_date',
        'status',
    ];

    protected $casts = [
        'transaction_date' => 'date',
    ];

    /* ---------- relationships ---------- */

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(DealerProfile::class, 'dealer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DealerRequestItem::class);
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(AnnouncementReaction::class, 'reactionable');
    }

    public function flags(): MorphMany
    {
        return $this->morphMany(AnnouncementFlag::class, 'flaggable');
    }

    /* ---------- scopes ---------- */

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeFulfilled(Builder $query): Builder
    {
        return $query->where('status', 'fulfilled');
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'expired');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'open')
            ->where('transaction_date', '>=', now());
    }

    /* ---------- actions ---------- */

    public function markAsFulfilled(): void
    {
        $this->update(['status' => 'fulfilled']);
    }

    public function markAsExpired(): void
    {
        if ($this->status === 'open' && $this->isExpired()) {
            $this->update(['status' => 'expired']);
        }
    }

    /* ---------- predicates ---------- */

    public function isExpired(): bool
    {
        return $this->status === 'open' 
            && Carbon::parse($this->transaction_date)->isPast();
    }

    /* ---------- accessors ---------- */

    public function totalQuantity(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->items->sum('quantity_kg')
        );
    }

    public function reactionCounts(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'thumbs_up' => $this->reactions->where('reaction_type', 'thumbs_up')->count(),
                'thumbs_down' => $this->reactions->where('reaction_type', 'thumbs_down')->count(),
            ]
        );
    }
}
