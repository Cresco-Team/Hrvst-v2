<?php

namespace App\Models\Marketplace;

use App\Enums\PostPriceFlag;
use App\Models\Product\Variety;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'post_id',
        'variety_id',
        'quantity_kg',
        'unit_price',
        'price_flag',
    ];

    protected function casts(): array
    {
        return [
            'quantity_kg' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'price_flag' => PostPriceFlag::class,
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function variety(): BelongsTo
    {
        return $this->belongsTo(Variety::class);
    }
}
