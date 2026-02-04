<?php

namespace Database\Seeders\Address;

use App\Models\Address\Province;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProvinceSeeder extends Seeder
{
    public function run(): void
    {
        Province::updateOrCreate(
            ['id' => 1],
            ['name' => 'Benguet']
        );
    }
}
