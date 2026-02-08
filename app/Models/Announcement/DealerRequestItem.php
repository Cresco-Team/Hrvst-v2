<?php

namespace App\Models\Announcement;

use App\Models\Product\Variety;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealerRequestItem extends Model
{
    protected $fillable = [
        'dealer_request_id',
        'variety_id',
        'quantity_kg',
        'price_offered',
        'price_flag',
    ];

    protected $casts = [
        'quantity_kg' => 'decimal:2',
        'price_offered' => 'decimal:2',
    ];

    /* ---------- relationships ---------- */

    public function dealerRequest(): BelongsTo
    {
        return $this->belongsTo(DealerRequest::class);
    }

    public function variety(): BelongsTo
    {
        return $this->belongsTo(Variety::class);
    }

    /* ---------- accessors ---------- */

    public function varietyName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->variety 
                ? "{$this->variety->vegetable->name} {$this->variety->name}"
                : 'Unknown'
        );
    }

    public function priceComparison(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->variety || !$this->variety->latestPrice) {
                    return 'unknown';
                }

                $marketMin = $this->variety->latestPrice->price_min;
                $marketMax = $this->variety->latestPrice->price_max;

                if ($this->price_offered < $marketMin) {
                    return 'cheap';
                } elseif ($this->price_offered > $marketMax) {
                    return 'high';
                } else {
                    return 'fair';
                }
            }
        );
    }
}
