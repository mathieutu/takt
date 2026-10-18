<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimesheetEntry;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake(['calendrier.api.gouv.fr/*' => Http::response([])]);

    $this->user = User::factory()->create();
    $this->client = Client::factory()->for($this->user)->create();
});

it('renders the timesheet page for the current month by default', function () {
    $this->actingAs($this->user)
        ->get(route('timesheet'))
        ->assertOk();
});

it('lists only projects active during the requested month', function () {
    $activeProject = Project::factory()->for($this->client)->create(['start_date' => today()->subMonth(), 'end_date' => null]);
    $inactiveProject = Project::factory()->for($this->client)->inactive()->create(['start_date' => today()->subYear(), 'end_date' => today()->subMonths(6)]);

    $response = $this->actingAs($this->user)->get(route('timesheet'));

    $response->assertInertia(function ($page) use ($activeProject, $inactiveProject) {
        $ids = collect($page->toArray()['props']['projects'])->pluck('id');

        expect($ids)->toContain($activeProject->id)
            ->not->toContain($inactiveProject->id);
    });
});

it('includes only invoices created or paid within the requested month', function () {
    $project = Project::factory()->for($this->client)->create();
    $matchingInvoice = Invoice::factory()->for($project)->create(['created_at' => today()->toDateString()]);
    Invoice::factory()->for($project)->create(['created_at' => today()->subYear()->toDateString()]);

    $response = $this->actingAs($this->user)->get(route('timesheet'));

    $response->assertInertia(function ($page) use ($matchingInvoice) {
        $ids = collect($page->toArray()['props']['invoices'])->pluck('id');

        expect($ids->all())->toBe([$matchingInvoice->id]);
    });
});

it('embeds the timesheet entries of the requested month keyed by date', function () {
    $project = Project::factory()->for($this->client)->create(['start_date' => today()->subMonth(), 'end_date' => null]);
    $entry = TimesheetEntry::factory()->for($project)->create(['date' => today()->toDateString()]);

    $response = $this->actingAs($this->user)->get(route('timesheet'));

    $response->assertInertia(function ($page) use ($project, $entry) {
        $projectData = collect($page->toArray()['props']['projects'])->firstWhere('id', $project->id);

        expect($projectData['entries'])->toHaveKey($entry->date->toDateString());
    });
});

it('flags whether a project had entries in the previous month', function () {
    $withPreviousActivity = Project::factory()->for($this->client)->create(['start_date' => today()->subYear(), 'end_date' => null]);
    TimesheetEntry::factory()->for($withPreviousActivity)->create(['date' => today()->subMonth()->toDateString()]);
    $withoutPreviousActivity = Project::factory()->for($this->client)->create(['start_date' => today()->subYear(), 'end_date' => null]);

    $response = $this->actingAs($this->user)->get(route('timesheet'));

    $response->assertInertia(function ($page) use ($withPreviousActivity, $withoutPreviousActivity) {
        $projects = collect($page->toArray()['props']['projects']);

        expect($projects->firstWhere('id', $withPreviousActivity->id)['has_previous_month_entries'])->toBeTrue()
            ->and($projects->firstWhere('id', $withoutPreviousActivity->id)['has_previous_month_entries'])->toBeFalse();
    });
});

it('flags, per client, the days billed more than once in the displayed month', function () {
    $a = Project::factory()->for($this->client)->create(['name' => 'A', 'start_date' => '2026-01-01']);
    $b = Project::factory()->for($this->client)->create(['name' => 'B', 'start_date' => '2026-01-01']);
    $otherClientProject = Project::factory()->for(Client::factory()->for($this->user))->create(['start_date' => '2026-01-01']);
    TimesheetEntry::factory()->for($a)->create(['date' => '2026-01-12', 'coverage' => 100]);
    TimesheetEntry::factory()->for($b)->create(['date' => '2026-01-12', 'coverage' => 100]);
    TimesheetEntry::factory()->for($otherClientProject)->create(['date' => '2026-01-12', 'coverage' => 100]);
    TimesheetEntry::factory()->for($a)->create(['date' => '2026-02-02', 'coverage' => 100]);
    TimesheetEntry::factory()->for($b)->create(['date' => '2026-02-02', 'coverage' => 100]);

    $this->actingAs($this->user)
        ->get(route('timesheet', ['month' => '2026-01']))
        ->assertInertia(fn ($page) => $page->where('duplicate_billing_days', [[
            'date' => '2026-01-12',
            'total_coverage' => 200,
            'projects' => ['A' => 100, 'B' => 100],
            'client_id' => $this->client->id,
            'client_name' => $this->client->name,
        ]]));
});
