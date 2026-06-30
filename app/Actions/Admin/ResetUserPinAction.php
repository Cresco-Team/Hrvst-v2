<?php

namespace App\Actions\Admin;

use App\Models\User;

final class ResetUserPinAction
{
    /**
     * Reset a user's PIN and flag them to change it on next login.
     * Returns the plaintext PIN — admin relays this to the user in person.
     */
    public function handle(User $user): string
    {
        $plainPin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'password' => $plainPin,
            'must_change_pin' => true,
        ]);

        return $plainPin;
    }
}
