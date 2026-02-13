<?php

namespace App\Models\Marketplace;

use App\FarmerOfferingStatus;
use App\FarmerPriceFlag;
use App\Models\Marketplace\Post;
use App\Models\Profiles\FarmerProfile;
use App\Models\Product\Variety;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class FarmerOffering extends Model
{
    protected $fillable = [
        'farmer_id',
        'variety_id',
        'weight_kg',
        'asking_price',
        'expiration_date',
        'image_path',
        'status'
    ];

    protected $casts = [
        'expiration_date' => 'date',
        'price_flag' => FarmerPriceFlag::class,
        'status' => FarmerOfferingStatus::class,
    ];

    /* ---------- relationships ---------- */

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(FarmerProfile::class, 'farmer_id');
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('expiration_date', '>=', now());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'expired');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', 'archived');
    }

    /* ---------- actions ---------- */

    public function markAsExpired(): void
    {
        if ($this->status === 'active' && $this->isExpired()) {
            $this->update(['status' => 'expired']);
        }
    }

    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }

    /* ---------- predicates ---------- */

    public function isExpired(): bool
    {
        return $this->status === 'active' 
            && Carbon::parse($this->expiration_date)->isPast();
    }

    /* ---------- accessors ---------- */

    public function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path 
                ? asset('storage/' . $this->image_path)
                : null
        );
    }

    public function varietyName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->variety 
                ? "{$this->variety->vegetable->name} {$this->variety->name}"
                : 'Unknown'
        );
    }

    public function reactionCounts(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->reactions
                ->groupBy('reaction_type')
                ->map(fn ($group) => $group->count())
                ->toArray()
        );
    }

    public function daysUntilExpiration(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->status !== 'active') {
                    return null;
                }
                
                return Carbon::now()->diffInDays($this->expiration_date, false);
            }
        );
    }
}
