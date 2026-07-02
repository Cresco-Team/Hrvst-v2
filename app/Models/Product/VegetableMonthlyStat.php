<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable('vegetable_id', 'period_date', 'supply_expired_kg', 'supply_fulfilled_kg', 'demand_expired_kg', 'demand_fulfilled_kg')]
class VegetableMonthlyStat extends Model
{
    protected function casts(): array
    {
        return [
            'period_date' => 'date',
            'supply_expired_kg' => 'decimal:2',
            'supply_fulfilled_kg' => 'decimal:2',
            'demand_expired_kg' => 'decimal:2',
            'demand_fulfilled_kg' => 'decimal:2',
        ];
    }

    public function vegetable(): BelongsTo
    {
        return $this->belongsTo(Vegetable::class);
    }
}
