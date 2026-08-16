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

it('renders the landing page for a guest', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('LandingPage'));
});

it('redirects to projects.index when the user has no project matching the selected period', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertRedirect(route('projects.index'));
});

it('honors an explicit from query param instead of the default 11-month lookback', function () {
    $project = Project::factory()->for($this->client)->create(['start_date' => '2020-01-01', 'end_date' => null]);
    TimesheetEntry::factory()->for($project)->create(['date' => '2020-02-15', 'coverage' => 100]);

    $response = $this->actingAs($this->user)->get(route('dashboard', ['from' => '2020-01']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->where('from', '2020-01'));
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

it('excludes non-billable time from kpis.monthRevenue/periodRevenue/projectedRevenue', function () {
    $project = Project::factory()->for($this->client)->create(['daily_rate' => 50000]);
    $pastMonth = today()->subMonth()->startOfMonth();

    // Past month, so monthAdvancement = 1 and projectedRevenue equals monthRevenue exactly.
    TimesheetEntry::factory()->for($project)->create(['date' => $pastMonth->copy(), 'coverage' => 100]);
    TimesheetEntry::factory()->for($project)->notBillable()->create(['date' => $pastMonth->copy()->addDay(), 'coverage' => 100]);

    $this->actingAs($this->user)
        ->get(route('dashboard', ['to' => $pastMonth->format('Y-m')]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('DashboardPage')
            ->where('kpis.monthRevenue', 50000)
            ->where('kpis.periodRevenue', 50000)
            ->where('kpis.projectedRevenue', 50000)
        );
});

it('excludes non-billable amounts from projects[].unbilled, kpis.unbilledAmount, and unbilledByClient', function () {
    $project = Project::factory()->for($this->client)->create(['daily_rate' => 50000]);
    TimesheetEntry::factory()->for($project)->create(['date' => today(), 'coverage' => 100]);
    TimesheetEntry::factory()->for($project)->notBillable()->create(['date' => today()->subDay(), 'coverage' => 100]);

    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('DashboardPage')
            ->where('projects.0.unbilled', 50000)
            ->where('kpis.unbilledAmount', 50000)
            ->where('unbilledByClient.0.amount', 50000)
        );
});

it('excludes non-billable coverage from projects[].workedDaysCount/periodDaysCount/monthDaysCount', function () {
    $project = Project::factory()->for($this->client)->create();
    TimesheetEntry::factory()->for($project)->create(['date' => today(), 'coverage' => 100]);
    TimesheetEntry::factory()->for($project)->notBillable()->create(['date' => today()->subDay(), 'coverage' => 100]);

    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('DashboardPage')
            ->where('projects.0.workedDaysCount', 1)
            ->where('projects.0.periodDaysCount', 1)
            ->where('projects.0.monthDaysCount', 1)
        );
});

it('still counts non-billable coverage in kpis.monthDays, a pure worked-days tracker', function () {
    $project = Project::factory()->for($this->client)->create();
    TimesheetEntry::factory()->for($project)->notBillable()->create(['date' => today(), 'coverage' => 100]);

    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('DashboardPage')
            ->where('kpis.monthDays', 1)
        );
});

it('still counts non-billable coverage in chart.projects, an activity chart rather than a revenue one', function () {
    $project = Project::factory()->for($this->client)->create();
    TimesheetEntry::factory()->for($project)->notBillable()->create(['date' => today(), 'coverage' => 100]);

    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('DashboardPage')
            ->where('chart.projects.0.data', fn ($data) => collect($data)->last() === 100)
        );
});

it('orders both projects[] and chart.projects[] by last timesheet entry date, most recent first', function () {
    $stale = Project::factory()->for($this->client)->create(['name' => 'Stale']);
    TimesheetEntry::factory()->for($stale)->create(['date' => today()->subDays(10), 'coverage' => 100]);

    $fresh = Project::factory()->for($this->client)->create(['name' => 'Fresh']);
    TimesheetEntry::factory()->for($fresh)->create(['date' => today(), 'coverage' => 100]);

    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('DashboardPage')
            ->where('projects.0.name', 'Fresh')
            ->where('projects.1.name', 'Stale')
            ->where('chart.projects.0.name', 'Fresh')
            ->where('chart.projects.1.name', 'Stale')
        );
});

it('filters kpis, chart and projects[] down to a single project when project_id is set, without touching projectOptions', function () {
    $projectA = Project::factory()->for($this->client)->create(['name' => 'A', 'daily_rate' => 50000]);
    TimesheetEntry::factory()->for($projectA)->create(['date' => today(), 'coverage' => 100]);

    $projectB = Project::factory()->for($this->client)->create(['name' => 'B', 'daily_rate' => 100000]);
    TimesheetEntry::factory()->for($projectB)->create(['date' => today(), 'coverage' => 100]);

    $this->actingAs($this->user)
        ->get(route('dashboard', ['project_id' => $projectA->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('DashboardPage')
            ->where('kpis.monthRevenue', 50000)
            ->where('projects', fn ($projects) => $projects->count() === 1 && $projects->first()['name'] === 'A')
            ->where('chart.projects', fn ($chartProjects) => $chartProjects->count() === 1 && $chartProjects->first()['name'] === 'A')
            ->where('projectOptions', fn ($options) => $options->count() === 2)
        );
});

it('filters kpis, chart and projects[] down to a single client when client_id is set', function () {
    $otherClient = Client::factory()->for($this->user)->create();

    $project = Project::factory()->for($this->client)->create(['daily_rate' => 50000]);
    TimesheetEntry::factory()->for($project)->create(['date' => today(), 'coverage' => 100]);

    $otherProject = Project::factory()->for($otherClient)->create(['daily_rate' => 100000]);
    TimesheetEntry::factory()->for($otherProject)->create(['date' => today(), 'coverage' => 100]);

    $this->actingAs($this->user)
        ->get(route('dashboard', ['client_id' => $this->client->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('DashboardPage')
            ->where('kpis.monthRevenue', 50000)
            ->where('projects', fn ($projects) => $projects->count() === 1)
        );
});

it('sorts projects[] by name when sort=name_asc, without affecting the default activity-based order otherwise', function () {
    $stale = Project::factory()->for($this->client)->create(['name' => 'Zebra']);
    TimesheetEntry::factory()->for($stale)->create(['date' => today()->subDays(10), 'coverage' => 100]);

    $fresh = Project::factory()->for($this->client)->create(['name' => 'Alpha']);
    TimesheetEntry::factory()->for($fresh)->create(['date' => today(), 'coverage' => 100]);

    $this->actingAs($this->user)
        ->get(route('dashboard', ['sort' => 'name_asc']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('DashboardPage')
            ->where('projects.0.name', 'Alpha')
            ->where('projects.1.name', 'Zebra')
        );
});

it('sorts projects[] by daily rate when sort=rate_desc', function () {
    $cheap = Project::factory()->for($this->client)->create(['name' => 'Cheap', 'daily_rate' => 10000]);
    TimesheetEntry::factory()->for($cheap)->create(['date' => today(), 'coverage' => 100]);

    $expensive = Project::factory()->for($this->client)->create(['name' => 'Expensive', 'daily_rate' => 90000]);
    TimesheetEntry::factory()->for($expensive)->create(['date' => today(), 'coverage' => 100]);

    $this->actingAs($this->user)
        ->get(route('dashboard', ['sort' => 'rate_desc']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('DashboardPage')
            ->where('projects.0.name', 'Expensive')
            ->where('projects.1.name', 'Cheap')
        );
});

it('sorts projects[] by month amount when sort=budget_desc, falling back to total worked amount on a tie', function () {
    $pastMonth = today()->subMonth()->startOfMonth();

    // Same month amount (0, no entry this month) for both — tie broken by total worked amount.
    $moreWorkedOverall = Project::factory()->for($this->client)->create(['name' => 'MoreWorkedOverall', 'daily_rate' => 50000]);
    TimesheetEntry::factory()->for($moreWorkedOverall)->create(['date' => $pastMonth->copy(), 'coverage' => 200]);

    $lessWorkedOverall = Project::factory()->for($this->client)->create(['name' => 'LessWorkedOverall', 'daily_rate' => 50000]);
    TimesheetEntry::factory()->for($lessWorkedOverall)->create(['date' => $pastMonth->copy(), 'coverage' => 100]);

    // Highest month amount this month — must rank first regardless of the above.
    $activeThisMonth = Project::factory()->for($this->client)->create(['name' => 'ActiveThisMonth', 'daily_rate' => 50000]);
    TimesheetEntry::factory()->for($activeThisMonth)->create(['date' => today(), 'coverage' => 100]);

    $this->actingAs($this->user)
        ->get(route('dashboard', ['sort' => 'budget_desc']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('DashboardPage')
            ->where('projects.0.name', 'ActiveThisMonth')
            ->where('projects.1.name', 'MoreWorkedOverall')
            ->where('projects.2.name', 'LessWorkedOverall')
        );
});
