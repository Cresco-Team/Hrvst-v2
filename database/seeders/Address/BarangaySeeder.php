<?php

namespace Database\Seeders\Address;

use App\Models\Address\Barangay;
use App\Models\Address\Municipality;
use Illuminate\Database\Seeder;

class BarangaySeeder extends Seeder
{
    public function run(): void
    {
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
                    'name'            => $name,
                ]);
            }
        }
    }
}
