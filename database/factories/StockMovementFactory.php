<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Product;
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
            'movement_type' => $this->faker->randomElement(['production', 'transfer', 'sale', 'discount', 'damaged', 'adjustment']),
            'product_id' => Product::factory(),
            'qty' => $this->faker->numberBetween(1, 100),
            'unit_cost' => $this->faker->numberBetween(5000, 20000),
            'unit_transfer_price' => $this->faker->numberBetween(20000, 30000),
            'unit_sell_price' => $this->faker->numberBetween(30000, 50000),
            'from_location_id' => Location::factory(),
            'to_location_id' => Location::factory(),
            'reference_no' => $this->faker->optional()->bothify('REF-###'),
            'reference_id' => null,
            'reference_type' => null,
        ];
    }
}
