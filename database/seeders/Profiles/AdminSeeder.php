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

        $user = User::firstOrCreate(
            ['email' => 'admin@hrvst.com'],
            [
                'name' => 'Admin Joe',
                'phone_number' => '09303997215',
                'email_verified_at' => now(),
                'password' => config('app.admin_password'),
            ]
        );

        $user->roles()->syncWithoutDetaching([$adminRole->id]);

        $this->command->info('✓ Admin seeded');
    }
}
