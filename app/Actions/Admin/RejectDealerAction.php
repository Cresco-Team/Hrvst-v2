<?php
namespace App\Actions\Admin;

use App\Models\Profiles\DealerProfile;

final class RejectDealerAction
{
    public function __invoke(DealerProfile $dealer): void
    {
        abort_if(! $dealer->pending(), 422, "Only pending dealers can be rejected.");

        $dealer->rejectAccount();
    }
}
