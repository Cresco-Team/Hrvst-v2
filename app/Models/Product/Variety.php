<?php

namespace App\Models\Product;

use App\Models\Marketplace\DealerRequest;
use App\Models\Marketplace\FarmerOffering;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    protected $appends = ['image_url'];

    /* ---------- relations ---------- */

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

    public function offerings(): HasMany
    {
        return $this->hasMany(FarmerOffering::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(DealerRequest::class);
    }

    /* ---------- accessors ---------- */

    public function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path 
                ? asset('storage/' . $this->image_path)
                : null,
        );
    }

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
