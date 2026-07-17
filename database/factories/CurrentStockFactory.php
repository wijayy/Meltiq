<?php

namespace Database\Factories;

use App\Models\CurrentStock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CurrentStock>
 */
class CurrentStockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => \App\Models\Product::factory(),
            'location_id' => \App\Models\Location::factory(),
            'stock' => $this->faker->numberBetween(0, 200),
        ];
    }
}
