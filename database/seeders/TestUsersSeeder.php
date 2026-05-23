<?php

namespace Database\Seeders;

use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use App\Models\Profiles\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        /* Farmer Jane */
        $farmerRole = Role::firstOrCreate(['name' => 'farmer']);

        $user = User::firstOrCreate(
            ['email' => 'farmer@hrvst.com'],
            [
                'name' => 'Farmer Jane',
                'phone_number' => '09123456789',
                'email_verified_at' => now(),
                'password' => '0000',
            ]
        );

        $user->roles()->syncWithoutDetaching([$farmerRole->id]);

        FarmerProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'province_id' => 1,
                'municipality_id' => 1,
                'barangay_id' => 1,
                'latitude' => 16.4023,
                'longitude' => 120.5960,
            ]
        );

        /* Dealer John */
        $dealerRole = Role::firstOrCreate(['name' => 'dealer']);

        $user = User::firstOrCreate(
            ['email' => 'dealer@hrvst.com'],
            [
                'name' => 'Dealer John',
                'phone_number' => '09171234567',
                'email_verified_at' => now(),
                'password' => '0000',
            ]
        );

        $user->roles()->syncWithoutDetaching([$dealerRole->id]);

        DealerProfile::firstOrCreate(
            ['user_id' => $user->id],
        );

        $this->command->info('✓ Test users seeded:');
    }
}
