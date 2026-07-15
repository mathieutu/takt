<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->client = Client::factory()->for($this->user)->create();
});

describe('destroy', function () {
    it('archives all of a client projects, without deleting them', function () {
        $project = Project::factory()->for($this->client)->create(['end_date' => null]);

        $this->actingAs($this->user)
            ->delete(route('clients.destroy', $this->client))
            ->assertRedirect();

        expect(Project::find($project->id)->end_date->toDateString())->toBe(today()->toDateString());
    });
});
