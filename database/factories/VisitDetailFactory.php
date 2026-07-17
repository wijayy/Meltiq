<?php

namespace Database\Factories;

use App\Models\VisitDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisitDetail>
 */
class VisitDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visit_id' => \App\Models\Visit::factory(),
            'product_id' => \App\Models\Product::factory(),
            'stockBefore' => $this->faker->numberBetween(0, 100),
            'physicalStock' => $this->faker->numberBetween(0, 100),
            'returnedQty' => $this->faker->numberBetween(0, 20),
            'expiredQty' => $this->faker->numberBetween(0, 10),
            'newDeliveryQty' => $this->faker->numberBetween(0, 30),
        ];
    }
}
