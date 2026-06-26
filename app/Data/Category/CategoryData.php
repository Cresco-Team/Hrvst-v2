<?php

namespace App\Data\Category;

use App\Models\Product\Category;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CategoryData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
    ) {}

    public static function fromModel(Category $category): self
    {
        return new self($category->id, $category->name, $category->slug);
    }
}
