<?php

namespace App\Actions\Product;

use App\Models\Vegetable\Vegetable;
use Illuminate\Http\UploadedFile;

class CreateVegetableAction
{
    public function handle(array $validated, ?UploadedFile $image = null): Vegetable
    {
        $vegetable = Vegetable::create($validated);

        if ($image) {
            $vegetable->addMedia($image)->toMediaCollection('vegetable_image');
        }

        return $vegetable;
    }
}
