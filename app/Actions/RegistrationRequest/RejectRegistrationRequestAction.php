<?php

namespace App\Actions\RegistrationRequest;

use App\Enums\RegistrationRequestStatus;
use App\Models\RegistrationRequest;
use App\Models\User;

final class RejectRegistrationRequestAction
{
    public function handle(RegistrationRequest $registrationRequest, User $reviewer, ?string $reason = null): void
    {
        abort_if(
            $registrationRequest->status !== RegistrationRequestStatus::Pending,
            409,
            'This request has already been reviewed.'
        );

        $registrationRequest->update([
            'status' => RegistrationRequestStatus::Rejected,
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);
    }
}
