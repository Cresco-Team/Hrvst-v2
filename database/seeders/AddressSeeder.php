<?php

namespace Database\Seeders;

use App\Models\Address\Barangay;
use App\Models\Address\Municipality;
use App\Models\Address\Province;
use Illuminate\Database\Seeder;

class AddressSeeder extends Seeder
{
    public function run(): void
    {
        Province::firstOrCreate(['name' => 'Benguet']);

        $municipalities = [
            [
                'name' => 'Atok',
                'latitude' => 16.5712,
                'longitude' => 120.6814,
            ], [
                'name' => 'Bakun',
                'latitude' => 16.7909,
                'longitude' => 120.6638,
            ], [
                'name' => 'Bokod',
                'latitude' => 16.4917,
                'longitude' => 120.8296,
            ], [
                'name' => 'Buguias',
                'latitude' => 16.7201,
                'longitude' => 120.8263,
            ], [
                'name' => 'Itogon',
                'latitude' => 16.3595,
                'longitude' => 120.6773,
            ], [
                'name' => 'Kabayan',
                'latitude' => 16.6228,
                'longitude' => 120.8380,
            ], [
                'name' => 'Kapangan',
                'latitude' => 16.5751,
                'longitude' => 120.5979,
            ], [
                'name' => 'Kibungan',
                'latitude' => 16.6937,
                'longitude' => 120.6539,
            ], [
                'name' => 'La Trinidad',
                'latitude' => 16.4617,
                'longitude' => 120.5885,
            ], [
                'name' => 'Mankayan',
                'latitude' => 16.8569,
                'longitude' => 120.7938,
            ], [
                'name' => 'Sablan',
                'latitude' => 16.4959,
                'longitude' => 120.4880,
            ], [
                'name' => 'Tuba',
                'latitude' => 16.3927,
                'longitude' => 120.5622,
            ], [
                'name' => 'Tublay',
                'latitude' => 16.4431,
                'longitude' => 120.6329,
            ],
        ];

        foreach ($municipalities as $municipality) {
            Municipality::firstOrCreate(
                ['name' => $municipality['name']],
                array_merge($municipality, ['province_id' => 1])
            );
        }

        $ids = Municipality::pluck('id', 'name');

        $data = [
            'Atok' => [
                'Ambiang', 'Caliking', 'Cattubo', 'Naguey',
                'Paoay', 'Pasdong', 'Poblacion', 'Topdac',
            ],
            'Bakun' => [
                'Ampusongan', 'Bagu', 'Dalipey', 'Gambang',
                'Kayapa', 'Poblacion', 'Sinacbat',
            ],
            'Bokod' => [
                'Ambulkao', 'Bila', 'Bobok-Bisal', 'Daclan', 'Ekip',
                'Karao', 'Nawal', 'Pito', 'Poblacion', 'Tikey',
            ],
            'Buguias' => [
                'Abatan', 'Amgaleyguey', 'Amlimay', 'Baculongan Norte',
                'Baculongan Sur', 'Bangao', 'Buyacaoan', 'Calamagan',
                'Catlubong', 'Lengaoan', 'Loo', 'Natubleng',
                'Poblacion', 'Sebang',
            ],
            'Itogon' => [
                'Ampucao', 'Dalupirip', 'Gumatdang', 'Loacan',
                'Poblacion', 'Tinongdan', 'Tuding', 'Ucab', 'Virac',
            ],
            'Kabayan' => [
                'Adaoay', 'Anchukey', 'Ballay', 'Bachoy', 'Batan',
                'Duacan', 'Eddet', 'Gusaran', 'Kabayan Barrio',
                'Lusod', 'Pacso', 'Poblacion', 'Tawangan',
            ],
            'Kapangan' => [
                'Balakbak', 'Beleng-Belis', 'Baklaoan', 'Cayapes', 'Cuba',
                'Datakan', 'Gadang', 'Gaswiling', 'Labueg', 'Paykek',
                'Poblacion Central', 'Pongayan', 'Pudong', 'Sagubo', 'Ta-ao',
            ],
            'Kibungan' => [
                'Badeo', 'Lubo', 'Madaymen', 'Palina',
                'Poblacion', 'Sagpat', 'Tacadang',
            ],
            'La Trinidad' => [
                'Alapang', 'Alno', 'Ambiong', 'Bahong', 'Balili', 'Beckel',
                'Betag', 'Bineng', 'Cruz', 'Lubas', 'Pico', 'Poblacion',
                'Puguis', 'Shilan', 'Tawang', 'Wangal',
            ],
            'Mankayan' => [
                'Balili', 'Bedbed', 'Bulalacao', 'Cabiten', 'Colalo',
                'Guinaoang', 'Paco', 'Palasaan', 'Poblacion',
                'Sapid', 'Tabio', 'Taneg',
            ],
            'Sablan' => [
                'Bagong', 'Balluay', 'Banangan', 'Banengbeng',
                'Bayabas', 'Kamog', 'Pappa', 'Poblacion',
            ],
            'Tuba' => [
                'Ansagan', 'Camp 1', 'Camp 3', 'Camp 4', 'Nangalisan',
                'Poblacion', 'San Pascual', 'Tabaan Norte', 'Tabaan Sur',
                'Tadiangan', 'Taloy Norte', 'Taloy Sur', 'Twin Peaks',
            ],
            'Tublay' => [
                'Ambassador', 'Ambongdolan', 'Ba-ayan', 'Basil',
                'Caponga', 'Daclan', 'Tublay Central', 'Tuel',
            ],
        ];

        foreach ($data as $municipalityName => $barangays) {
            $municipalityId = $ids[$municipalityName]
                ?? throw new \RuntimeException("Municipality '{$municipalityName}' not found. Run MunicipalitySeeder first.");

            foreach ($barangays as $name) {
                Barangay::firstOrCreate([
                    'municipality_id' => $municipalityId,
                    'name' => $name,
                ]);
            }
        }

        $this->command->info('✓ Address seeded');
    }
}
