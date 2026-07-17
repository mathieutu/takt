<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'amount' => fake()->numberBetween(50000, 500000),
            'discount_amount' => 0,
            'paid_at' => null,
            'notes' => null,
            'created_at' => today(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => ['paid_at' => today()]);
    }

    public function discounted(int $percent): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_amount' => (int) round($attributes['amount'] * $percent / 100),
        ]);
    }
}
