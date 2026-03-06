<?php
namespace App\Actions\Farmer;

use App\Models\Profiles\FarmerProfile;

final class RejectFarmerAction
{
    public function __invoke(FarmerProfile $farmer): void
    {
        abort_if(! $farmer->pending(), 422, "Only pending farmers can be rejected.");

        $farmer->rejectAccount();
    }
}