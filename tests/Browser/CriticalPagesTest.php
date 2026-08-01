<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake(['calendrier.api.gouv.fr/*' => Http::response([])]);
});

it('loads the dashboard without JavaScript errors', function () {
    $user = User::factory()->create();
    Client::factory()->for($user)->has(Project::factory())->create();

    $this->actingAs($user);

    visit(route('dashboard'))
        ->assertNoJavaScriptErrors();
});

it('loads the projects page without JavaScript errors', function () {
    $user = User::factory()->create();
    Client::factory()->for($user)->has(Project::factory())->create();

    $this->actingAs($user);

    visit(route('projects.index'))
        ->assertNoJavaScriptErrors();
});

it('loads the timesheet page without JavaScript errors', function () {
    $user = User::factory()->create();
    Client::factory()->for($user)->has(Project::factory())->create();

    $this->actingAs($user);

    visit(route('timesheet'))
        ->assertNoJavaScriptErrors();
});

it('loads a client billing page without JavaScript errors', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    Project::factory()->for($client)->create();

    $this->actingAs($user);

    visit(route('clients.billing.show', $client))
        ->assertNoJavaScriptErrors();
});

it('loads the login page without JavaScript errors', function () {
    visit(route('login'))
        ->assertNoJavaScriptErrors();
});
