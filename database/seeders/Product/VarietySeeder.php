<?php

namespace Database\Seeders\Product;

use App\Models\Product\Category;
use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VarietySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'Leafy Vegetables' => [
                'Lettuce' => [
                    ['name' => 'Romaine', 'image_path' => 'varieties/lettuce-romaine.jpg', 'weeks_to_harvest' => 8],
                    ['name' => 'Iceberg', 'image_path' => 'varieties/lettuce-iceberg.jpg', 'weeks_to_harvest' => 10],
                    ['name' => 'Butterhead', 'image_path' => 'varieties/lettuce-butterhead.jpg', 'weeks_to_harvest' => 7],
                    ['name' => 'Leaf', 'image_path' => 'varieties/lettuce-leaf.jpg', 'weeks_to_harvest' => 6],
                ],
                'Spinach' => [
                    ['name' => 'Savoy', 'image_path' => 'varieties/spinach-savoy.jpg', 'weeks_to_harvest' => 6],
                    ['name' => 'Flat Leaf', 'image_path' => 'varieties/spinach-flat.jpg', 'weeks_to_harvest' => 5],
                    ['name' => 'Semi-Savoy', 'image_path' => 'varieties/spinach-semi-savoy.jpg', 'weeks_to_harvest' => 6],
                ],
                'Cabbage' => [
                    ['name' => 'Green', 'image_path' => 'varieties/cabbage-green.jpg', 'weeks_to_harvest' => 12],
                    ['name' => 'Red', 'image_path' => 'varieties/cabbage-red.jpg', 'weeks_to_harvest' => 14],
                    ['name' => 'Napa', 'image_path' => 'varieties/cabbage-napa.jpg', 'weeks_to_harvest' => 10],
                    ['name' => 'Savoy', 'image_path' => 'varieties/cabbage-savoy.jpg', 'weeks_to_harvest' => 13],
                ],
                'Kale' => [
                    ['name' => 'Curly', 'image_path' => 'varieties/kale-curly.jpg', 'weeks_to_harvest' => 8],
                    ['name' => 'Lacinato', 'image_path' => 'varieties/kale-lacinato.jpg', 'weeks_to_harvest' => 9],
                    ['name' => 'Red Russian', 'image_path' => 'varieties/kale-red-russian.jpg', 'weeks_to_harvest' => 8],
                ],
            ],
            'Root Vegetables' => [
                'Carrot' => [
                    ['name' => 'Nantes', 'image_path' => 'varieties/carrot-nantes.jpg', 'weeks_to_harvest' => 10],
                    ['name' => 'Chantenay', 'image_path' => 'varieties/carrot-chantenay.jpg', 'weeks_to_harvest' => 11],
                    ['name' => 'Imperator', 'image_path' => 'varieties/carrot-imperator.jpg', 'weeks_to_harvest' => 12],
                    ['name' => 'Baby', 'image_path' => 'varieties/carrot-baby.jpg', 'weeks_to_harvest' => 8],
                ],
                'Radish' => [
                    ['name' => 'Cherry Belle', 'image_path' => 'varieties/radish-cherry-belle.jpg', 'weeks_to_harvest' => 4],
                    ['name' => 'French Breakfast', 'image_path' => 'varieties/radish-french.jpg', 'weeks_to_harvest' => 4],
                    ['name' => 'Daikon', 'image_path' => 'varieties/radish-daikon.jpg', 'weeks_to_harvest' => 8],
                    ['name' => 'Watermelon', 'image_path' => 'varieties/radish-watermelon.jpg', 'weeks_to_harvest' => 7],
                ],
                'Potato' => [
                    ['name' => 'Russet', 'image_path' => 'varieties/potato-russet.jpg', 'weeks_to_harvest' => 16],
                    ['name' => 'Red', 'image_path' => 'varieties/potato-red.jpg', 'weeks_to_harvest' => 14],
                    ['name' => 'Yukon Gold', 'image_path' => 'varieties/potato-yukon.jpg', 'weeks_to_harvest' => 15],
                    ['name' => 'Fingerling', 'image_path' => 'varieties/potato-fingerling.jpg', 'weeks_to_harvest' => 14],
                ],
                'Sweet Potato' => [
                    ['name' => 'Beauregard', 'image_path' => 'varieties/sweet-potato-beauregard.jpg', 'weeks_to_harvest' => 18],
                    ['name' => 'Jewel', 'image_path' => 'varieties/sweet-potato-jewel.jpg', 'weeks_to_harvest' => 16],
                    ['name' => 'Purple', 'image_path' => 'varieties/sweet-potato-purple.jpg', 'weeks_to_harvest' => 20],
                ],
            ],
            'Fruiting Vegetables' => [
                'Tomato' => [
                    ['name' => 'Beefsteak', 'image_path' => 'varieties/tomato-beefsteak.jpg', 'weeks_to_harvest' => 12],
                    ['name' => 'Cherry', 'image_path' => 'varieties/tomato-cherry.jpg', 'weeks_to_harvest' => 9],
                    ['name' => 'Roma', 'image_path' => 'varieties/tomato-roma.jpg', 'weeks_to_harvest' => 11],
                    ['name' => 'Heirloom', 'image_path' => 'varieties/tomato-heirloom.jpg', 'weeks_to_harvest' => 13],
                    ['name' => 'Grape', 'image_path' => 'varieties/tomato-grape.jpg', 'weeks_to_harvest' => 8],
                ],
                'Bell Pepper' => [
                    ['name' => 'Green', 'image_path' => 'varieties/pepper-green.jpg', 'weeks_to_harvest' => 10],
                    ['name' => 'Red', 'image_path' => 'varieties/pepper-red.jpg', 'weeks_to_harvest' => 12],
                    ['name' => 'Yellow', 'image_path' => 'varieties/pepper-yellow.jpg', 'weeks_to_harvest' => 12],
                    ['name' => 'Orange', 'image_path' => 'varieties/pepper-orange.jpg', 'weeks_to_harvest' => 11],
                ],
                'Eggplant' => [
                    ['name' => 'Black Beauty', 'image_path' => 'varieties/eggplant-black.jpg', 'weeks_to_harvest' => 12],
                    ['name' => 'Japanese', 'image_path' => 'varieties/eggplant-japanese.jpg', 'weeks_to_harvest' => 10],
                    ['name' => 'White', 'image_path' => 'varieties/eggplant-white.jpg', 'weeks_to_harvest' => 11],
                ],
                'Cucumber' => [
                    ['name' => 'Slicing', 'image_path' => 'varieties/cucumber-slicing.jpg', 'weeks_to_harvest' => 8],
                    ['name' => 'Pickling', 'image_path' => 'varieties/cucumber-pickling.jpg', 'weeks_to_harvest' => 7],
                    ['name' => 'English', 'image_path' => 'varieties/cucumber-english.jpg', 'weeks_to_harvest' => 10],
                    ['name' => 'Persian', 'image_path' => 'varieties/cucumber-persian.jpg', 'weeks_to_harvest' => 8],
                ],
            ],
            'Legumes' => [
                'Green Beans' => [
                    ['name' => 'Bush', 'image_path' => 'varieties/beans-bush.jpg', 'weeks_to_harvest' => 8],
                    ['name' => 'Pole', 'image_path' => 'varieties/beans-pole.jpg', 'weeks_to_harvest' => 10],
                    ['name' => 'French', 'image_path' => 'varieties/beans-french.jpg', 'weeks_to_harvest' => 9],
                ],
                'Peas' => [
                    ['name' => 'Snow Peas', 'image_path' => 'varieties/peas-snow.jpg', 'weeks_to_harvest' => 9],
                    ['name' => 'Sugar Snap', 'image_path' => 'varieties/peas-snap.jpg', 'weeks_to_harvest' => 10],
                    ['name' => 'English', 'image_path' => 'varieties/peas-english.jpg', 'weeks_to_harvest' => 11],
                ],
            ],
            'Bulb Vegetables' => [
                'Onion' => [
                    ['name' => 'Yellow', 'image_path' => 'varieties/onion-yellow.jpg', 'weeks_to_harvest' => 14],
                    ['name' => 'Red', 'image_path' => 'varieties/onion-red.jpg', 'weeks_to_harvest' => 15],
                    ['name' => 'White', 'image_path' => 'varieties/onion-white.jpg', 'weeks_to_harvest' => 14],
                    ['name' => 'Green Onion', 'image_path' => 'varieties/onion-green.jpg', 'weeks_to_harvest' => 6],
                ],
                'Garlic' => [
                    ['name' => 'Softneck', 'image_path' => 'varieties/garlic-softneck.jpg', 'weeks_to_harvest' => 24],
                    ['name' => 'Hardneck', 'image_path' => 'varieties/garlic-hardneck.jpg', 'weeks_to_harvest' => 26],
                    ['name' => 'Elephant', 'image_path' => 'varieties/garlic-elephant.jpg', 'weeks_to_harvest' => 28],
                ],
            ],
            'Brassicas' => [
                'Broccoli' => [
                    ['name' => 'Calabrese', 'image_path' => 'varieties/broccoli-calabrese.jpg', 'weeks_to_harvest' => 11],
                    ['name' => 'Sprouting', 'image_path' => 'varieties/broccoli-sprouting.jpg', 'weeks_to_harvest' => 12],
                    ['name' => 'Romanesco', 'image_path' => 'varieties/broccoli-romanesco.jpg', 'weeks_to_harvest' => 13],
                ],
                'Cauliflower' => [
                    ['name' => 'White', 'image_path' => 'varieties/cauliflower-white.jpg', 'weeks_to_harvest' => 12],
                    ['name' => 'Purple', 'image_path' => 'varieties/cauliflower-purple.jpg', 'weeks_to_harvest' => 13],
                    ['name' => 'Orange', 'image_path' => 'varieties/cauliflower-orange.jpg', 'weeks_to_harvest' => 12],
                ],
                'Brussels Sprouts' => [
                    ['name' => 'Long Island', 'image_path' => 'varieties/brussels-long-island.jpg', 'weeks_to_harvest' => 16],
                    ['name' => 'Jade Cross', 'image_path' => 'varieties/brussels-jade.jpg', 'weeks_to_harvest' => 18],
                ],
            ],
            'Squash' => [
                'Zucchini' => [
                    ['name' => 'Green', 'image_path' => 'varieties/zucchini-green.jpg', 'weeks_to_harvest' => 7],
                    ['name' => 'Yellow', 'image_path' => 'varieties/zucchini-yellow.jpg', 'weeks_to_harvest' => 7],
                    ['name' => 'Pattypan', 'image_path' => 'varieties/zucchini-pattypan.jpg', 'weeks_to_harvest' => 8],
                ],
                'Pumpkin' => [
                    ['name' => 'Sugar Pie', 'image_path' => 'varieties/pumpkin-sugar.jpg', 'weeks_to_harvest' => 16],
                    ['name' => 'Jack-o-Lantern', 'image_path' => 'varieties/pumpkin-jack.jpg', 'weeks_to_harvest' => 18],
                    ['name' => 'Miniature', 'image_path' => 'varieties/pumpkin-mini.jpg', 'weeks_to_harvest' => 14],
                ],
                'Butternut Squash' => [
                    ['name' => 'Waltham', 'image_path' => 'varieties/butternut-waltham.jpg', 'weeks_to_harvest' => 15],
                    ['name' => 'Honeynut', 'image_path' => 'varieties/butternut-honeynut.jpg', 'weeks_to_harvest' => 14],
                ],
            ],
        ];

        foreach ($data as $categoryName => $vegetables) {
            $category = Category::firstOrCreate(['name' => $categoryName]);

            foreach ($vegetables as $vegetableName => $varieties) {
                $vegetable = Vegetable::firstOrCreate([
                    'category_id' => $category->id,
                    'name' => $vegetableName,
                ]);

                foreach ($varieties as $variety) {
                    Variety::firstOrCreate(
                        [
                            'vegetable_id' => $vegetable->id,
                            'name' => $variety['name'],
                        ],
                        [
                            'image_path' => $variety['image_path'],
                            'weeks_to_harvest' => $variety['weeks_to_harvest'],
                        ]
                    );
                }
            }
        }

        $this->command->info('Successfully seeded ' . Variety::count() . ' varieties across ' . Vegetable::count() . ' vegetables in ' . Category::count() . ' categories.');
    }
}
