<?php

namespace App\Actions\Product;

use App\Models\Product\Vegetable;
use Illuminate\Http\UploadedFile;

class UpdateVegetableAction
{
    public function handle(Vegetable $vegetable, array $validated, ?UploadedFile $image = null): Vegetable
    {
        $vegetable->update($validated);

        if ($image) {
            $vegetable->addMedia($image)->toMediaCollection('vegetable_image');
        }

        return $vegetable;
    }
}
