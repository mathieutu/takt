<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimesheetEntry;
use App\Models\User;
use Illuminate\Support\Facades\Http;

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

    it('records the referer as the back destination when it differs from the edit page itself', function () {
        $this->actingAs($this->user)
            ->withHeader('Referer', route('projects.index'))
            ->get(route('clients.edit', $this->client))
            ->assertOk();

        expect(session("back_url_client_{$this->client->id}"))->toBe(route('projects.index', [], false));
    });

    it('exposes the recorded back destination as a relative path, for Cancel to return there', function () {
        $billingUrl = route('clients.billing.show', $this->client);

        $this->actingAs($this->user)
            ->withHeader('Referer', $billingUrl)
            ->get(route('clients.edit', $this->client))
            ->assertInertia(fn ($page) => $page->where('back_url', route('clients.billing.show', $this->client, false)));
    });

    it('exposes no back_url when none has been recorded yet', function () {
        $this->actingAs($this->user)
            ->get(route('clients.edit', $this->client))
            ->assertInertia(fn ($page) => $page->where('back_url', null));
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

    it('flags the days billed more than once across the client projects', function () {
        Http::fake(['calendrier.api.gouv.fr/*' => Http::response([])]);

        $a = Project::factory()->for($this->client)->create(['name' => 'A', 'start_date' => '2026-01-01']);
        $b = Project::factory()->for($this->client)->create(['name' => 'B', 'start_date' => '2026-01-01']);
        TimesheetEntry::factory()->for($a)->create(['date' => '2026-01-12', 'coverage' => 100]);
        TimesheetEntry::factory()->for($b)->create(['date' => '2026-01-12', 'coverage' => 50]);
        TimesheetEntry::factory()->for($a)->create(['date' => '2026-01-13', 'coverage' => 100]);
        TimesheetEntry::factory()->for($b)->create(['date' => '2026-01-13', 'coverage' => 100, 'billable' => false]);

        $this->actingAs($this->user)
            ->get(route('clients.billing.show', $this->client))
            ->assertInertia(fn ($page) => $page->where('duplicate_billing_days', [
                ['date' => '2026-01-12', 'total_coverage' => 150, 'projects' => ['A' => 100, 'B' => 50]],
            ]));
    });

    it('returns a 404 when the client has no projects', function () {
        $this->actingAs($this->user)
            ->get(route('clients.billing.show', $this->client))
            ->assertNotFound();
    });

    describe('period filter', function () {
        beforeEach(function () {
            Http::fake(['calendrier.api.gouv.fr/*' => Http::response([])]);

            $this->project = Project::factory()->for($this->client)->create(['name' => 'A', 'daily_rate' => 50000, 'start_date' => '2026-01-01']);
            TimesheetEntry::factory()->for($this->project)->create(['date' => '2026-01-12']);
            TimesheetEntry::factory()->for($this->project)->create(['date' => '2026-02-10']);
            TimesheetEntry::factory()->for($this->project)->create(['date' => '2026-03-10']);
            Invoice::factory()->for($this->project)->create(['amount' => 50000, 'discount_amount' => 10000, 'created_at' => '2026-01-31']);
            Invoice::factory()->for($this->project)->create(['amount' => 50000, 'created_at' => '2026-03-31']);
        });

        it('shows the whole history without a period', function () {
            $this->actingAs($this->user)
                ->get(route('clients.billing.show', $this->client))
                ->assertInertia(function ($page) {
                    $project = $page->toArray()['props']['projects'][0];

                    expect($page->toArray()['props']['period'])->toBeNull()
                        ->and(collect($project['months'])->pluck('month')->all())->toBe(['2026-01', '2026-02', '2026-03'])
                        ->and($project['total_worked'])->toBe(150000)
                        ->and($project['opening_consumed'])->toBe(0);
                });
        });

        it('restricts months and totals to the period, but keeps to pay and consumption over the whole history', function () {
            $this->actingAs($this->user)
                ->get(route('clients.billing.show', [$this->client, 'from' => '2026-02', 'to' => '2026-03']))
                ->assertInertia(function ($page) {
                    $props = $page->toArray()['props'];
                    $project = $props['projects'][0];

                    expect($props['period'])->toBe(['from' => '2026-02', 'to' => '2026-03'])
                        ->and(collect($project['months'])->pluck('month')->all())->toBe(['2026-02', '2026-03'])
                        ->and($project['total_worked'])->toBe(100000)
                        ->and($project['total_invoiced'])->toBe(50000)
                        ->and($project['total_discount'])->toBe(0)
                        ->and($project['opening_consumed'])->toBe(40000)
                        ->and($project['consumed'])->toBe(140000)
                        ->and($project['to_invoice'])->toBe(50000)
                        ->and($project['to_pay'])->toBe(90000)
                        ->and($project['unpaid_discount'])->toBe(10000)
                        ->and($project['oldest_unpaid_invoice_date'])->toBe('2026-01-31');
                });
        });

        it('reports what is left to invoice at the end of the period, including what was left before it', function (string $from, string $to, int $openingToInvoice, int $toInvoice) {
            $this->actingAs($this->user)
                ->get(route('clients.billing.show', [$this->client, 'from' => $from, 'to' => $to]))
                ->assertInertia(fn ($page) => expect($page->toArray()['props']['projects'][0])
                    ->opening_to_invoice->toBe($openingToInvoice)
                    ->to_invoice->toBe($toInvoice));
        })->with([
            'invoiced up to the period end' => ['2026-01', '2026-01', 0, 0],
            'unbilled month in the period' => ['2026-02', '2026-02', 0, 50000],
            'carried over from before the period' => ['2026-03', '2026-03', 50000, 50000],
        ]);

        it('leaves out the projects without any month in the period', function () {
            $other = Project::factory()->for($this->client)->create(['name' => 'B', 'start_date' => '2026-01-01']);
            TimesheetEntry::factory()->for($other)->create(['date' => '2026-01-20']);

            $this->actingAs($this->user)
                ->get(route('clients.billing.show', [$this->client, 'from' => '2026-02', 'to' => '2026-03']))
                ->assertInertia(fn ($page) => expect(collect($page->toArray()['props']['projects'])->pluck('name')->all())->toBe(['A']));
        });

        it('only flags the duplicate billing days within the period', function () {
            $other = Project::factory()->for($this->client)->create(['name' => 'B', 'start_date' => '2026-01-01']);
            TimesheetEntry::factory()->for($other)->create(['date' => '2026-01-12']);
            TimesheetEntry::factory()->for($other)->create(['date' => '2026-03-10']);

            $this->actingAs($this->user)
                ->get(route('clients.billing.show', [$this->client, 'from' => '2026-02', 'to' => '2026-03']))
                ->assertInertia(fn ($page) => expect(collect($page->toArray()['props']['duplicate_billing_days'])->pluck('date')->all())->toBe(['2026-03-10']));
        });

        it('falls back to the unfiltered page on an invalid period', function (array $query) {
            $this->actingAs($this->user)
                ->get(route('clients.billing.show', [$this->client, ...$query]))
                ->assertRedirect(route('clients.billing.show', $this->client));
        })->with([
            'bad format' => [['from' => '2026-1', 'to' => '2026-03']],
            'to before from' => [['from' => '2026-03', 'to' => '2026-02']],
            'from without to' => [['from' => '2026-02']],
        ]);
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
            ->assertRedirect(route('projects.index'));

        expect(Project::find($project->id)->end_date->toDateString())->toBe(today()->toDateString());
    });

    it('permanently deletes an already-archived client without projects', function () {
        $this->client->delete();

        $this->actingAs($this->user)
            ->delete(route('clients.destroy', $this->client))
            ->assertRedirect(route('projects.index'));

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

    it('drops the client filter instead of returning to the now-empty filtered projects page', function () {
        $this->client->delete();

        $this->actingAs($this->user)
            ->from(route('projects.index', ['client_id' => $this->client->id]))
            ->delete(route('clients.destroy', $this->client))
            ->assertRedirect(route('projects.index'));
    });
});
