<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\TimesheetEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimesheetEntry>
 */
class TimesheetEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'date' => today(),
            'coverage' => 100,
        ];
    }

    public function notBillable(): static
    {
        return $this->state(['billable' => false]);
    }
}
