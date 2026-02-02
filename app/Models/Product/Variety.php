<?php

namespace App\Models\Product;

use App\Models\Profiles\FarmerProfile;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Variety extends Model
{
    use HasFactory;

    protected $fillable = [
        'vegetable_id',
        'name',
        'image_path',
        'weeks_to_harvest',
    ];

    /* ---------- relations ---------- */

    public function farmers(): BelongsToMany
    {
        return $this->belongsTo(FarmerProfile::class, 'plantings')
            ->using(Planting::class)
            ->withPivot(['weight_kg', 'date_planted', 'date_harvested'])
            ->withTimestamps();
    }

    public function plantings(): HasMany
    {
        return $this->hasMany(Planting::class);
    }

    public function vegetable(): BelongsTo
    {
        return $this->belongsTo(Vegetable::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(PriceHistory::class);
    }

    public function latestPrice(): HasOne
    {
        return $this->hasOne(PriceHistory::class)->latest('recorded_at');
    }

    /* ---------- accessors ---------- */

    public function averagePrice(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->price_min + $this->price_max) / 2,
        );
    }

    public function priceRange(): Attribute
    {
        return Attribute::make(
            get: fn () => "₱{$this->price_min} - ₱{$this->price_max}",
        );
    }
}
