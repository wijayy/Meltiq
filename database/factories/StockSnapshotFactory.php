<?php

namespace Database\Factories;

use App\Models\StockSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockSnapshot>
 */
class StockSnapshotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'snapshot_date' => $this->faker->dateTime(),
            'product_id' => \App\Models\Product::factory(),
            'location_id' => \App\Models\Location::factory(),
            'stock' => $this->faker->numberBetween(0, 200),
        ];
    }
}
