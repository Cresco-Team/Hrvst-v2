<?php

namespace App\Models\Product;

use App\Models\Marketplace\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vegetable extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
    ];

    /* ---------- relations ---------- */

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function varieties(): HasMany
    {
        return $this->hasMany(Variety::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /* ---------- scopes ---------- */

    public function scopeSearch(Builder $query, ?string $search): void
    {
        $query->when($search, fn (Builder $q) => $q->where('name', 'ilike', "%{$search}%"));
    }
}
