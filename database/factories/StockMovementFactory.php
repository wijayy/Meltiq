<?php

namespace Database\Factories;

use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'movement_date' => $this->faker->dateTime(),
            'movement_type' => $this->faker->randomElement(['production', 'transfer', 'sale', 'return', 'expired', 'adjustment']),
            'product_id' => \App\Models\Product::factory(),
            'qty' => $this->faker->numberBetween(1, 100),
            'from_location_id' => \App\Models\Location::factory(),
            'to_location_id' => \App\Models\Location::factory(),
            'reference_no' => $this->faker->optional()->bothify('REF-###'),
            'reference_id' => null,
            'reference_type' => null,
        ];
    }
}
