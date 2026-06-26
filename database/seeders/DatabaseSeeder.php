<?php

namespace Database\Seeders;

use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use Database\Seeders\Profiles\AdminSeeder;
use Database\Seeders\Profiles\RoleSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AddressSeeder::class,
            ProductSeeder::class,
            RoleSeeder::class,
            AdminSeeder::class,
        ]);

        if (app()->environment('local', 'development')) {
            $this->call([
                TestUsersSeeder::class,
            ]);

            FarmerProfile::factory(500)->create();
            DealerProfile::factory(30)->create();

            $this->call([
                PostSeeder::class,
                VarietyMonthlyStatsSeeder::class,
            ]);
        }

        $this->command->info('Database seeded successfully!');
    }
}
