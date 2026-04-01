<?php

namespace Database\Seeders\Profiles;

use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DealerSeeder extends Seeder
{
    public function run(): void
    {
        $dealerRole = Role::firstOrCreate(['name' => 'dealer']);

        $user = User::firstOrCreate(
            ['email' => 'dealer@hrvst.com'],
            [
                'name' => 'Dealer John',
                'phone_number' => '09171234567',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        $user->roles()->syncWithoutDetaching([$dealerRole->id]);

        DealerProfile::firstOrCreate(
            ['user_id' => $user->id],
        );

        $this->command->info('✓ Dealer seeded: dealer@hrvst.com / password');

        DealerProfile::factory(5)->create();
    }
}
