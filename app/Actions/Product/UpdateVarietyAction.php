<?php

namespace App\Actions\Product;

use App\Models\Product\Variety;
use Illuminate\Http\UploadedFile;

class UpdateVarietyAction
{
    public function handle(Variety $variety, array $validated, ?UploadedFile $image = null): Variety
    {
        $variety->update($validated);

        if ($image) {
            $variety->addMedia($image)->toMediaCollection('variety_image');
        }

        return $variety->load('latestPrice');
    }
}