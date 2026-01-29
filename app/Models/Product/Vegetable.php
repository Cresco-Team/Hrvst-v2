<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vegetable extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
    ];

    /* ---------- relations ---------- */

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function varieties(): HasMany
    {
        return $this->hasMany(Variety::class);
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
