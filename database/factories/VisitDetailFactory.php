<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Visit;
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
            'visit_id' => Visit::factory(),
            'product_id' => Product::factory(),
            'stockBefore' => $this->faker->numberBetween(0, 100),
            'physicalStock' => $this->faker->numberBetween(0, 100),
            'returnedQty' => 0,
            'discountQty' => $this->faker->numberBetween(0, 20),
            'expiredQty' => 0,
            'damagedQty' => $this->faker->numberBetween(0, 10),
            'newDeliveryQty' => $this->faker->numberBetween(0, 30),
        ];
    }
}
