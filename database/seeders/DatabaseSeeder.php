<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Address\BarangaySeeder;
use Database\Seeders\Address\MunicipalitySeeder;
use Database\Seeders\Address\ProvinceSeeder;
use Database\Seeders\Product\PriceHistorySeeder;
use Database\Seeders\Product\VarietySeeder;
use Database\Seeders\Profiles\AdminSeeder;
use Database\Seeders\Profiles\FarmerSeeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProvinceSeeder::class,
            MunicipalitySeeder::class,
            BarangaySeeder::class,

            VarietySeeder::class,
            PriceHistorySeeder::class,
            
            AdminSeeder::class,
            FarmerSeeder::class,
        ]);
    }
}
