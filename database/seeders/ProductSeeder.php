<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Unit;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
          // Sample category
        $category = Category::firstOrCreate(['name' => 'Raw Chicken']);
        $subCategory = Subcategory::firstOrCreate([
            'category_id' => $category->id,
            'name' => 'Whole Chicken'
        ]);

        $unit = Unit::firstOrCreate(['name' => 'KG']);

        // 🔁 Auto-generate item code based on latest ID
        $lastId = Product::max('id') ?? 0;
        $nextId = $lastId + 1;
        $itemCode = 'ITEM-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
        
        // Sample product
        Product::create([
            'creater_id' => 1, // Replace with actual user ID
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'item_code' => $itemCode,
            'unit_id' => $unit->id,
            'unit_type' => 'kg',
            'item_name' => 'Whole Chicken (Without Skin)',
            'price' => 650,
            'alert_quantity' => 10,
            'image' => 'default.png',
            // 'quantity' => 50
        ]);
    }
}
