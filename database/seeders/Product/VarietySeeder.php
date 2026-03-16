<?php

namespace Database\Seeders\Product;

use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use Illuminate\Database\Seeder;

class VarietySeeder extends Seeder
{
    public function run(): void
    {
        /**
         * Images are managed via laravel-medialibrary (spatie/laravel-medialibrary).
         * Upload variety images through the admin panel after seeding.
         *
         * Sources: BSU Extension publications, DA-CAR Regional Crop Production
         * Guide, BAPTC commodity records, PCARRD variety registry.
         */
        $data = [
            // ── Leafy Vegetables ─────────────────────────────────────────────
            'Lettuce' => [
                ['name' => 'Green Batavia'],
                ['name' => 'Red Batavia'],
                ['name' => 'Butterhead'],
                ['name' => 'Romaine (Cos)'],
                ['name' => 'Lollo Rossa'],
                ['name' => 'Grand Rapids'],
            ],
            'Cabbage Scorpio' => [
                ['name' => '1st Class'],
                ['name' => '2nd Class'],
            ],
            'Cabbage Wonderball' => [
                ['name' => '1st Class'],
                ['name' => '2nd Class'],
                ['name' => 'Big Size'],
            ],
            'Cabbage Rareball' => [
                ['name' => '1st Class'],
                ['name' => '2nd Class'],
            ],
            'Pechay' => [
                ['name' => 'Improved White Stem'],
                ['name' => 'Green Fortune'],
                ['name' => 'Baguio White'],
                ['name' => 'Hybrid F1 Pechay'],
            ],
            'Celery' => [
                ['name' => 'Utah 52-70'],
                ['name' => 'Tall Utah'],
                ['name' => 'Golden Self-Blanching'],
            ],
            'Broccoli' => [
                ['name' => 'Trimmed'],
            ],
            'Cauliflower' => [
                ['name' => 'Snowball Y'],
                ['name' => 'Igloo'],
                ['name' => 'Benguet White'],
                ['name' => 'Amazing'],
            ],
            'Spinach' => [
                ['name' => 'Bloomsdale'],
                ['name' => 'Tyee'],
                ['name' => 'Regiment'],
            ],

            // ── Root Vegetables ───────────────────────────────────────────────
            'Carrot' => [
                ['name' => 'Big'],
                ['name' => 'ML'],
                ['name' => 'Medium'],
                ['name' => 'Lumpia'],
            ],
            'Potato Granola' => [
                ['name' => 'SXL'],
                ['name' => 'XL'],
                ['name' => '3XL'],
                ['name' => 'Extra'],
                ['name' => 'Marble'],
            ],
            'Potato LBR' => [
                ['name' => 'SXL'],
                ['name' => 'XL'],
                ['name' => '3XL'],
                ['name' => 'Extra'],
                ['name' => 'Marble'],
            ],
            'Radish Long' => [
                ['name' => 'Good'],
                ['name' => 'Good-semi'],
                ['name' => 'Big size'],
                ['name' => 'Putol'],
            ],
            'Sayote' => [
                ['name' => 'Green Prickly'],
                ['name' => 'White Smooth'],
                ['name' => 'Spineless Green'],
            ],
            'Turnip' => [
                ['name' => 'Purple Top White Globe'],
                ['name' => 'Tokyo Cross'],
            ],
            'Beet' => [
                ['name' => 'Detroit Dark Red'],
                ['name' => 'Chioggia'],
                ['name' => 'Golden Beet'],
            ],

            // ── Fruiting Vegetables ───────────────────────────────────────────
            'Tomato' => [
                ['name' => 'Green Big-Jumbo'],
                ['name' => 'Green Medium'],
                ['name' => 'Half Ripe Big-Jumbo'],
            ],
            'Eggplant' => [
                ['name' => 'Long Purple'],
                ['name' => 'Black Beauty'],
                ['name' => 'Ping Tung Long'],
                ['name' => 'Sinabawanon'],
            ],
            'Cucumber' => [
                ['name' => 'Good'],
                ['name' => 'Semi'],
            ],
            'Bell Pepper' => [
                ['name' => 'California Big'],
                ['name' => 'California Medium'],
                ['name' => 'California Small'],
                ['name' => 'Dongxin (Green) Big'],
                ['name' => 'Dongxin (Red) Big'],
                ['name' => 'Dongxin (Red) Medium'],
                ['name' => 'Sultan Big'],
                ['name' => 'Sultan Medium'],
                ],
            'Lemon' => [
                ['name' => 'Green'],
                ['name' => 'Yelow'],
            ],
            'Chayote' => [
                ['name' => '1st Class'],
                ['name' => '2nd Class'],
                ['name' => 'Prickly Green'],
            ],

            // ── Bean Vegetables ───────────────────────────────────────────────
            'Snap Beans' => [
                ['name' => 'Good'],
                ['name' => 'Semi'],
            ],
            'Garden Peas' => [
                ['name' => 'Chinese'],
            ],
            'String Beans' => [
                ['name' => 'Contender'],
                ['name' => 'Blue Lake'],
                ['name' => 'Jade'],
                ['name' => 'Sitaw Pula'],
            ],
            'Green Beans' => [
                ['name' => 'Kentucky Wonder'],
                ['name' => 'Provider'],
                ['name' => 'Roma II'],
            ],
            'Sugar Snap Peas' => [
                ['name' => 'Super Sugar Snap'],
                ['name' => 'Cascadia'],
                ['name' => 'Sugar Ann'],
            ],
            'Chicharo' => [
                ['name' => 'Benguet Chicharo Local'],
                ['name' => 'Wando'],
            ],
            'Broad Beans' => [
                ['name' => 'Aquadulce Claudia'],
                ['name' => 'The Sutton'],
            ],
        ];

        foreach ($data as $vegetableName => $varieties) {
            $vegetable = Vegetable::where('name', $vegetableName)->firstOrFail();

            foreach ($varieties as $variety) {
                Variety::firstOrCreate(
                    ['vegetable_id' => $vegetable->id, 'name' => $variety['name']],
                );
            }
        }
    }
}
