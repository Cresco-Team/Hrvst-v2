<?php

namespace App\Actions\Admin;

use App\Models\Profiles\FarmerProfile;

final class ApproveFarmerAction
{
    public function __invoke(FarmerProfile $farmer): void
    {
        abort_if(! $farmer->pending(), 422, "Only pending farmers can be approved.");

        $farmer->approveAcocunt();
    }
}