<?php

namespace Database\Seeders\Profiles;

use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DealerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dealerRole = Role::firstOrCreate(['name' => 'dealer']);

        $dealer = User::firstOrCreate([
            'email' => 'dealer@hrvst.com',
            'phone_number' => '09171234567'
        ], [
            'name' => 'Dealer John',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $dealer->roles()->attach($dealerRole);

        // Create approved dealer profile
        DealerProfile::create([
            'user_id' => $dealer->id,
        ], [
            'is_approved' => true,
            'document_image' => null,
        ]);

        $this->command->info('✓ Test dealer created: dealer@test.com / password');
    }
}
