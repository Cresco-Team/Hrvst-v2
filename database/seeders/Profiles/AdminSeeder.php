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

        $adminUsers = [
            [
                'phone_number' => '09746654779',
                'name' => 'Trading Post',
                'password' => '000000',
                'must_change_pin' => true
            ], [
                'phone_number' => '09640549891',
                'name' => 'Trading Post',
                'password' => '000000',
                'must_change_pin' => true
            ]
        ];

        foreach($adminUsers as $admin) {
            $administrator = User::firstOrCreate([
                'phone_number' => $admin['phone_number']
            ], [
                'name' => $admin['name'],
                'password' => $admin['password']
            ]);

            $administrator->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        /* Test Admin */
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
