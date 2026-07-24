<?php

namespace App\Actions\Admin;

use App\Concerns\GeneratesPin;
use App\Models\User;

final class ResetUserPinAction
{
    use GeneratesPin;

    /**
     * Reset a user's PIN and flag them to change it on next login.
     * Returns the plaintext PIN — admin relays this to the user in person.
     */
    public function handle(User $user): string
    {
        $plainPin = $this->generatePin();

        $user->update([
            'password' => $plainPin,
            'must_change_pin' => true,
        ]);

        return $plainPin;
    }
}
