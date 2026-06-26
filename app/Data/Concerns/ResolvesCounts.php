<?php

namespace App\Data\Concerns;

use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Optional;

trait ResolvesCounts
{
    protected static function resolveCount(Model $model, string $key): int|Optional
    {
        return array_key_exists($key, $model->getAttributes())
            ? (int) $model->getAttribute($key)
            : Optional::create();
    }
}
