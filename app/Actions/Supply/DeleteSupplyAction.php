<?php

namespace App\Actions\Supply;

use App\Models\Marketplace\FarmerSupply;
use Illuminate\Support\Facades\DB;

final class DeleteSupplyAction
{
    public function __invoke(FarmerSupply $supply): void
    {
        DB::transaction(function () use ($supply) {
            // InteractsWithMedia fires a 'deleted' observer hook that removes
            // all associated media files and records automatically.
            // The explicit Storage::disk('public')->delete($supply->image_path) is gone.
            $supply->post()->delete();
            $supply->delete();
        });
    }
}
