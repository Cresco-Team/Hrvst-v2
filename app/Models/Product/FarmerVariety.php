<?php

namespace App\Models\Product;

use App\Models\Profiles\FarmerProfile;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class FarmerVariety extends Pivot
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
        return $this->belongsTo(FarmerProfile::class);
    }

    public function variety(): BelongsTo
    {
        return $this->belongsTo(Variety::class);
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
                default     => 'Unknown',
            }
        );
     }
}
