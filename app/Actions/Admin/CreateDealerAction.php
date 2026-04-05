<?php

namespace App\Actions\Admin;

use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateDealerAction
{
    /**
     * @return array{user: User, plain_pin: string}
     */
    public function handle(array $validated): array
    {
        $plainPin = $this->generatePin();

        $user = DB::transaction(function () use ($validated, $plainPin): User {
            $user = User::create([
                'name' => $validated['name'],
                'phone_number' => $validated['phone_number'],
                'email' => $validated['email'] ?? null,
                'password' => $plainPin,
                'must_change_pin' => true,
            ]);

            $role = Role::where('name', 'dealer')->firstOrFail();
            $user->roles()->attach($role);

            DealerProfile::create(['user_id' => $user->id]);

            return $user;
        });

        return ['user' => $user, 'plain_pin' => $plainPin];
    }

    private function generatePin(): string
    {
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}
