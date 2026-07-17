<?php

namespace Database\Factories;

use App\Models\ProductionDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionDetail>
 */
class ProductionDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'production_id' => \App\Models\Production::factory(),
            'product_id' => \App\Models\Product::factory(),
            'qty' => $this->faker->numberBetween(1, 50),
        ];
    }
}
