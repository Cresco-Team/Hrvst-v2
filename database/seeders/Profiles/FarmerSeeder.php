<?php

namespace Database\Seeders\Profiles;

use App\Models\Profiles\FarmerProfile;
use App\Models\Profiles\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FarmerSeeder extends Seeder
{
    public function run(): void
    {
        $farmerRole = Role::firstOrCreate(['name' => 'farmer']);

        $user = User::firstOrCreate(
            ['email' => 'farmer@hrvst.com'],
            [
                'name' => 'Farmer Jane',
                'phone_number' => '09123456789',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        $user->roles()->syncWithoutDetaching([$farmerRole->id]);

        FarmerProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'is_approved' => true,
                'province_id' => 1,
                'municipality_id' => 1,
                'barangay_id' => 1,
                'latitude' => 16.4023,
                'longitude' => 120.5960,
            ]
        );

        $this->command->info('✓ Farmer seeded: farmer@hrvst.com / password');

        FarmerProfile::factory(10)->create();
    }
}
