<?php

namespace App\Actions\Admin;

use App\Models\User;

final class UpdateUserPhoneAction
{
    public function handle(User $user, string $phoneNumber): User
    {
        $user->update(['phone_number' => $phoneNumber]);

        return $user;
    }
}
