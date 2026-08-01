<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->client = Client::factory()->for($this->user)->create();
});

describe('edit', function () {
    it('renders the client form', function () {
        $this->actingAs($this->user)
            ->get(route('clients.edit', $this->client))
            ->assertOk();
    });
});

describe('update', function () {
    it('updates the name and daily_rate', function () {
        $this->actingAs($this->user)
            ->put(route('clients.update', $this->client), ['name' => 'New Name', 'daily_rate' => 70000])
            ->assertRedirect();

        expect($this->client->fresh())
            ->name->toBe('New Name')
            ->daily_rate->toBe(70000);
    });
});

describe('restore', function () {
    it('restores a soft-deleted client', function () {
        $this->client->delete();

        $this->actingAs($this->user)
            ->post(route('clients.restore', $this->client))
            ->assertRedirect();

        expect(Client::find($this->client->id))->not->toBeNull();
    });
});

describe('showBilling', function () {
    it('renders the billing page when the client has projects', function () {
        Project::factory()->for($this->client)->create();

        $this->actingAs($this->user)
            ->get(route('clients.billing.show', $this->client))
            ->assertOk();
    });

    it('returns a 404 when the client has no projects', function () {
        $this->actingAs($this->user)
            ->get(route('clients.billing.show', $this->client))
            ->assertNotFound();
    });
});

describe('storeShare', function () {
    it('generates a share_token when the client has none', function () {
        $this->actingAs($this->user)
            ->post(route('clients.share.store', $this->client))
            ->assertRedirect();

        expect($this->client->fresh()->share_token)->not->toBeNull();
    });

    it('keeps the existing share_token when one is already set', function () {
        $this->client->update(['share_token' => 'existing-token']);

        $this->actingAs($this->user)
            ->post(route('clients.share.store', $this->client))
            ->assertRedirect();

        expect($this->client->fresh()->share_token)->toBe('existing-token');
    });
});

describe('destroyShare', function () {
    it('clears the share_token', function () {
        $this->client->update(['share_token' => 'existing-token']);

        $this->actingAs($this->user)
            ->delete(route('clients.share.destroy', $this->client))
            ->assertRedirect();

        expect($this->client->fresh()->share_token)->toBeNull();
    });
});

describe('destroy', function () {
    it('archives all of a client projects, without deleting them', function () {
        $project = Project::factory()->for($this->client)->create(['end_date' => null]);

        $this->actingAs($this->user)
            ->delete(route('clients.destroy', $this->client))
            ->assertRedirect();

        expect(Project::find($project->id)->end_date->toDateString())->toBe(today()->toDateString());
    });

    it('permanently deletes an already-archived client without projects', function () {
        $this->client->delete();

        $this->actingAs($this->user)
            ->delete(route('clients.destroy', $this->client))
            ->assertRedirect();

        expect(Client::withTrashed()->find($this->client->id))->toBeNull();
    });

    it('refuses to permanently delete an already-archived client that still has projects', function () {
        $project = Project::factory()->for($this->client)->create(['end_date' => today()]);
        $this->client->delete();

        $this->actingAs($this->user)
            ->delete(route('clients.destroy', $this->client))
            ->assertRedirect();

        expect(Client::withTrashed()->find($this->client->id))->not->toBeNull()
            ->and(Project::find($project->id))->not->toBeNull();
    });
});
