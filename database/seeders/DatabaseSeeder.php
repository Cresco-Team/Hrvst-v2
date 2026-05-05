<?php

namespace Database\Seeders;

use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use Database\Seeders\Product\PriceHistorySeeder;
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
                PriceHistorySeeder::class,
                // VarietyMonthlyStatsSeeder::class,
            ]);

            FarmerProfile::factory(10)->create();
            DealerProfile::factory(5)->create();
        }

        $this->command->info('Database seeded successfully!');
    }
}
