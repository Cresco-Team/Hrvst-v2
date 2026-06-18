<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['variety_id', 'price_min', 'price_max', 'recorded_at'])]
class PriceHistory extends Model
{
    use HasFactory;

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
