<?php

namespace App\Services;

use App\Models\Product\Category;
use App\Models\Product\Vegetable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class VegetableService
{
    /**
     * Paginated list of vegetables with category eager-loaded,
     * plus variety count for each row.
     */
    public static function paginated(int $perPage = 20): LengthAwarePaginator
    {
        return Vegetable::with('category')
            ->withCount('varieties')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Summary stats for the dashboard cards at the top of the page.
     */
    public static function summary(): array
    {
        return [
            'total_vegetables' => Vegetable::count(),
            'total_categories' => Category::count(),
            'total_varieties'  => \App\Models\Product\Variety::count(),
            'categories'       => Category::withCount('vegetables')->get(),
        ];
    }

    /**
     * All categories keyed by id — used to populate the form <select>.
     */
    public static function categoryOptions(): array
    {
        return Category::pluck('name', 'id')->all();
    }

    /** Create. */
    public static function create(array $validated): Vegetable
    {
        return Vegetable::create($validated);
    }

    /** Update. */
    public static function update(Vegetable $vegetable, array $validated): Vegetable
    {
        $vegetable->update($validated);

        return $vegetable;
    }

    /** Soft-guard: refuse to delete if it still has varieties. */
    public static function delete(Vegetable $vegetable): bool
    {
        if ($vegetable->varieties()->exists()) {
            return false;   // caller must handle the error
        }

        $vegetable->delete();

        return true;
    }
}
