<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\TimesheetEntry;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->client = Client::factory()->for($this->user)->create();
});

describe('destroy', function () {
    it('archives an active project by setting its end date to today', function () {
        $project = Project::factory()->for($this->client)->create(['end_date' => null]);

        $this->actingAs($this->user)
            ->delete(route('projects.destroy', $project))
            ->assertRedirect();

        expect($project->refresh()->end_date->toDateString())->toBe(today()->toDateString());
    });

    it('permanently deletes an already inactive project without entries', function () {
        $project = Project::factory()->for($this->client)->inactive()->create();

        $this->actingAs($this->user)
            ->delete(route('projects.destroy', $project))
            ->assertRedirect();

        expect(Project::find($project->id))->toBeNull();
    });

    it('refuses to permanently delete an inactive project with timesheet entries', function () {
        $project = Project::factory()->for($this->client)->inactive()->create();
        TimesheetEntry::factory()->for($project)->create();

        $this->actingAs($this->user)
            ->delete(route('projects.destroy', $project))
            ->assertRedirect();

        expect(Project::find($project->id))->not->toBeNull();
    });
});

describe('restore', function () {
    it('restores an inactive project by clearing its end date', function () {
        $project = Project::factory()->for($this->client)->inactive()->create();

        $this->actingAs($this->user)
            ->post(route('projects.restore', $project))
            ->assertRedirect();

        expect($project->refresh()->end_date)->toBeNull();
    });
});

describe('syncEntries', function () {
    it('rejects an entry dated before the start date', function () {
        $project = Project::factory()->for($this->client)->create(['start_date' => today(), 'end_date' => null]);

        $this->actingAs($this->user)
            ->patchJson(route('projects.entries.sync', $project), [
                'entries' => [['date' => today()->subDay()->toDateString(), 'coverage' => 100]],
            ])
            ->assertStatus(422);
    });

    it('rejects an entry dated after the end date', function () {
        $project = Project::factory()->for($this->client)->inactive()->create();

        $this->actingAs($this->user)
            ->patchJson(route('projects.entries.sync', $project), [
                'entries' => [['date' => today()->toDateString(), 'coverage' => 100]],
            ])
            ->assertStatus(422);
    });

    it('accepts an entry dated on the end date, even for an already inactive project', function () {
        $project = Project::factory()->for($this->client)->inactive()->create();

        $this->actingAs($this->user)
            ->patchJson(route('projects.entries.sync', $project), [
                'entries' => [['date' => $project->end_date->toDateString(), 'coverage' => 100]],
            ])
            ->assertSuccessful();

        expect($project->timesheetEntries()->where('date', $project->end_date->toDateString())->exists())->toBeTrue();
    });

    it('accepts an entry within the open date range', function () {
        $project = Project::factory()->for($this->client)->create(['start_date' => today(), 'end_date' => null]);

        $this->actingAs($this->user)
            ->patchJson(route('projects.entries.sync', $project), [
                'entries' => [['date' => today()->toDateString(), 'coverage' => 100]],
            ])
            ->assertSuccessful();

        expect($project->timesheetEntries()->where('date', today()->toDateString())->exists())->toBeTrue();
    });
});

describe('duplicate', function () {
    it('keeps the original start_date and resets the end date', function () {
        $project = Project::factory()->for($this->client)->create([
            'start_date' => today()->subMonths(3),
            'end_date' => today()->subDay(),
        ]);

        $this->actingAs($this->user)
            ->post(route('projects.duplicate', $project))
            ->assertRedirect();

        $duplicate = Project::where('id', '!=', $project->id)->where('client_id', $this->client->id)->firstOrFail();

        expect($duplicate->start_date->toDateString())->toBe($project->start_date->toDateString())
            ->and($duplicate->end_date)->toBeNull();
    });
});

describe('update', function () {
    it('validates start_date and end_date', function () {
        $project = Project::factory()->for($this->client)->create();

        $this->actingAs($this->user)
            ->put(route('projects.update', $project), [
                'name' => $project->name,
                'daily_rate' => $project->daily_rate,
                'client_id' => $this->client->id,
                'start_date' => 'not-a-date',
            ])
            ->assertInvalid(['start_date']);
    });
});
