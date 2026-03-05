<?php

namespace Database\Seeders\Address;

use App\Models\Address\Municipality;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MunicipalitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $municipalities = [
            [
                'name' => 'Atok',
                'latitude' => 16.5712,
                'longitude' => 120.6814
            ], [
                'name' => 'Bakun',
                'latitude' => 16.7909,
                'longitude' => 120.6638
            ], [
                'name' => 'Bokod',
                'latitude' => 16.4917,
                'longitude' => 120.8296
            ], [
                'name' => 'Buguias',
                'latitude' => 16.7201,
                'longitude' => 120.8263
            ], [
                'name' => 'Itogon',
                'latitude' => 16.3595,
                'longitude' => 120.6773
            ], [
                'name' => 'Kabayan',
                'latitude' => 16.6228,
                'longitude' => 120.8380
            ], [
                'name' => 'Kapangan',
                'latitude' => 16.5751,
                'longitude' => 120.5979
            ], [
                'name' => 'Kibungan',
                'latitude' => 16.6937,
                'longitude' => 120.6539
            ], [
                'name' => 'La Trinidad',
                'latitude' => 16.4617,
                'longitude' => 120.5885
            ], [
                'name' => 'Mankayan',
                'latitude' => 16.8569,
                'longitude' => 120.7938
            ], [
                'name' => 'Sablan',
                'latitude' => 16.4959,
                'longitude' => 120.4880
            ], [
                'name' => 'Tuba',
                'latitude' => 16.3927,
                'longitude' => 120.5622
            ], [
                'name' => 'Tublay',
                'latitude' => 16.4431,
                'longitude' => 120.6329
            ],
        ];

        foreach ($municipalities as $municipality) {
            Municipality::firstOrCreate(
                ['name' => $municipality['name']],
                array_merge($municipality, ['province_id' => 1])
            );
        }
    }
}
