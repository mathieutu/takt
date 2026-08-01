<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\User;

it('renders the api doc page with the user projects', function () {
    $user = User::factory()->create(['api_token' => 'a-token']);
    $client = Client::factory()->for($user)->create();
    $project = Project::factory()->for($client)->create();

    $response = $this->actingAs($user)->get(route('docs.api'));

    $response->assertOk();
    $response->assertInertia(function ($page) use ($project) {
        $ids = collect($page->toArray()['props']['projects'])->pluck('id');

        expect($ids)->toContain($project->id)
            ->and($page->toArray()['props']['api_token'])->toBe('a-token');
    });
});
