<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Product\PriceHistorySeeder;
use Database\Seeders\Product\VarietySeeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            VarietySeeder::class,
            PriceHistorySeeder::class,
            AdminSeeder::class,
        ]);
    }
}
