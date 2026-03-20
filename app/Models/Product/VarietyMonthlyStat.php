<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable('variety_id', 'year', 'month', 'supply_archived_kg', 'supply_fulfilled_kg', 'demand_archived_kg', 'demand_fulfilled_kg')]
class VarietyMonthlyStat extends Model
{
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'supply_archived_kg' => 'decimal:2',
            'supply_fulfilled_kg' => 'decimal:2',
            'demand_archived_kg' => 'decimal:2',
            'demand_fulfilled_kg' => 'decimal:2',
        ];
    }

    /* ---------- relations ---------- */

    public function variety(): BelongsTo
    {
        return $this->belongsTo(Variety::class);
    }
}
