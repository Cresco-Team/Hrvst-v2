<?php

namespace App\Services\Product;

use App\Models\Product\Vegetable;
use Illuminate\Database\Eloquent\Builder;

class VegetableService
{
    public function paginated(
        ?string $categoryId = null,
        ?string $search = null,
    ): Builder {
        return Vegetable::query()
            ->with('category')
            ->withCount([
                'postItems as supply_count' => fn (Builder $q) => $q->ongoing()->whereHas('post', fn (Builder $p) => $p->supply()),
                'postItems as demand_count' => fn (Builder $q) => $q->ongoing()->whereHas('post', fn (Builder $p) => $p->demand()),
            ])
            ->when($categoryId, fn (Builder $q) => $q->where('category_id', $categoryId))
            ->search($search)
            ->orderBy('vegetable_name')
            ->orderByRaw('variety_name IS NULL, variety_name');
    }

    public function summary(): array
    {
        return [
            'total_vegetables' => Vegetable::count(),
        ];
    }
}
