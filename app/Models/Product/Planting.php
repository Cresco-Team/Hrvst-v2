<?php

namespace App\Models\Product;

use App\Models\Messaging\Conversation;
use App\Models\Profiles\FarmerProfile;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Planting extends Model
{
    protected $fillable = [
        'farmer_id',
        'variety_id',
        'weight_kg',
        'date_planted',
        'expected_harvest_date',
        'date_harvested',
        'status'
    ];

    protected $casts = [
        'date_planted' => 'date',
        'expected_harvest_date'=> 'date',
        'date_harvested' => 'date',
    ];

    /* ---------- relations ---------- */

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(FarmerProfile::class, 'farmer_id');
    }

    public function variety(): BelongsTo
    {
        return $this->belongsTo(Variety::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /* ---------- predicate ---------- */

    public function isExpired(): bool
    {
        if ($this->status !== 'active' || !$this->expected_harvest_date) {
            return false;
        }
        return Carbon::now()->isAfter($this->expected_harvest_date);
    }

    /* ---------- actions ---------- */

    public function markAsHarvested(?float $actualWeight = null): void
    {
        $this->update([
            'status' => 'harvested',
            'date_harvested' => Carbon::now(),
            'weight_kg' => $actualWeight ?? $this->weight_kg,
        ]);
    }

    public function markAsExpired(): void
    {
        if ($this->status === 'active' && $this->isExpired()) {
            $this->update(['status' => 'expired']);
        }
    }

    public function markAsCancelled(): void
    {
        if ($this->status === 'active') {
            $this->update(['status' => 'cancelled']);
        }
    }

    /* ---------- scopes ---------- */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeHarvested(Builder $query): Builder
    {
        return $query->where('status', 'harvested');
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'expired');
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeHarvestingSoon(Builder $query, int $days = 7): Builder
    {
        return $query->active()
            ->whereNotNull('expected_harvest_date')
            ->whereBetween('expected_harvest_date', [now(), now()->addDays($days)]);
    }

    /* ---------- accessors ---------- */

    public function daysUnillHarvest(): Attribute
     {
        return Attribute::make(
            get: function (mixed $value, array $attributes): ?int {
                if (!$attributes['expected_harvest_date'] || $attributes['status'] !== 'active') {
                    return null;
                }
                return Carbon::now()->diffInDays($attributes['expected_harvest_date'], false);
            }
        );
     }

     public function statusBadge(): Attribute
     {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => match($attributes['status']) {
                'active'    => $this->isExpired() ? 'Overdue' : 'Growing',
                'harvested' => 'Harvested',
                'expired'   => 'Expired',
                'cancelled' => 'Cancelled',
                default     => 'Unknown',
            }
        );
     }
}
