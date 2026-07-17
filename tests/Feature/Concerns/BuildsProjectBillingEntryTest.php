<?php

use App\Http\Concerns\BuildsProjectBillingEntry;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimesheetEntry;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->client = Client::factory()->for($this->user)->create();

    $this->builder = new class
    {
        use BuildsProjectBillingEntry;

        public function totals(Project $project): array
        {
            return $this->buildProjectBillingEntryWithTotals($project, 'Test Client');
        }
    };
});

it('does not drift to_invoice when a fully-discounted invoice exactly covers the worked amount', function () {
    $project = Project::factory()->for($this->client)->create(['daily_rate' => 50000]);

    TimesheetEntry::factory()->for($project)->create(['date' => today(), 'coverage' => 100]);

    // 1 full day at 50000/day = 50000 worked. The invoice covers it fully (gross), even though
    // the discount write-off means nothing was actually received.
    Invoice::factory()->for($project)->create([
        'amount' => 50000,
        'discount_amount' => 50000,
        'created_at' => today(),
    ]);

    $totals = $this->builder->totals($project);

    expect($totals['to_invoice'])->toBe(0.0);
});

it('excludes the discounted portion of an unpaid invoice from to_pay', function () {
    $project = Project::factory()->for($this->client)->create();

    Invoice::factory()->for($project)->create([
        'amount' => 100000,
        'discount_amount' => 20000,
        'paid_at' => null,
    ]);

    $totals = $this->builder->totals($project);

    expect($totals['to_pay'])->toBe(80000);
});

it('still counts the full net amount in to_pay when the invoice has no discount', function () {
    $project = Project::factory()->for($this->client)->create();

    Invoice::factory()->for($project)->create([
        'amount' => 100000,
        'discount_amount' => 0,
        'paid_at' => null,
    ]);

    $totals = $this->builder->totals($project);

    expect($totals['to_pay'])->toBe(100000);
});

it('excludes a non-billable entry from days_worked/total_days/worked/to_invoice', function () {
    $project = Project::factory()->for($this->client)->create(['daily_rate' => 50000]);

    TimesheetEntry::factory()->for($project)->create(['date' => today(), 'coverage' => 100]);
    TimesheetEntry::factory()->for($project)->notBillable()->create(['date' => today()->subDay(), 'coverage' => 100]);

    $totals = $this->builder->totals($project);
    $month = collect($totals['months'])->firstWhere('month', today()->format('Y-m'));

    expect($month['days_worked'])->toBe(1.0)
        ->and($totals['total_days'])->toBe(1.0)
        ->and($totals['total_worked'])->toBe(50000.0)
        ->and($totals['to_invoice'])->toBe(50000.0);
});

it('keeps a non-billable entry in month.entries, flagged as billable: false', function () {
    $project = Project::factory()->for($this->client)->create();

    TimesheetEntry::factory()->for($project)->notBillable()->create(['date' => today(), 'coverage' => 100]);

    $totals = $this->builder->totals($project);
    $month = collect($totals['months'])->firstWhere('month', today()->format('Y-m'));
    $entry = $month['entries']->get(today()->toDateString());

    expect($entry)->not->toBeNull()
        ->and($entry['billable'])->toBeFalse();
});
