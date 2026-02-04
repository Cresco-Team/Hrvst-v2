<?php

namespace Database\Seeders\Address;

use App\Models\Address\Barangay;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BarangaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $barangays = [
            // Atok
            ['municipality_id' => 1, 'name' => 'Ambiang'],
            ['municipality_id' => 1, 'name' => 'Caliking'],
            ['municipality_id' => 1, 'name' => 'Cattubo'],
            ['municipality_id' => 1, 'name' => 'Naguey'],
            ['municipality_id' => 1, 'name' => 'Paoay'],
            ['municipality_id' => 1, 'name' => 'Pasdong'],
            ['municipality_id' => 1, 'name' => 'Poblacion'],
            ['municipality_id' => 1, 'name' => 'Topdac'],

            // Bakun
            ['municipality_id' => 2, 'name' => 'Ampusongan'],
            ['municipality_id' => 2, 'name' => 'Bagu'],
            ['municipality_id' => 2, 'name' => 'Dalipey'],
            ['municipality_id' => 2, 'name' => 'Gambang'],
            ['municipality_id' => 2, 'name' => 'Kayapa'],
            ['municipality_id' => 2, 'name' => 'Poblacion'],
            ['municipality_id' => 2, 'name' => 'Sinacbat'],

            // Bokod
            ['municipality_id' => 3, 'name' => 'Ambulkao'],
            ['municipality_id' => 3, 'name' => 'Bila'],
            ['municipality_id' => 3, 'name' => 'Bobok-Bisal'],
            ['municipality_id' => 3, 'name' => 'Daclan'],
            ['municipality_id' => 3, 'name' => 'Ekip'],
            ['municipality_id' => 3, 'name' => 'Karao'],
            ['municipality_id' => 3, 'name' => 'Nawal'],
            ['municipality_id' => 3, 'name' => 'Pito'],
            ['municipality_id' => 3, 'name' => 'Poblacion'],
            ['municipality_id' => 3, 'name' => 'Tikey'],

            // Buguias
            ['municipality_id' => 4, 'name' => 'Abatan'],
            ['municipality_id' => 4, 'name' => 'Amgaleyguey'],
            ['municipality_id' => 4, 'name' => 'Amlimay'],
            ['municipality_id' => 4, 'name' => 'Baculongan Norte'],
            ['municipality_id' => 4, 'name' => 'Baculongan Sur'],
            ['municipality_id' => 4, 'name' => 'Bangao'],
            ['municipality_id' => 4, 'name' => 'Buyacaoan'],
            ['municipality_id' => 4, 'name' => 'Calamagan'],
            ['municipality_id' => 4, 'name' => 'Catlubong'],
            ['municipality_id' => 4, 'name' => 'Lengaoan'],
            ['municipality_id' => 4, 'name' => 'Loo'],
            ['municipality_id' => 4, 'name' => 'Natubleng'],
            ['municipality_id' => 4, 'name' => 'Poblacion'],
            ['municipality_id' => 4, 'name' => 'Sebang'],

            // Itogon
            ['municipality_id' => 5, 'name' => 'Ampucao'],
            ['municipality_id' => 5, 'name' => 'Dalupirip'],
            ['municipality_id' => 5, 'name' => 'Gumatdang'],
            ['municipality_id' => 5, 'name' => 'Loacan'],
            ['municipality_id' => 5, 'name' => 'Poblacion'],
            ['municipality_id' => 5, 'name' => 'Tinongdan'],
            ['municipality_id' => 5, 'name' => 'Tuding'],
            ['municipality_id' => 5, 'name' => 'Ucab'],
            ['municipality_id' => 5, 'name' => 'Virac'],

            // Kabayan
            ['municipality_id' => 6, 'name' => 'Adaoay'],
            ['municipality_id' => 6, 'name' => 'Anchukey'],
            ['municipality_id' => 6, 'name' => 'Ballay'],
            ['municipality_id' => 6, 'name' => 'Bachoy'],
            ['municipality_id' => 6, 'name' => 'Batan'],
            ['municipality_id' => 6, 'name' => 'Duacan'],
            ['municipality_id' => 6, 'name' => 'Eddet'],
            ['municipality_id' => 6, 'name' => 'Gusaran'],
            ['municipality_id' => 6, 'name' => 'Kabayan Barrio'],
            ['municipality_id' => 6, 'name' => 'Lusod'],
            ['municipality_id' => 6, 'name' => 'Pacso'],
            ['municipality_id' => 6, 'name' => 'Poblacion'],
            ['municipality_id' => 6, 'name' => 'Tawangan'],

            // Kapangan
            ['municipality_id' => 7, 'name' => 'Balakbak'],
            ['municipality_id' => 7, 'name' => 'Beleng-Belis'],
            ['municipality_id' => 7, 'name' => 'Baklaoan'],
            ['municipality_id' => 7, 'name' => 'Cayapes'],
            ['municipality_id' => 7, 'name' => 'Cuba'],
            ['municipality_id' => 7, 'name' => 'Datakan'],
            ['municipality_id' => 7, 'name' => 'Gadang'],
            ['municipality_id' => 7, 'name' => 'Gaswiling'],
            ['municipality_id' => 7, 'name' => 'Labueg'],
            ['municipality_id' => 7, 'name' => 'Paykek'],
            ['municipality_id' => 7, 'name' => 'Poblacion Central'],
            ['municipality_id' => 7, 'name' => 'Pongayan'],
            ['municipality_id' => 7, 'name' => 'Pudong'],
            ['municipality_id' => 7, 'name' => 'Sagubo'],
            ['municipality_id' => 7, 'name' => 'Ta-ao'],

            // Kibungan
            ['municipality_id' => 8, 'name' => 'Badeo'],
            ['municipality_id' => 8, 'name' => 'Lubo'],
            ['municipality_id' => 8, 'name' => 'Madaymen'],
            ['municipality_id' => 8, 'name' => 'Palina'],
            ['municipality_id' => 8, 'name' => 'Poblacion'],
            ['municipality_id' => 8, 'name' => 'Sagpat'],
            ['municipality_id' => 8, 'name' => 'Tacadang'],

            // La Trinidad
            ['municipality_id' => 9, 'name' => 'Alapang'],
            ['municipality_id' => 9, 'name' => 'Alno'],
            ['municipality_id' => 9, 'name' => 'Ambiong'],
            ['municipality_id' => 9, 'name' => 'Bahong'],
            ['municipality_id' => 9, 'name' => 'Balili'],
            ['municipality_id' => 9, 'name' => 'Beckel'],
            ['municipality_id' => 9, 'name' => 'Betag'],
            ['municipality_id' => 9, 'name' => 'Bineng'],
            ['municipality_id' => 9, 'name' => 'Cruz'],
            ['municipality_id' => 9, 'name' => 'Lubas'],
            ['municipality_id' => 9, 'name' => 'Pico'],
            ['municipality_id' => 9, 'name' => 'Poblacion'],
            ['municipality_id' => 9, 'name' => 'Puguis'],
            ['municipality_id' => 9, 'name' => 'Shilan'],
            ['municipality_id' => 9, 'name' => 'Tawang'],
            ['municipality_id' => 9, 'name' => 'Wangal'],

            // Mankayan
            ['municipality_id' => 10, 'name' => 'Balili'],
            ['municipality_id' => 10, 'name' => 'Bedbed'],
            ['municipality_id' => 10, 'name' => 'Bulalacao'],
            ['municipality_id' => 10, 'name' => 'Cabiten'],
            ['municipality_id' => 10, 'name' => 'Colalo'],
            ['municipality_id' => 10, 'name' => 'Guinaoang'],
            ['municipality_id' => 10, 'name' => 'Paco'],
            ['municipality_id' => 10, 'name' => 'Palasaan'],
            ['municipality_id' => 10, 'name' => 'Poblacion'],
            ['municipality_id' => 10, 'name' => 'Sapid'],
            ['municipality_id' => 10, 'name' => 'Tabio'],
            ['municipality_id' => 10, 'name' => 'Taneg'],

            // Sablan
            ['municipality_id' => 11, 'name' => 'Bagong'],
            ['municipality_id' => 11, 'name' => 'Balluay'],
            ['municipality_id' => 11, 'name' => 'Banangan'],
            ['municipality_id' => 11, 'name' => 'Banengbeng'],
            ['municipality_id' => 11, 'name' => 'Bayabas'],
            ['municipality_id' => 11, 'name' => 'Kamog'],
            ['municipality_id' => 11, 'name' => 'Pappa'],
            ['municipality_id' => 11, 'name' => 'Poblacion'],

            // Tuba
            ['municipality_id' => 12, 'name' => 'Ansagan'],
            ['municipality_id' => 12, 'name' => 'Camp 1'],
            ['municipality_id' => 12, 'name' => 'Camp 3'],
            ['municipality_id' => 12, 'name' => 'Camp 4'],
            ['municipality_id' => 12, 'name' => 'Nangalisan'],
            ['municipality_id' => 12, 'name' => 'Poblacion'],
            ['municipality_id' => 12, 'name' => 'San Pascual'],
            ['municipality_id' => 12, 'name' => 'Tabaan Norte'],
            ['municipality_id' => 12, 'name' => 'Tabaan Sur'],
            ['municipality_id' => 12, 'name' => 'Tadiangan'],
            ['municipality_id' => 12, 'name' => 'Taloy Norte'],
            ['municipality_id' => 12, 'name' => 'Taloy Sur'],
            ['municipality_id' => 12, 'name' => 'Twin Peaks'],

            // Tublay
            ['municipality_id' => 13, 'name' => 'Ambassador'],
            ['municipality_id' => 13, 'name' => 'Ambongdolan'],
            ['municipality_id' => 13, 'name' => 'Ba-ayan'],
            ['municipality_id' => 13, 'name' => 'Basil'],
            ['municipality_id' => 13, 'name' => 'Caponga'],
            ['municipality_id' => 13, 'name' => 'Daclan'],
            ['municipality_id' => 13, 'name' => 'Tublay Central'],
            ['municipality_id' => 13, 'name' => 'Tuel'],
        ];

        foreach ($barangays as $brgy) {
            Barangay::FirstOrCreate($brgy);
        }
    }
}
