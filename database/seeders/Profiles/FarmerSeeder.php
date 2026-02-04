<?php

namespace Database\Seeders\Profiles;

use App\Models\Profiles\FarmerProfile;
use App\Models\Profiles\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            'password' => Hash::make('password'),
        ]);

        $user->roles()->syncWithoutDetaching([$farmerRole->id]);

        FarmerProfile::firstOrCreate(
            [
                'user_id' => $user->id,
            ],
            [
                'is_approved' => true,
                'province_id' => 1,
                'municipality_id' => 1, // CHANGE if invalid
                'barangay_id' => 1,     // CHANGE if invalid
                'latitude' => 16.4023,
                'longitude' => 120.5960,
                'farm_image' => null,
            ]
        );
    }
}
