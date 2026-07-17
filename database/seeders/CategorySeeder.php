<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productCount = 1;
        foreach (range(1, 5) as $key => $item) {
            $category = Category::factory()->create([
                'name' => 'Category ' . $item,
            ]);
            foreach (range(1, mt_rand(1, 5)) as $key => $item) {
                Product::factory()->recycle($category)->create(['name' => "Product " . $productCount]);
                $productCount++;
            }
        }
    }
}
