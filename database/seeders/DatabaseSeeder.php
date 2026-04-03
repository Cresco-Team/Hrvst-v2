<?php

namespace Database\Seeders;

use Database\Seeders\Address\BarangaySeeder;
use Database\Seeders\Address\MunicipalitySeeder;
use Database\Seeders\Address\ProvinceSeeder;
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
            ProvinceSeeder::class,
            MunicipalitySeeder::class,
            BarangaySeeder::class,
        ]);

        $this->call([
            CategorySeeder::class,
            VegetableSeeder::class,
            VarietySeeder::class,
        ]);

        if (app()->environment('local', 'development')) {
            $this->call([
                PriceHistorySeeder::class,
                VarietyMonthlyStatsSeeder::class,
            ]);
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

        $this->command->info('Database seeded successfully!');
    }
}
