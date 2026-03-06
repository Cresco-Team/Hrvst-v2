<?php

namespace Database\Seeders\Product;

use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use Illuminate\Database\Seeder;

class VarietySeeder extends Seeder
{
    /**
     * Placeholder used for image_path on seeded records.
     * Replace with real assets via the admin panel or a dedicated import command.
     */
    private const IMAGE_PLACEHOLDER = 'images/varieties/placeholder.jpg';

    public function run(): void
    {
        /**
         * Structure: vegetable name => array of ['name', 'weeks_to_harvest']
         *
         * weeks_to_harvest reflects typical growing periods under Benguet
         * highland conditions (~1,500–2,500 masl, cool semi-temperate climate).
         *
         * Sources: BSU Extension publications, DA-CAR Regional Crop Production
         * Guide, BAPTC commodity records, PCARRD variety registry.
         */
        $data = [
            // ── Leafy Vegetables ─────────────────────────────────────────────
            'Lettuce' => [
                ['name' => 'Green Batavia',  'weeks_to_harvest' => 7],
                ['name' => 'Red Batavia',    'weeks_to_harvest' => 7],
                ['name' => 'Butterhead',     'weeks_to_harvest' => 8],
                ['name' => 'Romaine (Cos)',  'weeks_to_harvest' => 8],
                ['name' => 'Lollo Rossa',    'weeks_to_harvest' => 7],
                ['name' => 'Grand Rapids',   'weeks_to_harvest' => 6],
            ],
            'Cabbage' => [
                ['name' => 'KK Cross',          'weeks_to_harvest' => 12],
                ['name' => 'Summer Autumn 55',  'weeks_to_harvest' => 11],
                ['name' => 'Tropicana',         'weeks_to_harvest' => 13],
                ['name' => 'Dominant',          'weeks_to_harvest' => 12],
                ['name' => 'Savoy King',        'weeks_to_harvest' => 14],
            ],
            'Pechay' => [
                ['name' => 'Improved White Stem', 'weeks_to_harvest' => 4],
                ['name' => 'Green Fortune',       'weeks_to_harvest' => 4],
                ['name' => 'Baguio White',        'weeks_to_harvest' => 3],
                ['name' => 'Hybrid F1 Pechay',    'weeks_to_harvest' => 3],
            ],
            'Celery' => [
                ['name' => 'Utah 52-70',              'weeks_to_harvest' => 17],
                ['name' => 'Tall Utah',               'weeks_to_harvest' => 18],
                ['name' => 'Golden Self-Blanching',   'weeks_to_harvest' => 16],
            ],
            'Broccoli' => [
                ['name' => 'Green Magic',    'weeks_to_harvest' => 11],
                ['name' => 'Waltham 29',     'weeks_to_harvest' => 12],
                ['name' => 'Premium Crop',   'weeks_to_harvest' => 11],
                ['name' => 'Marathon',       'weeks_to_harvest' => 12],
            ],
            'Cauliflower' => [
                ['name' => 'Snowball Y',     'weeks_to_harvest' => 12],
                ['name' => 'Igloo',          'weeks_to_harvest' => 11],
                ['name' => 'Benguet White',  'weeks_to_harvest' => 12],
                ['name' => 'Amazing',        'weeks_to_harvest' => 10],
            ],
            'Chinese Cabbage' => [
                ['name' => 'Wong Bok',      'weeks_to_harvest' => 8],
                ['name' => 'Napa Valley',   'weeks_to_harvest' => 8],
                ['name' => 'Green Rocket',  'weeks_to_harvest' => 7],
                ['name' => 'Jade Pagoda',   'weeks_to_harvest' => 7],
            ],
            'Spinach' => [
                ['name' => 'Bloomsdale', 'weeks_to_harvest' => 7],
                ['name' => 'Tyee',       'weeks_to_harvest' => 7],
                ['name' => 'Regiment',   'weeks_to_harvest' => 8],
            ],

            // ── Root Vegetables ───────────────────────────────────────────────
            'Carrot' => [
                ['name' => 'Chantenay Red Core',    'weeks_to_harvest' => 11],
                ['name' => 'Nantes Coreless',       'weeks_to_harvest' => 10],
                ['name' => 'Imperator 58',          'weeks_to_harvest' => 12],
                ['name' => 'Benguet Hybrid Carrot', 'weeks_to_harvest' => 11],
                ['name' => 'Kuroda',                'weeks_to_harvest' => 10],
            ],
            'Potato' => [
                ['name' => 'Granola',        'weeks_to_harvest' => 14],
                ['name' => 'Desiree',        'weeks_to_harvest' => 15],
                ['name' => 'Igorota',        'weeks_to_harvest' => 16],
                ['name' => 'Benguet Native', 'weeks_to_harvest' => 16],
                ['name' => 'Red Pontiac',    'weeks_to_harvest' => 14],
                ['name' => 'Lady Rosetta',   'weeks_to_harvest' => 15],
            ],
            'Radish' => [
                ['name' => 'Japanese White',   'weeks_to_harvest' => 5],
                ['name' => 'Cherry Belle',     'weeks_to_harvest' => 4],
                ['name' => 'Daikon',           'weeks_to_harvest' => 6],
                ['name' => 'French Breakfast', 'weeks_to_harvest' => 4],
            ],
            'Sayote' => [
                ['name' => 'Green Prickly',   'weeks_to_harvest' => 18],
                ['name' => 'White Smooth',    'weeks_to_harvest' => 18],
                ['name' => 'Spineless Green', 'weeks_to_harvest' => 17],
            ],
            'Turnip' => [
                ['name' => 'Purple Top White Globe', 'weeks_to_harvest' => 7],
                ['name' => 'Tokyo Cross',            'weeks_to_harvest' => 6],
            ],
            'Beet' => [
                ['name' => 'Detroit Dark Red', 'weeks_to_harvest' => 9],
                ['name' => 'Chioggia',         'weeks_to_harvest' => 9],
                ['name' => 'Golden Beet',      'weeks_to_harvest' => 10],
            ],

            // ── Fruiting Vegetables ───────────────────────────────────────────
            'Tomato' => [
                ['name' => 'Cardinal',      'weeks_to_harvest' => 12],
                ['name' => 'Diamante Max',  'weeks_to_harvest' => 11],
                ['name' => 'Lydia',         'weeks_to_harvest' => 12],
                ['name' => 'Benguet Local', 'weeks_to_harvest' => 13],
                ['name' => 'Marikit',       'weeks_to_harvest' => 11],
                ['name' => 'Perla',         'weeks_to_harvest' => 12],
            ],
            'Eggplant' => [
                ['name' => 'Long Purple',    'weeks_to_harvest' => 11],
                ['name' => 'Black Beauty',   'weeks_to_harvest' => 12],
                ['name' => 'Ping Tung Long', 'weeks_to_harvest' => 10],
                ['name' => 'Sinabawanon',    'weeks_to_harvest' => 11],
            ],
            'Bitter Melon' => [
                ['name' => 'Galaxy',     'weeks_to_harvest' => 14],
                ['name' => 'Jade Star',  'weeks_to_harvest' => 13],
                ['name' => 'Sta. Rita',  'weeks_to_harvest' => 14],
                ['name' => 'Tiyan Baboy','weeks_to_harvest' => 15],
            ],
            'Bell Pepper' => [
                ['name' => 'California Wonder', 'weeks_to_harvest' => 11],
                ['name' => 'Yolo Wonder',       'weeks_to_harvest' => 11],
                ['name' => 'Lamuyo',            'weeks_to_harvest' => 10],
                ['name' => 'Sweet Chocolate',   'weeks_to_harvest' => 12],
            ],
            'Squash' => [
                ['name' => 'Honey Bear',       'weeks_to_harvest' => 9],
                ['name' => 'Waltham Butternut','weeks_to_harvest' => 11],
                ['name' => 'Buttercup',        'weeks_to_harvest' => 10],
            ],
            'Chayote' => [
                ['name' => 'Smooth White',  'weeks_to_harvest' => 18],
                ['name' => 'Smooth Green',  'weeks_to_harvest' => 18],
                ['name' => 'Prickly Green', 'weeks_to_harvest' => 20],
            ],

            // ── Bean Vegetables ───────────────────────────────────────────────
            'Snow Peas' => [
                ['name' => 'Oregon Sugar Pod',       'weeks_to_harvest' => 9],
                ['name' => 'Mammoth Melting Sugar',  'weeks_to_harvest' => 10],
                ['name' => 'Benguet Snow Pea',       'weeks_to_harvest' => 9],
            ],
            'String Beans' => [
                ['name' => 'Contender', 'weeks_to_harvest' => 7],
                ['name' => 'Blue Lake', 'weeks_to_harvest' => 8],
                ['name' => 'Jade',      'weeks_to_harvest' => 7],
                ['name' => 'Sitaw Pula','weeks_to_harvest' => 8],
            ],
            'Green Beans' => [
                ['name' => 'Kentucky Wonder', 'weeks_to_harvest' => 8],
                ['name' => 'Provider',        'weeks_to_harvest' => 7],
                ['name' => 'Roma II',         'weeks_to_harvest' => 8],
            ],
            'Sugar Snap Peas' => [
                ['name' => 'Super Sugar Snap', 'weeks_to_harvest' => 9],
                ['name' => 'Cascadia',         'weeks_to_harvest' => 9],
                ['name' => 'Sugar Ann',        'weeks_to_harvest' => 8],
            ],
            'Chicharo' => [
                ['name' => 'Benguet Chicharo Local', 'weeks_to_harvest' => 9],
                ['name' => 'Wando',                  'weeks_to_harvest' => 10],
            ],
            'Broad Beans' => [
                ['name' => 'Aquadulce Claudia', 'weeks_to_harvest' => 15],
                ['name' => 'The Sutton',         'weeks_to_harvest' => 14],
            ],
        ];

        foreach ($data as $vegetableName => $varieties) {
            $vegetable = Vegetable::where('name', $vegetableName)->firstOrFail();

            foreach ($varieties as $variety) {
                Variety::firstOrCreate(
                    // Search keys — must match the unique index: (vegetable_id, name)
                    [
                        'vegetable_id' => $vegetable->id,
                        'name'         => $variety['name'],
                    ],
                    // Attributes set only on CREATE — never overwrite on re-seed
                    [
                        'image_path'       => self::IMAGE_PLACEHOLDER,
                        'weeks_to_harvest' => $variety['weeks_to_harvest'],
                    ]
                );
            }
        }
    }
}
