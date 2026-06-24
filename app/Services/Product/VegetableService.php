<?php

namespace App\Services\Product;

use App\Models\Product\Vegetable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class VegetableService
{
    public function paginated(
        ?string $categoryId = null,
        ?string $search = null,
        ?int $userId = null,
    ): Builder {
        return Vegetable::with([
            'category',
            'varieties' => function (HasMany $varieties): void {
                $varieties
                    ->withCount([
                        'postItems as supply_count' => fn (Builder $q) => $q->ongoing()->whereHas(
                            'post', fn (Builder $p) => $p->supply()
                        ),
                        'postItems as demand_count' => fn (Builder $q) => $q->ongoing()->whereHas(
                            'post', fn (Builder $p) => $p->demand()
                        ),
                    ])
                    ->orderBy('name');
            },
        ])
            ->when($categoryId, fn (Builder $q) => $q->where('category_id', $categoryId))
            ->search($search)
            ->orderBy('name');
    }

    public function summary(): array
    {
        return [
            'total_vegetables' => Vegetable::count(),
            'total_varieties' => DB::table('varieties')->count(),
        ];
    }
}
