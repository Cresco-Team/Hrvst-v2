<?php

namespace Database\Seeders\Profiles;

use App\Models\Profiles\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::insert([
            ['name' => 'farmer'],
            ['name' => 'dealer'],
        ]);
    }
}
