<?php

namespace Database\Seeders\Profiles;

use App\Models\Profiles\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate([
            'name' => 'admin'
        ]);

        $user = User::firstOrCreate([
            'email' => 'admin@hrvst.com',
            'phone_number' => '09303997215'
        ], [
            'name' => 'Admin Joe',
            'password' => Hash::make('password'),
        ]);

        $user->roles()->syncWithoutDetaching([$adminRole->id]);
    }
}
