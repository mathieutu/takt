<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimesheetEntry;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('update', function () {
    it('updates the name and email', function () {
        $this->actingAs($this->user)
            ->put(route('profile.update'), ['name' => 'New Name', 'email' => 'new@example.com'])
            ->assertRedirect(route('profile'));

        expect($this->user->fresh())
            ->name->toBe('New Name')
            ->email->toBe('new@example.com');
    });

    it('requires a valid email', function () {
        $this->actingAs($this->user)
            ->put(route('profile.update'), ['name' => 'New Name', 'email' => 'not-an-email'])
            ->assertInvalid(['email']);
    });
});

describe('regenerateToken', function () {
    it('sets a new random api_token', function () {
        $this->user->update(['api_token' => 'old-token']);

        $this->actingAs($this->user)
            ->post(route('profile.tokens.regenerate'))
            ->assertRedirect(route('profile'));

        expect($this->user->fresh()->api_token)
            ->not->toBeNull()
            ->not->toBe('old-token');
    });
});

describe('deleteToken', function () {
    it('clears the api_token', function () {
        $this->user->update(['api_token' => 'some-token']);

        $this->actingAs($this->user)
            ->delete(route('profile.tokens.destroy'))
            ->assertRedirect(route('profile'));

        expect($this->user->fresh()->api_token)->toBeNull();
    });
});

describe('destroy', function () {
    it('deletes the user along with their clients, projects, entries and invoices', function () {
        $client = Client::factory()->for($this->user)->create();
        $project = Project::factory()->for($client)->create();
        TimesheetEntry::factory()->for($project)->create();
        Invoice::factory()->for($project)->create();

        $this->actingAs($this->user)
            ->delete(route('profile.destroy'))
            ->assertRedirect('/');

        expect(User::find($this->user->id))->toBeNull()
            ->and(Client::withTrashed()->find($client->id))->toBeNull()
            ->and(Project::find($project->id))->toBeNull();
    });

    it('logs the user out', function () {
        $this->actingAs($this->user)
            ->delete(route('profile.destroy'));

        expect(auth()->check())->toBeFalse();
    });
});
