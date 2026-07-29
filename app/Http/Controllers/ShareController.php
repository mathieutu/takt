<?php

namespace App\Http\Controllers;

use App\Http\Concerns\BuildsProjectBillingEntry;
use App\Http\Requests\ExportBillingRequest;
use App\Models\Client;
use App\Models\Project;
use App\Models\SavedShare;
use App\Services\HolidayService;
use App\Services\PdfGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ShareController
{
    use BuildsProjectBillingEntry;

    public function show(string $token, HolidayService $holidays): Response|RedirectResponse
    {
        $client = Client::findByShareTokenOrFail($token);

        $viewer = auth()->user();

        if ($viewer?->id === $client->user_id) {
            return redirect()->route('clients.billing.show', $client);
        }

        $projects = $client->projects()->orderedByEndDateThenName()->get();

        abort_if($projects->isEmpty(), 404);

        $projects->load(['timesheetEntries', 'invoices']);

        $existingSavedShare = $viewer?->shares()->firstWhere('client_id', $client->id);

        $existingSavedShare?->update(['token' => $client->share_token]);

        $builtProjects = $projects->map(fn (Project $p) => $this->buildProjectBillingEntryWithTotals($p, $client->name))->values();

        return Inertia::render('ProjectBillingPage', [
            'shared_by' => $client->user->name,
            'is_shared' => true,
            'projects' => $builtProjects,
            'holidays' => $this->buildHolidaysForPeriod($holidays, $builtProjects),
            'token' => $token,
            'saved_share_id' => $existingSavedShare?->id,
        ]);
    }

    public function export(ExportBillingRequest $request, string $token, HolidayService $holidays, PdfGenerator $pdf): HttpResponse
    {
        $client = Client::findByShareTokenOrFail($token);
        $projects = $this->resolveExportProjects($client, $request);

        $sourceUrl = route('shares.show', $token);

        return $this->buildBillingExportResponse($client, $projects, $client->user->name, null, $request->validated('from'), $request->validated('to'), $holidays, $pdf, $sourceUrl);
    }

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('SharesPage', [
            'received_shares' => $user->shares()
                ->with('client.user', 'client.projects:id,client_id,name')
                ->get()
                ->map->export([
                    'id',
                    'token',
                    'client.name as client_name',
                    'client.user.name as owner_name',
                    'isValid() as is_valid',
                    'created_at as saved_at',
                ]),

            'my_shares' => $user->clients()
                ->where(fn ($q) => $q->whereNotNull('share_token')->orWhereHas('shares'))
                ->with(['shares' => fn ($q) => $q->with('user:id,name')])
                ->get()
                ->map(fn (Client $c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'is_shared' => $c->share_token !== null,
                    'share_url' => $c->shareUrl(),
                    'followers' => $c->shares->map(fn (SavedShare $s) => [
                        'id' => $s->id,
                        'user_name' => $s->user->name,
                        'is_valid' => $s->isValid(),
                        'saved_at' => $s->created_at,
                    ]),
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['token' => ['required', 'uuid']]);

        $client = Client::findByShareTokenOrFail($data['token']);

        abort_if($client->user_id === $request->user()->id, 403);

        $request->user()->shares()->updateOrCreate(
            ['client_id' => $client->id],
            ['token' => $data['token']],
        );

        return back()->with('success', 'Partage enregistré.');
    }

    public function destroy(SavedShare $share): RedirectResponse
    {
        $share->delete();

        return back()->with('success', 'Partage retiré.');
    }
}
