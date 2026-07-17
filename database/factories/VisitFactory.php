<?php

namespace Database\Factories;

use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Visit>
 */
class VisitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visit_no' => 'VST' . now()->format('Ymd') . '001',
            'visit_date' => $this->faker->date(),
            'notes' => $this->faker->optional()->sentence(),
            'location_id' => \App\Models\Location::factory(),
            'created_by' => \App\Models\User::factory(),
            'status' => $this->faker->randomElement(['pending', 'completed', 'cancelled']),
        ];
    }
}
