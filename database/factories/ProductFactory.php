<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'category_id' => Category::factory(),
            'sku' => $this->faker->unique()->bothify('SKU-###'),
            'description' => $this->faker->sentence(),
            'costPrice' => $this->faker->numberBetween(10, 50) * 1000,
            'transferPrice' => $this->faker->numberBetween(15, 60) * 1000,
            'salePrice' => $this->faker->numberBetween(20, 70) * 1000,
        ];
    }
}