<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\User;

it('returns the clients and projects of the authenticated user only', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create();
    $project = Project::factory()->for($client)->create();

    $otherUser = User::factory()->create();
    $otherClient = Client::factory()->for($otherUser)->create();
    Project::factory()->for($otherClient)->create();

    $response = $this->actingAs($user)->getJson(route('command-bar'));

    $response->assertOk();
    $clientIds = collect($response->json('clients'))->pluck('id');
    $projectIds = collect($response->json('projects'))->pluck('id');

    expect($clientIds->all())->toBe([$client->id])
        ->and($projectIds->all())->toBe([$project->id]);
});
