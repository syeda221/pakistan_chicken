<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Subcategory;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            'Raw Chicken' => ['Whole Chicken', 'Chicken Parts', 'Boneless', 'Keema (Mince)'],
            'Marinated Chicken' => ['Tikka', 'Malai Boti', 'Seekh Kebab', 'Chapli Kebab'],
            'Fried Chicken' => ['Broast', 'Nuggets', 'Wings'],
            'Eggs' => ['Farm Eggs', 'Desi Eggs'],
        ];

        foreach ($data as $categoryName => $subcategories) {
            $category = Category::create(['name' => $categoryName]);

            foreach ($subcategories as $sub) {
                Subcategory::create([
                    'category_id' => $category->id,
                    'name' => $sub,
                ]);
            }
        }
    }
}
