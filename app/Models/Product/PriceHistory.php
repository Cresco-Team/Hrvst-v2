<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'variety_id',
        'price_min',
        'price_max',
        'recorded_at',
    ];

    protected $casts = [
        'price_min' => 'decimal:2',
        'price_max' => 'decimal:2',
        'recorded_at' => 'date',
    ];

    protected $touches = ['variety'];

    /* ---------- relations ---------- */

    public function variety()
    {
        return $this->belongsTo(Variety::class);
    }
}
