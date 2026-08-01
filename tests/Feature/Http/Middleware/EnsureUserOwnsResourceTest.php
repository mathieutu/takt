<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->client = Client::factory()->for($this->owner)->create();
    $this->project = Project::factory()->for($this->client)->create();
    $this->invoice = Invoice::factory()->for($this->project)->create();

    $this->intruder = User::factory()->create();
});

describe('clients', function () {
    it('forbids editing a client owned by another user', function () {
        $this->actingAs($this->intruder)
            ->get(route('clients.edit', $this->client))
            ->assertForbidden();
    });

    it('forbids updating a client owned by another user', function () {
        $this->actingAs($this->intruder)
            ->put(route('clients.update', $this->client), ['name' => 'Hacked', 'daily_rate' => 0])
            ->assertForbidden();

        expect($this->client->fresh()->name)->not->toBe('Hacked');
    });

    it('forbids destroying a client owned by another user', function () {
        $this->actingAs($this->intruder)
            ->delete(route('clients.destroy', $this->client))
            ->assertForbidden();

        expect(Client::find($this->client->id))->not->toBeNull();
    });

    it('forbids viewing the billing page of a client owned by another user', function () {
        $this->actingAs($this->intruder)
            ->get(route('clients.billing.show', $this->client))
            ->assertForbidden();
    });
});

describe('projects', function () {
    it('forbids editing a project owned by another user', function () {
        $this->actingAs($this->intruder)
            ->get(route('projects.edit', $this->project))
            ->assertForbidden();
    });

    it('forbids updating a project owned by another user', function () {
        $this->actingAs($this->intruder)
            ->put(route('projects.update', $this->project), [
                'name' => 'Hacked',
                'client_id' => $this->client->id,
                'start_date' => $this->project->start_date->toDateString(),
            ])
            ->assertForbidden();

        expect($this->project->fresh()->name)->not->toBe('Hacked');
    });

    it('forbids destroying a project owned by another user', function () {
        $this->actingAs($this->intruder)
            ->delete(route('projects.destroy', $this->project))
            ->assertForbidden();

        expect(Project::find($this->project->id))->not->toBeNull();
    });

    it('forbids duplicating a project owned by another user', function () {
        $this->actingAs($this->intruder)
            ->post(route('projects.duplicate', $this->project))
            ->assertForbidden();
    });

    it('forbids syncing entries on a project owned by another user', function () {
        $this->actingAs($this->intruder)
            ->patchJson(route('projects.entries.sync', $this->project), [
                'entries' => [['date' => today()->toDateString(), 'coverage' => 100]],
            ])
            ->assertForbidden();
    });
});

describe('invoices', function () {
    it('forbids updating an invoice on a project owned by another user', function () {
        $this->actingAs($this->intruder)
            ->put(route('invoices.update', $this->invoice), [
                'amount' => $this->invoice->amount,
                'discount_amount' => 0,
                'created_at' => today()->toDateString(),
            ])
            ->assertForbidden();
    });

    it('forbids destroying an invoice on a project owned by another user', function () {
        $this->actingAs($this->intruder)
            ->delete(route('invoices.destroy', $this->invoice))
            ->assertForbidden();

        expect(Invoice::find($this->invoice->id))->not->toBeNull();
    });

    it('forbids creating an invoice on a project owned by another user', function () {
        $this->actingAs($this->intruder)
            ->post(route('invoices.store', $this->project), [
                'amount' => 100000,
                'discount_amount' => 0,
                'created_at' => today()->toDateString(),
            ])
            ->assertForbidden();

        expect(Invoice::where('project_id', $this->project->id)->count())->toBe(1);
    });
});
