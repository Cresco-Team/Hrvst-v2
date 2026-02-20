<?php

namespace App\Models\Marketplace;

use App\Enums\DealerDemandStatus;
use App\DealerPriceFlag;
use App\Models\Marketplace\Post;
use App\Models\Profiles\DealerProfile;
use App\Models\Product\Variety;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class DealerDemand extends Model
{
    protected $fillable = [
        'dealer_id',
        'variety_id',
        'quantity_kg',
        'price_offered',
        'price_flag',
        'status',
        'transaction_date',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'price_flag' => DealerPriceFlag::class,
        'status' => DealerDemandStatus::class,
    ];

    /* ---------- relationships ---------- */

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(DealerProfile::class, 'dealer_id');
    }

    public function variety(): BelongsTo
    {
        return $this->belongsTo(Variety::class);
    }

    public function post(): MorphOne
    {
        return $this->morphOne(Post::class, 'postable');
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
        return $query->where('status', 'expired')
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

    public function reactionCounts(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'thumbs_up' => $this->reactions->where('reaction_type', 'thumbs_up')->count(),
                'thumbs_down' => $this->reactions->where('reaction_type', 'thumbs_down')->count(),
            ]
        );
    }

    public function daysUntilTransaction(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->status !== DealerDemandStatus::Open) {
                    return null;
                }

                return (int) now()->diffInDays($this->transaction_date, false);
            }
        );
    }
}
