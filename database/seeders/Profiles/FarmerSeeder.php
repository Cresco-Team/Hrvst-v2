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
        $farmerRole = Role::firstOrCreate([
            'name' => 'farmer'
        ]);

        $user = User::firstOrCreate([
            'email' => 'farmer@hrvst.com',
            'phone_number' => '09123456789'
        ], [
            'name' => 'Farmer Jane',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $user->roles()->attach($farmerRole);

        FarmerProfile::firstOrCreate(
            [
                'user_id' => $user->id,
            ],
            [
                'is_approved' => false,
                'province_id' => 1,
                'municipality_id' => 1,
                'barangay_id' => 1,
                'latitude' => 16.4023,
                'longitude' => 120.5960,
                'farm_image' => null,
            ]
        );

        $this->command->info('✓ Test farmer created: farmer@hrvst.com / password');
    }
}
