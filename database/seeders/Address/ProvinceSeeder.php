<?php

namespace Database\Seeders\Address;

use App\Models\Address\Province;
use Illuminate\Database\Seeder;

class ProvinceSeeder extends Seeder
{
    public function run(): void
    {
        Province::firstOrCreate(['name' => 'Benguet']);
    }
}