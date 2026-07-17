<?php

namespace Database\Factories;

use App\Models\Production;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Production>
 */
class ProductionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'production_no' => 'PRD' . now()->format('Ymd') . '001',
            'production_date' => $this->faker->date(),
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => \App\Models\User::factory(),
        ];
    }
}
