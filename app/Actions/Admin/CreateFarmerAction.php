<?php

namespace App\Actions\Admin;

use App\Concerns\GeneratesPin;
use App\Models\Address\Municipality;
use App\Models\Profiles\FarmerProfile;
use App\Models\Profiles\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateFarmerAction
{
    use GeneratesPin;

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

            $role = Role::where('name', 'farmer')->firstOrFail();
            $user->roles()->attach($role);

            $municipality = Municipality::findOrFail($validated['municipality_id']);

            FarmerProfile::create([
                'user_id' => $user->id,
                'province_id' => $municipality->province_id,
                'municipality_id' => $validated['municipality_id'],
                'barangay_id' => $validated['barangay_id'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
            ]);

            return $user;
        });

        return ['user' => $user, 'plain_pin' => $plainPin];
    }
}
