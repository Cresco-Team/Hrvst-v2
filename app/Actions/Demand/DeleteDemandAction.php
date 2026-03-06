<?php

namespace App\Actions\Demand;

use App\Models\Marketplace\DealerDemand;
use Illuminate\Support\Facades\DB;

final class DeleteDemandAction
{
    public function __invoke(DealerDemand $demand): void
    {
        DB::transaction(function () use ($demand) {
            $demand->post()->delete();
            $demand->delete();
        });
    }
}
