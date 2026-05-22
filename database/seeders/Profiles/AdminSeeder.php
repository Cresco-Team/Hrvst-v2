<?php

namespace Database\Seeders\Profiles;

use App\Models\Profiles\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        $password = '0000';

        if (app()->environment('production')) {
            $password = env('ADMIN_PASSWORD');
        }

        $user = User::firstOrCreate(
            ['email' => 'admin@hrvst.com'],
            [
                'name' => 'Admin Joe',
                'phone_number' => '09303997215',
                'email_verified_at' => now(),
                'password' => $password,
            ]
        );

        $user->roles()->syncWithoutDetaching([$adminRole->id]);

        $this->command->info('✓ Admin seeded');
    }
}
