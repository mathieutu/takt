<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->catchPhrase(),
            'daily_rate' => fake()->numberBetween(30000, 80000),
            'client_id' => Client::factory(),
            'start_date' => today(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['start_date' => today()->subMonth(), 'end_date' => today()->subDay()]);
    }
}
