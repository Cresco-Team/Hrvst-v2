<?php

namespace Database\Seeders;

use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use Database\Seeders\Product\CategorySeeder;
use Database\Seeders\Product\PriceHistorySeeder;
use Database\Seeders\Product\VarietySeeder;
use Database\Seeders\Product\VegetableSeeder;
use Database\Seeders\Profiles\AdminSeeder;
use Database\Seeders\Profiles\DealerSeeder;
use Database\Seeders\Profiles\FarmerSeeder;
use Database\Seeders\Profiles\RoleSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AddressSeeder::class,
        ]);

        $this->call([
            CategorySeeder::class,
            VegetableSeeder::class,
            VarietySeeder::class,
        ]);
        $this->command->info('✓ Products seeded');

        if (app()->environment('local', 'development')) {
            $this->call([
                PriceHistorySeeder::class,
                VarietyMonthlyStatsSeeder::class,
            ]);
            $this->command->info('✓ Product stats seeded');
        }

        $this->call([
            RoleSeeder::class,
            AdminSeeder::class,
        ]);

        if (app()->environment('local', 'development')) {
            $this->call([
                FarmerSeeder::class,
                DealerSeeder::class,
            ]);
        }

        $this->call([
            FarmerProfile::factory(10)->create(),
            DealerProfile::factory(5)->create(),
        ]);
        $this->command->info('✓ Profiles seeded');

        $this->command->info('Database seeded successfully!');
    }
}
