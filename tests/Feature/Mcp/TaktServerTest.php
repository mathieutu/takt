<?php

use App\Mcp\Servers\TaktServer;
use App\Mcp\Tools\BillingRecap;
use App\Mcp\Tools\CreateClient;
use App\Mcp\Tools\CreateInvoice;
use App\Mcp\Tools\CreateProject;
use App\Mcp\Tools\GetProject;
use App\Mcp\Tools\ListClients;
use App\Mcp\Tools\ListEntries;
use App\Mcp\Tools\ListInvoices;
use App\Mcp\Tools\ListProjects;
use App\Mcp\Tools\UpdateClient;
use App\Mcp\Tools\UpdateInvoice;
use App\Mcp\Tools\UpdateProject;
use App\Mcp\Tools\UpsertEntries;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimesheetEntry;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->user = User::factory()->create(['api_token' => 'secret-token']);
    $this->client = Client::factory()->for($this->user)->create(['name' => 'Acme']);
    $this->project = Project::factory()->for($this->client)->create(['name' => 'Refonte', 'start_date' => '2026-01-01']);

    $this->stranger = User::factory()->create();
    $this->strangerClient = Client::factory()->for($this->stranger)->create();
    $this->strangerProject = Project::factory()->for($this->strangerClient)->create(['start_date' => '2026-01-01']);
});

describe('authentication', function () {
    it('rejects an MCP request without a bearer token', function () {
        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize'])->assertUnauthorized();
    });

    it('serves an MCP request authenticated with the user api token', function () {
        $this->withToken('secret-token')
            ->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
            ->assertOk()
            ->assertJsonPath('result.tools.0.name', 'list-clients');
    });
});

describe('clients', function () {
    it('lists only the clients of the authenticated user', function () {
        TaktServer::actingAs($this->user)->tool(ListClients::class)
            ->assertOk()
            ->assertSee('Acme')
            ->assertDontSee($this->strangerClient->name);
    });

    it('creates and updates a client', function () {
        TaktServer::actingAs($this->user)->tool(CreateClient::class, ['name' => 'Globex', 'daily_rate' => 50000])->assertOk();

        $created = $this->user->clients()->where('name', 'Globex')->sole();
        expect($created->daily_rate)->toBe(50000);

        TaktServer::actingAs($this->user)
            ->tool(UpdateClient::class, ['client_id' => $created->id, 'name' => 'Globex SA', 'daily_rate' => 60000])
            ->assertOk();

        expect($created->refresh()->name)->toBe('Globex SA')->and($created->daily_rate)->toBe(60000);
    });

    it('refuses to update the client of another user', function () {
        TaktServer::actingAs($this->user)
            ->tool(UpdateClient::class, ['client_id' => $this->strangerClient->id, 'name' => 'Hijacked', 'daily_rate' => 1])
            ->assertHasErrors(['Client not found.']);

        expect($this->strangerClient->refresh()->name)->not->toBe('Hijacked');
    });

    it('validates client input', function () {
        TaktServer::actingAs($this->user)->tool(CreateClient::class, ['name' => 'No rate'])->assertHasErrors();
    });
});

describe('projects', function () {
    it('lists active projects, and inactive ones on demand, scoped to the user', function () {
        $inactive = Project::factory()->for($this->client)->inactive()->create(['name' => 'Ancien']);

        TaktServer::actingAs($this->user)->tool(ListProjects::class)
            ->assertSee('Refonte')->assertDontSee('Ancien')->assertDontSee($this->strangerProject->name);

        TaktServer::actingAs($this->user)->tool(ListProjects::class, ['include_inactive' => true])
            ->assertSee(['Refonte', $inactive->name]);

        TaktServer::actingAs($this->user)->tool(ListProjects::class, ['client_id' => $this->client->id])
            ->assertSee('Refonte');
    });

    it('creates a project with a past start date', function () {
        TaktServer::actingAs($this->user)->tool(CreateProject::class, [
            'client_id' => $this->client->id,
            'name' => 'Migration',
            'daily_rate' => 45000,
            'start_date' => '2025-09-01',
        ])->assertOk();

        $project = Project::where('name', 'Migration')->sole();
        expect($project->start_date->toDateString())->toBe('2025-09-01')
            ->and($project->daily_rate)->toBe(45000);
    });

    it('refuses to create a project on the client of another user', function () {
        TaktServer::actingAs($this->user)
            ->tool(CreateProject::class, ['client_id' => $this->strangerClient->id, 'name' => 'Nope'])
            ->assertHasErrors(['Client not found.']);

        expect(Project::where('name', 'Nope')->exists())->toBeFalse();
    });

    it('updates a project and records a rate change in its history', function () {
        TaktServer::actingAs($this->user)->tool(UpdateProject::class, [
            'project_id' => $this->project->id,
            'client_id' => $this->client->id,
            'name' => 'Refonte v2',
            'start_date' => '2026-01-01',
            'daily_rate' => 99000,
            'daily_rate_effective_date' => '2026-03-01',
        ])->assertOk();

        $project = $this->project->refresh();
        expect($project->name)->toBe('Refonte v2')
            ->and($project->daily_rates->get('2026-03-01'))->toBe(99000)
            ->and($project->daily_rates->count())->toBe(2);
    });

    it('shows the rate history of a project', function () {
        TaktServer::actingAs($this->user)->tool(GetProject::class, ['project_id' => $this->project->id])
            ->assertOk()->assertSee(['daily_rates', 'entries_count']);

        TaktServer::actingAs($this->user)->tool(GetProject::class, ['project_id' => $this->strangerProject->id])
            ->assertHasErrors(['Project not found.']);
    });
});

describe('entries', function () {
    it('upserts entries across several months in one call', function () {
        TaktServer::actingAs($this->user)->tool(UpsertEntries::class, [
            'project_id' => $this->project->id,
            'entries' => [
                ['date' => '2026-01-15', 'coverage' => 100, 'title' => 'Dev'],
                ['date' => '2026-02-10', 'coverage' => 50],
                ['date' => '2026-03-05', 'coverage' => 25, 'billable' => false],
            ],
        ])->assertOk()->assertSee('2026-02-10');

        expect($this->project->timesheetEntries()->count())->toBe(3)
            ->and($this->project->timesheetEntries()->where('billable', false)->count())->toBe(1);
    });

    it('updates existing days, leaves absent days alone and deletes emptied ones', function () {
        TimesheetEntry::factory()->for($this->project)->create(['date' => '2026-01-10', 'coverage' => 100]);
        TimesheetEntry::factory()->for($this->project)->create(['date' => '2026-01-11', 'coverage' => 100]);
        TimesheetEntry::factory()->for($this->project)->create(['date' => '2026-01-12', 'coverage' => 100]);

        TaktServer::actingAs($this->user)->tool(UpsertEntries::class, [
            'project_id' => $this->project->id,
            'entries' => [
                ['date' => '2026-01-10', 'coverage' => 50],
                ['date' => '2026-01-11', 'coverage' => 0],
            ],
        ])->assertOk();

        $coverages = $this->project->timesheetEntries()->orderBy('date')->pluck('coverage', 'date')->mapWithKeys(fn ($c, $d) => [substr($d, 0, 10) => $c]);
        expect($coverages->all())->toBe(['2026-01-10' => 50, '2026-01-12' => 100]);
    });

    it('writes nothing on a dry run but reports the diff', function () {
        TimesheetEntry::factory()->for($this->project)->create(['date' => '2026-01-10', 'coverage' => 100]);

        TaktServer::actingAs($this->user)->tool(UpsertEntries::class, [
            'project_id' => $this->project->id,
            'dry_run' => true,
            'entries' => [
                ['date' => '2026-01-10', 'coverage' => 0],
                ['date' => '2026-01-11', 'coverage' => 100],
            ],
        ])->assertOk()->assertSee(['"dry_run":true', '2026-01-11']);

        expect($this->project->timesheetEntries()->count())->toBe(1)
            ->and($this->project->timesheetEntries()->value('coverage'))->toBe(100);
    });

    it('rejects the whole batch when one date is outside the project period', function () {
        TaktServer::actingAs($this->user)->tool(UpsertEntries::class, [
            'project_id' => $this->project->id,
            'entries' => [
                ['date' => '2026-01-15', 'coverage' => 100],
                ['date' => '2025-06-01', 'coverage' => 100],
            ],
        ])->assertHasErrors();

        expect($this->project->timesheetEntries()->count())->toBe(0);
    });

    it('refuses batches over the size cap', function () {
        $entries = collect(range(0, UpsertEntries::MAX_ENTRIES))
            ->map(fn (int $i) => ['date' => today()->addDays($i)->toDateString(), 'coverage' => 100])->all();

        TaktServer::actingAs($this->user)
            ->tool(UpsertEntries::class, ['project_id' => $this->project->id, 'entries' => $entries])
            ->assertHasErrors();
    });

    it('refuses to write on the project of another user', function () {
        TaktServer::actingAs($this->user)->tool(UpsertEntries::class, [
            'project_id' => $this->strangerProject->id,
            'entries' => [['date' => '2026-01-15', 'coverage' => 100]],
        ])->assertHasErrors(['Project not found.']);

        expect($this->strangerProject->timesheetEntries()->count())->toBe(0);
    });

    it('lists the entries of a period', function () {
        TimesheetEntry::factory()->for($this->project)->create(['date' => '2026-01-10', 'title' => 'Janvier']);
        TimesheetEntry::factory()->for($this->project)->create(['date' => '2026-03-10', 'title' => 'Mars']);

        TaktServer::actingAs($this->user)
            ->tool(ListEntries::class, ['project_id' => $this->project->id, 'from' => '2026-03-01', 'to' => '2026-03-31'])
            ->assertSee('Mars')->assertDontSee('Janvier');
    });
});

describe('duplicate billing days', function () {
    beforeEach(function () {
        $this->otherProject = Project::factory()->for($this->client)->create(['name' => 'Autre', 'start_date' => '2026-01-01']);
        TimesheetEntry::factory()->for($this->otherProject)->create(['date' => '2026-01-12', 'coverage' => 100, 'billable' => true]);
        TimesheetEntry::factory()->for($this->otherProject)->create(['date' => '2026-01-13', 'coverage' => 100, 'billable' => false]);
    });

    it('warns, on a dry run too, about days already fully billed on another project of the client', function () {
        $entries = [
            ['date' => '2026-01-12', 'coverage' => 50],
            ['date' => '2026-01-13', 'coverage' => 100],
            ['date' => '2026-01-14', 'coverage' => 100],
        ];

        TaktServer::actingAs($this->user)
            ->tool(UpsertEntries::class, ['project_id' => $this->project->id, 'dry_run' => true, 'entries' => $entries])
            ->assertOk()
            ->assertSee(['"duplicate_billing_days":[{"date":"2026-01-12","total_coverage":150', 'Autre', 'Refonte'])
            ->assertDontSee('2026-01-13","total')
            ->assertDontSee('2026-01-14","total');

        expect($this->project->timesheetEntries()->count())->toBe(0);

        TaktServer::actingAs($this->user)
            ->tool(UpsertEntries::class, ['project_id' => $this->project->id, 'entries' => $entries])
            ->assertSee('"date":"2026-01-12","total_coverage":150');

        expect($this->project->timesheetEntries()->count())->toBe(3);
    });

    it('does not warn about non-billable entries or about other clients projects', function () {
        $strangerProject = Project::factory()->for(Client::factory()->for($this->user))->create(['start_date' => '2026-01-01']);
        TimesheetEntry::factory()->for($strangerProject)->create(['date' => '2026-01-20', 'coverage' => 100]);

        TaktServer::actingAs($this->user)->tool(UpsertEntries::class, [
            'project_id' => $this->project->id,
            'entries' => [
                ['date' => '2026-01-12', 'coverage' => 100, 'billable' => false],
                ['date' => '2026-01-20', 'coverage' => 100],
            ],
        ])->assertSee('"duplicate_billing_days":[]');
    });

    it('reports the duplicate days of the period in the billing recap', function () {
        TimesheetEntry::factory()->for($this->project)->create(['date' => '2026-01-12', 'coverage' => 100]);
        TimesheetEntry::factory()->for($this->project)->create(['date' => '2026-03-02', 'coverage' => 100]);
        TimesheetEntry::factory()->for($this->otherProject)->create(['date' => '2026-03-02', 'coverage' => 100]);

        TaktServer::actingAs($this->user)
            ->tool(BillingRecap::class, ['client_id' => $this->client->id, 'from' => '2026-01', 'to' => '2026-01'])
            ->assertSee(['"date":"2026-01-12","total_coverage":200'])
            ->assertDontSee('2026-03-02","total');

        TaktServer::actingAs($this->user)
            ->tool(BillingRecap::class, ['client_id' => $this->client->id, 'from' => '2026-01', 'to' => '2026-03'])
            ->assertSee(['2026-01-12', '2026-03-02']);
    });
});

describe('invoices', function () {
    it('creates, lists and marks an invoice as paid', function () {
        TaktServer::actingAs($this->user)->tool(CreateInvoice::class, [
            'project_id' => $this->project->id,
            'amount' => 100000,
            'discount_amount' => 10000,
            'created_at' => '2026-02-01',
        ])->assertOk();

        $invoice = $this->project->invoices()->sole();

        TaktServer::actingAs($this->user)->tool(ListInvoices::class, ['project_id' => $this->project->id])
            ->assertSee(['"net_amount":90000']);

        TaktServer::actingAs($this->user)->tool(UpdateInvoice::class, [
            'invoice_id' => $invoice->id,
            'amount' => 100000,
            'discount_amount' => 10000,
            'created_at' => '2026-02-01',
            'paid_at' => '2026-02-20',
        ])->assertOk();

        expect($invoice->refresh()->paid_at->toDateString())->toBe('2026-02-20');
    });

    it('validates that the discount does not exceed the amount', function () {
        TaktServer::actingAs($this->user)->tool(CreateInvoice::class, [
            'project_id' => $this->project->id, 'amount' => 100, 'discount_amount' => 200, 'created_at' => '2026-02-01',
        ])->assertHasErrors();
    });

    it('refuses to touch the invoice of another user', function () {
        $invoice = Invoice::factory()->for($this->strangerProject)->create();

        TaktServer::actingAs($this->user)->tool(UpdateInvoice::class, [
            'invoice_id' => $invoice->id, 'amount' => 1, 'discount_amount' => 0, 'created_at' => '2026-02-01',
        ])->assertHasErrors(['Invoice not found.']);

        TaktServer::actingAs($this->user)
            ->tool(CreateInvoice::class, ['project_id' => $this->strangerProject->id, 'amount' => 1, 'discount_amount' => 0, 'created_at' => '2026-02-01'])
            ->assertHasErrors(['Project not found.']);
    });
});

describe('billing recap', function () {
    beforeEach(function () {
        $this->project->update(['daily_rates' => ['2026-01-01' => 50000]]);
        TimesheetEntry::factory()->for($this->project)->create(['date' => '2026-01-10', 'coverage' => 100, 'billable' => true]);
        TimesheetEntry::factory()->for($this->project)->create(['date' => '2026-02-10', 'coverage' => 100, 'billable' => true]);
        TimesheetEntry::factory()->for($this->project)->create(['date' => '2026-02-11', 'coverage' => 100, 'billable' => false]);
    });

    it('returns the period recap as JSON, excluding non-billable days from the money', function () {
        TaktServer::actingAs($this->user)
            ->tool(BillingRecap::class, ['client_id' => $this->client->id, 'from' => '2026-02', 'to' => '2026-02'])
            ->assertOk()
            ->assertSee(['"opening_to_invoice":50000', '"total_worked":50000', '"to_invoice":50000', '"total_worked_days":2', '"total_billable_days":1', 'pdf_url', route('clients.billing.show', $this->client)]);
    });

    it('refuses the recap of a client of another user', function () {
        TaktServer::actingAs($this->user)
            ->tool(BillingRecap::class, ['client_id' => $this->strangerClient->id, 'from' => '2026-01', 'to' => '2026-02'])
            ->assertHasErrors(['Client not found.']);
    });

    it('refuses projects that do not belong to the client', function () {
        TaktServer::actingAs($this->user)
            ->tool(BillingRecap::class, ['client_id' => $this->client->id, 'from' => '2026-01', 'to' => '2026-02', 'project_ids' => [$this->strangerProject->id]])
            ->assertHasErrors(['Project(s) not found for this client.']);
    });

    it('serves the PDF behind the signed url without a session, and nothing once tampered or expired', function () {
        Http::fake([
            'calendrier.api.gouv.fr/*' => Http::response([]),
            config('services.pdf.api_url') => Http::response('%PDF-1.4 fake-pdf-content'),
        ]);

        $response = $this->withToken('secret-token')->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'billing-recap',
                'arguments' => ['client_id' => $this->client->id, 'from' => '2026-01', 'to' => '2026-02'],
            ],
        ])->assertOk();

        $payload = json_decode($response->json('result.content.0.text'), true);
        $url = $payload['pdf_url'];

        $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(str_replace('2026-02', '2026-03', $url))->assertForbidden();

        $this->travel(11)->minutes();
        $this->get($url)->assertForbidden();
    });
});
