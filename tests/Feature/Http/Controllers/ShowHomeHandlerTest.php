<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimesheetEntry;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // The dashboard is a full-page Inertia::render() (unlike other controllers tested here, which
    // return redirects/JSON) — it triggers Inertia SSR and an outbound holidays API call, both faked here.
    config(['inertia.ssr.enabled' => false]);
    Http::fake(['calendrier.api.gouv.fr/*' => Http::response([])]);

    $this->user = User::factory()->create();
    $this->client = Client::factory()->for($this->user)->create();
});

it('reflects the net amount (not gross) in kpis.outstandingAmount for a discounted unpaid invoice', function () {
    $project = Project::factory()->for($this->client)->create();
    TimesheetEntry::factory()->for($project)->create(['date' => today(), 'coverage' => 100]);

    Invoice::factory()->for($project)->create([
        'amount' => 100000,
        'discount_amount' => 30000,
        'paid_at' => null,
    ]);

    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('DashboardPage')
            ->where('kpis.outstandingAmount', 70000)
            ->where('kpis.outstandingDiscount', 30000)
        );
});

it('does not affect kpis.outstandingAmount for a non-discounted unpaid invoice', function () {
    $project = Project::factory()->for($this->client)->create();
    TimesheetEntry::factory()->for($project)->create(['date' => today(), 'coverage' => 100]);

    Invoice::factory()->for($project)->create([
        'amount' => 100000,
        'discount_amount' => 0,
        'paid_at' => null,
    ]);

    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('DashboardPage')
            ->where('kpis.outstandingAmount', 100000)
            ->where('kpis.outstandingDiscount', 0)
        );
});

it('reflects the net amount (not gross) in chart.net for a discounted invoice created in the current month, regardless of payment', function () {
    $project = Project::factory()->for($this->client)->create();
    TimesheetEntry::factory()->for($project)->create(['date' => today(), 'coverage' => 100]);

    Invoice::factory()->for($project)->create([
        'amount' => 100000,
        'discount_amount' => 30000,
        'created_at' => today(),
        'paid_at' => null,
    ]);

    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('DashboardPage')
            ->where('chart.net', fn ($net) => $net->last() === 70000)
        );
});

it('reflects the net invoiced amount over the selected period in kpis.periodNetInvoiced, regardless of payment', function () {
    $project = Project::factory()->for($this->client)->create();
    TimesheetEntry::factory()->for($project)->create(['date' => today(), 'coverage' => 100]);

    Invoice::factory()->for($project)->create([
        'amount' => 100000,
        'discount_amount' => 30000,
        'created_at' => today(),
        'paid_at' => null,
    ]);

    // Created outside the selected period — must not leak into the sum.
    Invoice::factory()->for($project)->create([
        'amount' => 999999,
        'discount_amount' => 0,
        'created_at' => today()->subYears(2),
    ]);

    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('DashboardPage')
            ->where('kpis.periodNetInvoiced', 70000)
        );
});

it('does not let projects[].unbilled drift on a 100%-discounted invoice covering all worked amount', function () {
    $project = Project::factory()->for($this->client)->create(['daily_rate' => 50000]);
    TimesheetEntry::factory()->for($project)->create(['date' => today(), 'coverage' => 100]);

    // Gross amount fully covers the 50000 worked; discount write-off doesn't leave work "unbilled".
    Invoice::factory()->for($project)->create([
        'amount' => 50000,
        'discount_amount' => 50000,
        'paid_at' => today(),
        'created_at' => today(),
    ]);

    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('DashboardPage')
            ->where('projects.0.unbilled', 0)
        );
});

it('reflects the net amount actually received within the period in kpis.periodPaid, grouped by payment date', function () {
    $project = Project::factory()->for($this->client)->create();
    TimesheetEntry::factory()->for($project)->create(['date' => today(), 'coverage' => 100]);

    // Issued long ago, paid within the period — must count via paid_at, not created_at.
    Invoice::factory()->for($project)->create([
        'amount' => 100000,
        'discount_amount' => 30000,
        'created_at' => today()->subYears(2),
        'paid_at' => today(),
    ]);

    // Issued within the period but still unpaid — must not count.
    Invoice::factory()->for($project)->create([
        'amount' => 999999,
        'discount_amount' => 0,
        'created_at' => today(),
        'paid_at' => null,
    ]);

    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('DashboardPage')
            ->where('kpis.periodPaid', 70000)
        );
});
