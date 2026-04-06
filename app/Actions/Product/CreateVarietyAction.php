<?php

namespace App\Actions\Product;

use App\Models\Product\Variety;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CreateVarietyAction
{
    public function handle(array $validated, ?UploadedFile $image = null): Variety
    {
        $priceMin = $validated['price_min'];
        $priceMax = $validated['price_max'];
        unset($validated['price_min'], $validated['price_max']);

        $variety = Variety::create($validated);

        if ($image !== null) {
            $tmpPath = $image->store('tmp/varieties', 'local');

            try {
                $variety->addMediaFromDisk($tmpPath, 'local')
                    ->toMediaCollection('variety_image');
            } finally {
                Storage::disk('local')->delete($tmpPath);
            }
        }

        $variety->prices()->create([
            'price_min' => $priceMin,
            'price_max' => $priceMax,
            'recorded_at' => now()->startOfWeek(),
        ]);

        return $variety->load('latestPrice');
    }
}
