<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

function billingExportQuery(array $projectIds, string $from, string $to): string
{
    return http_build_query(['project_ids' => $projectIds, 'from' => $from, 'to' => $to]);
}

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->client = Client::factory()->for($this->user)->create();
    $this->project = Project::factory()->for($this->client)->create();
});

describe('clients.billing.export', function () {
    it('returns the generated PDF for the owner with valid project_ids', function () {
        Http::fake([
            config('services.pdf.api_url') => Http::response('%PDF-1.4 fake-pdf-content'),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('clients.billing.export', $this->client).'?'.billingExportQuery([$this->project->id], '2026-01', '2026-06'));

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');

        $expectedFilename = Str::slug($this->client->name).'_'.Str::slug($this->project->name).'_2026-01_2026-06.pdf';
        expect($response->headers->get('Content-Disposition'))->toBe('attachment; filename="'.$expectedFilename.'"');
        expect($response->getContent())->toBe('%PDF-1.4 fake-pdf-content');
    });

    it('sends pdfOptions with a footerTemplate and margin, linking to the app URL when the client has no share_token', function () {
        Http::fake([
            config('services.pdf.api_url') => Http::response('%PDF-1.4 fake-pdf-content'),
        ]);

        $this->actingAs($this->user)
            ->get(route('clients.billing.export', $this->client).'?'.billingExportQuery([$this->project->id], '2026-01', '2026-06'))
            ->assertStatus(200);

        Http::assertSent(function ($request) {
            $pdfOptions = $request->data()['pdfOptions'] ?? null;

            expect($pdfOptions)->not->toBeNull();
            expect($pdfOptions['footerTemplate'])->toContain('class="pageNumber"')
                ->toContain('class="totalPages"')
                ->toContain('href="'.config('app.url').'"');
            expect($pdfOptions['margin'])->toHaveKeys(['top', 'bottom', 'left', 'right']);

            return true;
        });
    });

    it('links the footer to shares.show when the client has a share_token', function () {
        $this->client->update(['share_token' => (string) Str::uuid()]);

        Http::fake([
            config('services.pdf.api_url') => Http::response('%PDF-1.4 fake-pdf-content'),
        ]);

        $this->actingAs($this->user)
            ->get(route('clients.billing.export', $this->client).'?'.billingExportQuery([$this->project->id], '2026-01', '2026-06'))
            ->assertStatus(200);

        $expectedHref = route('shares.show', $this->client->share_token);

        Http::assertSent(function ($request) use ($expectedHref) {
            expect($request->data()['pdfOptions']['footerTemplate'])->toContain('href="'.$expectedHref.'"');

            return true;
        });
    });

    it('denies access to a non-owner', function () {
        $otherUser = User::factory()->create();

        Http::fake();

        $this->actingAs($otherUser)
            ->get(route('clients.billing.export', $this->client).'?'.billingExportQuery([$this->project->id], '2026-01', '2026-06'))
            ->assertStatus(403);
    });

    it('returns 404 when a project_id belongs to another client', function () {
        $otherClient = Client::factory()->for($this->user)->create();
        $otherProject = Project::factory()->for($otherClient)->create();

        Http::fake();

        $this->actingAs($this->user)
            ->get(route('clients.billing.export', $this->client).'?'.billingExportQuery([$otherProject->id], '2026-01', '2026-06'))
            ->assertStatus(404);
    });

    it('returns a 502 with a generic message when the PDF service fails', function () {
        Http::fake([
            config('services.pdf.api_url') => Http::response(['error' => 'boom'], 500),
        ]);

        $this->actingAs($this->user)
            ->get(route('clients.billing.export', $this->client).'?'.billingExportQuery([$this->project->id], '2026-01', '2026-06'))
            ->assertStatus(502)
            ->assertJson(['message' => 'La génération du PDF a échoué. Réessaie dans quelques instants.']);
    });

    it('returns a 502 when the PDF service is unreachable', function () {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->actingAs($this->user)
            ->get(route('clients.billing.export', $this->client).'?'.billingExportQuery([$this->project->id], '2026-01', '2026-06'))
            ->assertStatus(502)
            ->assertJson(['message' => 'La génération du PDF a échoué. Réessaie dans quelques instants.']);
    });
});

describe('clients.billing.export.preview', function () {
    it('returns the rendered HTML in the testing environment without calling the PDF service', function () {
        Http::fake();

        $response = $this->actingAs($this->user)
            ->get(route('clients.billing.export.preview', $this->client).'?'.billingExportQuery([$this->project->id], '2026-01', '2026-06'));

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8');

        Http::assertNothingSent();
    });
});

describe('shares.billing.export', function () {
    beforeEach(function () {
        $this->client->update(['share_token' => (string) Str::uuid()]);
    });

    it('returns the generated PDF for a valid share token', function () {
        Http::fake([
            config('services.pdf.api_url') => Http::response('%PDF-1.4 fake-pdf-content'),
        ]);

        $response = $this->get(route('shares.billing.export', $this->client->share_token).'?'.billingExportQuery([$this->project->id], '2026-01', '2026-06'));

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
        expect($response->getContent())->toBe('%PDF-1.4 fake-pdf-content');
    });

    it('links the footer to shares.show with the token used for the export', function () {
        Http::fake([
            config('services.pdf.api_url') => Http::response('%PDF-1.4 fake-pdf-content'),
        ]);

        $this->get(route('shares.billing.export', $this->client->share_token).'?'.billingExportQuery([$this->project->id], '2026-01', '2026-06'))
            ->assertStatus(200);

        $expectedHref = route('shares.show', $this->client->share_token);

        Http::assertSent(function ($request) use ($expectedHref) {
            expect($request->data()['pdfOptions']['footerTemplate'])->toContain('href="'.$expectedHref.'"');

            return true;
        });
    });

    it('returns 404 for an invalid share token', function () {
        Http::fake();

        $this->get(route('shares.billing.export', 'invalid-token').'?'.billingExportQuery([$this->project->id], '2026-01', '2026-06'))
            ->assertStatus(404);
    });

    it('throttles after 10 requests per minute', function () {
        Cache::flush();

        Http::fake([
            config('services.pdf.api_url') => Http::response('%PDF-1.4 fake-pdf-content'),
        ]);

        $url = route('shares.billing.export', $this->client->share_token).'?'.billingExportQuery([$this->project->id], '2026-01', '2026-06');

        foreach (range(1, 10) as $_) {
            $this->get($url)->assertStatus(200);
        }

        $this->get($url)->assertStatus(429);
    });
});
