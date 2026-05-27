<?php

namespace Database\Seeders\Profiles;

use App\Models\Profiles\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::insertOrIgnore([
            ['name' => 'admin'],
            ['name' => 'farmer'],
            ['name' => 'dealer'],
        ]);

        $this->command->info('✓ Roles seeded');
    }
}
