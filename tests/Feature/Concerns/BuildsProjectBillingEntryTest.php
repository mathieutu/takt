<?php

use App\Http\Concerns\BuildsProjectBillingEntry;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimesheetEntry;
use App\Models\User;
use Carbon\CarbonImmutable;

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

    expect($totals['to_invoice'])->toBe(0);
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
        ->and($totals['total_worked'])->toBe(50000)
        ->and($totals['to_invoice'])->toBe(50000);
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

it('uses the daily rate effective at work time, not the current rate, for an already-invoiced month', function () {
    $january = CarbonImmutable::parse('2026-01-15');
    $march = CarbonImmutable::parse('2026-03-01');

    $project = Project::factory()->for($this->client)->create();
    $project->update(['daily_rates' => [$january->subMonth()->toDateString() => 50000]]);

    TimesheetEntry::factory()->for($project)->create(['date' => $january, 'coverage' => 100]);

    Invoice::factory()->for($project)->create([
        'amount' => 50000,
        'created_at' => $january,
    ]);

    // The rate increases in March, after January was already invoiced at 50000/day.
    $project->update(['daily_rates' => [
        $january->subMonth()->toDateString() => 50000,
        $march->toDateString() => 60000,
    ]]);

    $totals = $this->builder->totals($project->fresh());
    $januaryMonth = collect($totals['months'])->firstWhere('month', '2026-01');

    expect($januaryMonth['daily_rate'])->toBe(50000)
        ->and($januaryMonth['worked'])->toBe(50000)
        ->and($totals['to_invoice'])->toBe(0);
});

it('bills each entry at the rate effective on its own date when the rate changes mid-month', function () {
    $project = Project::factory()->for($this->client)->create();
    $project->update(['daily_rates' => [
        '2025-01-01' => 50000,
        '2026-01-15' => 60000,
    ]]);

    TimesheetEntry::factory()->for($project)->create(['date' => '2026-01-10', 'coverage' => 100]);
    TimesheetEntry::factory()->for($project)->create(['date' => '2026-01-20', 'coverage' => 100]);

    $totals = $this->builder->totals($project->fresh());
    $januaryMonth = collect($totals['months'])->firstWhere('month', '2026-01');

    // One day at the old rate (50000) + one day at the new rate (60000), not 2 days at either rate.
    expect($januaryMonth['worked'])->toBe(110000);
});

it('weights invoiced days by each month\'s own rate rather than the current rate', function () {
    $project = Project::factory()->for($this->client)->create();
    $project->update(['daily_rates' => [
        '2025-01-01' => 50000,
        '2026-02-01' => 100000,
    ]]);

    // 50000 invoiced in January (at 50000/day = 1 day) and 100000 invoiced in February (at
    // 100000/day = 1 day): 2 days total. Dividing the combined 150000 by the current rate
    // (100000/day) would wrongly read as 1.5 days.
    Invoice::factory()->for($project)->create(['amount' => 50000, 'created_at' => '2026-01-15']);
    Invoice::factory()->for($project)->create(['amount' => 100000, 'created_at' => '2026-02-15']);

    $totals = $this->builder->totals($project->fresh());

    expect($totals['total_invoiced_days'])->toBe(2.0);
});

it('anchors unbilled_since on the balance crossing back into debt, not on an old advance invoice', function () {
    $project = Project::factory()->for($this->client)->create(['daily_rate' => 50000]);

    // A single day worked, then an invoice paid in advance of any further work: the balance goes
    // negative (client has credit), not just to zero.
    TimesheetEntry::factory()->for($project)->create(['date' => '2025-01-10', 'coverage' => 100]);
    Invoice::factory()->for($project)->create(['amount' => 100000, 'created_at' => '2025-01-15']);

    // Months later, new work finally eats through the advance and tips the balance positive again.
    TimesheetEntry::factory()->for($project)->create(['date' => '2025-06-01', 'coverage' => 100]);
    TimesheetEntry::factory()->for($project)->create(['date' => '2025-06-02', 'coverage' => 100]);

    $totals = $this->builder->totals($project->fresh());

    expect($totals['unbilled_since'])->toBe('2025-06-02');
});

it('anchors unbilled_since on a recent isolated entry, not on a stale last invoice date', function () {
    $project = Project::factory()->for($this->client)->create(['daily_rate' => 50000]);

    // Fully settled long ago: one day worked, one invoice that covers it exactly.
    TimesheetEntry::factory()->for($project)->create(['date' => '2025-01-10', 'coverage' => 100]);
    Invoice::factory()->for($project)->create(['amount' => 50000, 'created_at' => '2025-01-15']);

    // A single, recent day of work on an otherwise dormant project.
    TimesheetEntry::factory()->for($project)->create(['date' => today()->subDay(), 'coverage' => 100]);

    $totals = $this->builder->totals($project->fresh());

    expect($totals['unbilled_since'])->toBe(today()->subDay()->toDateString());
});

it('reports unbilled_since as null when the project is currently paid up or in advance', function () {
    $project = Project::factory()->for($this->client)->create(['daily_rate' => 50000]);

    TimesheetEntry::factory()->for($project)->create(['date' => '2025-01-10', 'coverage' => 100]);
    Invoice::factory()->for($project)->create(['amount' => 100000, 'created_at' => '2025-01-15']);

    $totals = $this->builder->totals($project->fresh());

    expect($totals['unbilled_since'])->toBeNull();
});
