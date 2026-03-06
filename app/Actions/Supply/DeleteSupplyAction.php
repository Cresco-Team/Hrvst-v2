<?php

namespace App\Actions\Supply;

use App\Models\Marketplace\FarmerSupply;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class DeleteSupplyAction
{
    public function __invoke(FarmerSupply $supply): void
    {
        DB::transaction(function () use ($supply) {
            if ($supply->image_path) {
                Storage::disk('public')->delete($supply->image_path);
            }

            $supply->post()->delete();
            $supply->delete();
        });
    }
}