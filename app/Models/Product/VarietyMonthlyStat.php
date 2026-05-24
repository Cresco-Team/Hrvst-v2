<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable('variety_id', 'period_date', 'supply_unsettled_kg', 'supply_fulfilled_kg', 'demand_unsettled_kg', 'demand_fulfilled_kg')]
class VarietyMonthlyStat extends Model
{
    protected function casts(): array
    {
        return [
            'period_date' => 'date',
            'supply_unsettled_kg' => 'decimal:2',
            'supply_fulfilled_kg' => 'decimal:2',
            'demand_unsettled_kg' => 'decimal:2',
            'demand_fulfilled_kg' => 'decimal:2',
        ];
    }

    /* ---------- relations ---------- */

    public function variety(): BelongsTo
    {
        return $this->belongsTo(Variety::class);
    }
}
