<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\TimesheetEntry;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->client = Client::factory()->for($this->user)->create();
});

describe('index', function () {
    it('redirects to project creation when the user has no active project and nothing hidden', function () {
        $this->actingAs($this->user)
            ->get(route('projects.index'))
            ->assertRedirect(route('projects.create'));
    });

    it('renders the projects page when an active project exists', function () {
        Project::factory()->for($this->client)->create(['end_date' => null]);

        $this->actingAs($this->user)
            ->get(route('projects.index'))
            ->assertOk();
    });

    it('redirects with_trashed instead of creation when an inactive project is hidden', function () {
        Project::factory()->for($this->client)->inactive()->create();

        $this->actingAs($this->user)
            ->get(route('projects.index'))
            ->assertRedirect(route('projects.index', ['with_trashed' => true]));
    });

    it('filters projects by name search', function () {
        $matching = Project::factory()->for($this->client)->create(['name' => 'Website Redesign', 'end_date' => null]);
        $other = Project::factory()->for($this->client)->create(['name' => 'Mobile App', 'end_date' => null]);

        $response = $this->actingAs($this->user)->get(route('projects.index', ['search' => 'redesign']));

        $response->assertInertia(function ($page) use ($matching, $other) {
            $ids = collect($page->toArray()['props']['projects'])->pluck('id');

            expect($ids)->toContain($matching->id)->not->toContain($other->id);
        });
    });

    it('filters projects by client_id', function () {
        $otherClient = Client::factory()->for($this->user)->create();
        $matching = Project::factory()->for($this->client)->create(['end_date' => null]);
        $other = Project::factory()->for($otherClient)->create(['end_date' => null]);

        $response = $this->actingAs($this->user)->get(route('projects.index', ['client_id' => $this->client->id]));

        $response->assertInertia(function ($page) use ($matching, $other) {
            $ids = collect($page->toArray()['props']['projects'])->pluck('id');

            expect($ids)->toContain($matching->id)->not->toContain($other->id);
        });
    });

    it('sorts projects by daily_rate ascending', function () {
        $cheap = Project::factory()->for($this->client)->create(['daily_rate' => 10000, 'end_date' => null]);
        $expensive = Project::factory()->for($this->client)->create(['daily_rate' => 90000, 'end_date' => null]);

        $response = $this->actingAs($this->user)->get(route('projects.index', ['sort' => 'rate_asc']));

        $response->assertInertia(function ($page) use ($cheap, $expensive) {
            $ids = collect($page->toArray()['props']['projects'])->pluck('id')->all();

            expect(array_search($cheap->id, $ids))->toBeLessThan(array_search($expensive->id, $ids));
        });
    });

    it('filters clients by name search', function () {
        $matching = Client::factory()->for($this->user)->create(['name' => 'Acme Corp']);
        $other = Client::factory()->for($this->user)->create(['name' => 'Globex']);
        Project::factory()->for($this->client)->create(['end_date' => null]);

        $response = $this->actingAs($this->user)->get(route('projects.index', ['search' => 'acme']));

        $response->assertInertia(function ($page) use ($matching, $other) {
            $ids = collect($page->toArray()['props']['clients'])->pluck('id');

            expect($ids)->toContain($matching->id)->not->toContain($other->id);
        });
    });

    it('combines with_trashed and search to find an archived project under an archived client', function () {
        $archivedClient = Client::factory()->for($this->user)->create(['name' => 'Old Client']);
        $matching = Project::factory()->for($archivedClient)->create(['name' => 'Legacy Project', 'end_date' => today()]);
        $archivedClient->delete();
        Project::factory()->for($this->client)->create(['end_date' => null]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.index', ['with_trashed' => true, 'search' => 'legacy']));

        $response->assertInertia(function ($page) use ($matching) {
            $ids = collect($page->toArray()['props']['projects'])->pluck('id');

            expect($ids->all())->toBe([$matching->id]);
        });
    });

    it('lists soft-deleted clients when with_trashed is set', function () {
        $trashedClient = Client::factory()->for($this->user)->create();
        $trashedClient->delete();
        Project::factory()->for($this->client)->create(['end_date' => null]);

        $response = $this->actingAs($this->user)->get(route('projects.index', ['with_trashed' => true]));

        $response->assertInertia(function ($page) use ($trashedClient) {
            $ids = collect($page->toArray()['props']['clients'])->pluck('id');

            expect($ids)->toContain($trashedClient->id);
        });
    });
});

describe('create', function () {
    it('renders the project form', function () {
        $this->actingAs($this->user)
            ->get(route('projects.create'))
            ->assertOk();
    });
});

describe('edit', function () {
    it('renders the project form', function () {
        $project = Project::factory()->for($this->client)->create();

        $this->actingAs($this->user)
            ->get(route('projects.edit', $project))
            ->assertOk();
    });

    it('records the referer as the back destination when it differs from the edit page itself', function () {
        $project = Project::factory()->for($this->client)->create();

        $this->actingAs($this->user)
            ->withHeader('Referer', route('projects.index'))
            ->get(route('projects.edit', $project))
            ->assertOk();

        expect(session("back_url_project_{$project->id}"))->toBe(route('projects.index'));
    });
});

describe('store', function () {
    it('creates a project for an existing client', function () {
        $this->actingAs($this->user)
            ->post(route('projects.store'), [
                'name' => 'New Project',
                'description' => null,
                'max_month_budget' => null,
                'max_total_budget' => null,
                'daily_rate' => 50000,
                'client_id' => $this->client->id,
            ])
            ->assertRedirect(route('projects.index'));

        expect(Project::where('client_id', $this->client->id)->where('name', 'New Project')->exists())->toBeTrue();
    });

    it('creates a new client inline when no client_id is given', function () {
        $this->actingAs($this->user)
            ->post(route('projects.store'), [
                'name' => 'New Project',
                'description' => null,
                'max_month_budget' => null,
                'max_total_budget' => null,
                'daily_rate' => 50000,
                'client_name' => 'Brand New Client',
                'client_rate' => 60000,
            ])
            ->assertRedirect(route('projects.index'));

        $client = Client::where('user_id', $this->user->id)->where('name', 'Brand New Client')->firstOrFail();
        expect(Project::where('client_id', $client->id)->where('name', 'New Project')->exists())->toBeTrue();
    });

    it('rejects a client_id belonging to another user', function () {
        $otherClient = Client::factory()->create();

        $this->actingAs($this->user)
            ->post(route('projects.store'), [
                'name' => 'New Project',
                'description' => null,
                'max_month_budget' => null,
                'max_total_budget' => null,
                'daily_rate' => 50000,
                'client_id' => $otherClient->id,
            ])
            ->assertInvalid(['client_id']);
    });
});

describe('destroy', function () {
    it('permanently deletes an active project without entries', function () {
        $project = Project::factory()->for($this->client)->create(['end_date' => null]);

        $this->actingAs($this->user)
            ->delete(route('projects.destroy', $project))
            ->assertRedirect();

        expect(Project::find($project->id))->toBeNull();
    });

    it('permanently deletes an inactive project without entries', function () {
        $project = Project::factory()->for($this->client)->inactive()->create();

        $this->actingAs($this->user)
            ->delete(route('projects.destroy', $project))
            ->assertRedirect();

        expect(Project::find($project->id))->toBeNull();
    });

    it('refuses to permanently delete a project with timesheet entries', function () {
        $project = Project::factory()->for($this->client)->create();
        TimesheetEntry::factory()->for($project)->create();

        $this->actingAs($this->user)
            ->delete(route('projects.destroy', $project))
            ->assertRedirect();

        expect(Project::find($project->id))->not->toBeNull();
    });

    it('refuses to permanently delete a project with invoices', function () {
        $project = Project::factory()->for($this->client)->create();
        $project->invoices()->create(['amount' => 100000]);

        $this->actingAs($this->user)
            ->delete(route('projects.destroy', $project))
            ->assertRedirect();

        expect(Project::find($project->id))->not->toBeNull();
    });
});

describe('syncEntries', function () {
    it('redirects back for a plain (non-JSON) request', function () {
        $project = Project::factory()->for($this->client)->create(['start_date' => today(), 'end_date' => null]);

        $this->actingAs($this->user)
            ->patch(route('projects.entries.sync', $project), [
                'entries' => [['date' => today()->toDateString(), 'coverage' => 100]],
            ])
            ->assertRedirect();
    });

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

    it('defaults a new entry to billable when the field is omitted', function () {
        $project = Project::factory()->for($this->client)->create(['start_date' => today(), 'end_date' => null]);

        $this->actingAs($this->user)
            ->patchJson(route('projects.entries.sync', $project), [
                'entries' => [['date' => today()->toDateString(), 'coverage' => 100]],
            ])
            ->assertSuccessful();

        expect($project->timesheetEntries()->where('date', today()->toDateString())->first()->billable)->toBeTrue();
    });

    it('persists billable: false when submitted explicitly', function () {
        $project = Project::factory()->for($this->client)->create(['start_date' => today(), 'end_date' => null]);

        $this->actingAs($this->user)
            ->patchJson(route('projects.entries.sync', $project), [
                'entries' => [['date' => today()->toDateString(), 'coverage' => 100, 'billable' => false]],
            ])
            ->assertSuccessful();

        expect($project->timesheetEntries()->where('date', today()->toDateString())->first()->billable)->toBeFalse();
    });

    it('leaves billable untouched on a partial patch that omits it', function () {
        $project = Project::factory()->for($this->client)->create(['start_date' => today(), 'end_date' => null]);
        TimesheetEntry::factory()->for($project)->notBillable()->create(['date' => today()->toDateString(), 'coverage' => 50]);

        $this->actingAs($this->user)
            ->patchJson(route('projects.entries.sync', $project), [
                'entries' => [['date' => today()->toDateString(), 'coverage' => 100]],
            ])
            ->assertSuccessful();

        $entry = $project->timesheetEntries()->where('date', today()->toDateString())->first();

        expect($entry->coverage)->toBe(100)
            ->and($entry->billable)->toBeFalse();
    });

    it('deletes an existing entry when submitted with zero coverage and no title or description', function () {
        $project = Project::factory()->for($this->client)->create(['start_date' => today(), 'end_date' => null]);
        TimesheetEntry::factory()->for($project)->create(['date' => today()->toDateString(), 'coverage' => 50]);

        $this->actingAs($this->user)
            ->patchJson(route('projects.entries.sync', $project), [
                'entries' => [['date' => today()->toDateString(), 'coverage' => 0]],
            ])
            ->assertSuccessful();

        expect($project->timesheetEntries()->where('date', today()->toDateString())->exists())->toBeFalse();
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

    it('leaves daily_rate and max_month_budget untouched when neither value nor date is given', function () {
        // Saving the form for an unrelated reason (e.g. renaming the project) without touching the
        // rate/budget fields at all must never silently change the rate or clear the budget.
        $project = Project::factory()->for($this->client)->create(['daily_rate' => 50000]);
        $project->update(['monthly_budgets' => [today()->toDateString() => 495000]]);

        $this->actingAs($this->user)
            ->put(route('projects.update', $project), [
                'name' => $project->name,
                'client_id' => $this->client->id,
                'start_date' => $project->start_date->toDateString(),
            ])
            ->assertRedirect();

        $project->refresh();

        expect($project->daily_rate)->toBe(50000)
            ->and($project->max_month_budget)->toBe(495000);
    });

    it('dates the new daily_rate and max_month_budget entries independently', function () {
        $project = Project::factory()->for($this->client)->create();
        $originalRate = $project->daily_rate;
        $rateEffectiveDate = today()->subMonth()->toDateString();
        $budgetEffectiveDate = today()->subMonths(2)->toDateString();

        $this->actingAs($this->user)
            ->put(route('projects.update', $project), [
                'name' => $project->name,
                'daily_rate' => 70000,
                'daily_rate_effective_date' => $rateEffectiveDate,
                'max_month_budget' => 495000,
                'monthly_budget_effective_date' => $budgetEffectiveDate,
                'client_id' => $this->client->id,
                'start_date' => $project->start_date->toDateString(),
            ])
            ->assertRedirect();

        $project->refresh();

        // Backdating a rate doesn't retroactively change what was already effective today (the
        // project's creation-time rate, still in place — updating in the past isn't the same as
        // updating from today), and each field keeps its own effective date.
        expect($project->getDailyRateForDate($rateEffectiveDate))->toBe(70000)
            ->and($project->getMonthlyBudgetForDate($budgetEffectiveDate))->toBe(495000)
            ->and($project->daily_rates->has($budgetEffectiveDate))->toBeFalse()
            ->and($project->getDailyRateForDate(today()))->toBe($originalRate);
    });

    it('does not add a new history entry when the submitted rate matches what was already effective on that date', function () {
        $project = Project::factory()->for($this->client)->create(['daily_rate' => 50000]);

        $this->actingAs($this->user)
            ->put(route('projects.update', $project), [
                'name' => $project->name,
                'daily_rate' => 50000,
                'daily_rate_effective_date' => today()->toDateString(),
                'client_id' => $this->client->id,
                'start_date' => $project->start_date->toDateString(),
            ])
            ->assertRedirect();

        expect($project->fresh()->daily_rates)->toHaveCount(1);
    });

    it('defaults daily_rate to 0 when a daily_rate_effective_date is given without one', function () {
        $project = Project::factory()->for($this->client)->create(['daily_rate' => 50000]);
        $effectiveDate = today()->toDateString();

        $this->actingAs($this->user)
            ->put(route('projects.update', $project), [
                'name' => $project->name,
                'daily_rate_effective_date' => $effectiveDate,
                'client_id' => $this->client->id,
                'start_date' => $project->start_date->toDateString(),
            ])
            ->assertRedirect();

        expect($project->fresh()->getDailyRateForDate($effectiveDate))->toBe(0);
    });

    it('defaults daily_rate_effective_date to today when a daily_rate is given without one', function () {
        $project = Project::factory()->for($this->client)->create();

        $this->actingAs($this->user)
            ->put(route('projects.update', $project), [
                'name' => $project->name,
                'daily_rate' => 70000,
                'client_id' => $this->client->id,
                'start_date' => $project->start_date->toDateString(),
            ])
            ->assertRedirect();

        $project->refresh();

        expect($project->daily_rates->get(today()->toDateString()))->toBe(70000)
            ->and($project->daily_rate)->toBe(70000);
    });

    it('defaults monthly_budget_effective_date to today when a max_month_budget is given without one', function () {
        $project = Project::factory()->for($this->client)->create();

        $this->actingAs($this->user)
            ->put(route('projects.update', $project), [
                'name' => $project->name,
                'max_month_budget' => 495000,
                'client_id' => $this->client->id,
                'start_date' => $project->start_date->toDateString(),
            ])
            ->assertRedirect();

        expect($project->fresh()->getMonthlyBudgetForDate(today()))->toBe(495000);
    });

    it('sets the monthly budget to unlimited from the given date when max_month_budget is left empty', function () {
        $project = Project::factory()->for($this->client)->create();
        $project->update(['monthly_budgets' => [today()->subMonths(2)->toDateString() => 495000]]);
        $effectiveDate = today()->toDateString();

        $this->actingAs($this->user)
            ->put(route('projects.update', $project), [
                'name' => $project->name,
                'monthly_budget_effective_date' => $effectiveDate,
                'client_id' => $this->client->id,
                'start_date' => $project->start_date->toDateString(),
            ])
            ->assertRedirect();

        expect($project->fresh()->getMonthlyBudgetForDate($effectiveDate))->toBeNull();
    });
});
