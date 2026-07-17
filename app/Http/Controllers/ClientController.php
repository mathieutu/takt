<?php

namespace App\Http\Controllers;

use App\Http\Concerns\BuildsProjectBillingEntry;
use App\Http\Concerns\BuildsProjectsPageProps;
use App\Http\Requests\ExportBillingRequest;
use App\Models\Client;
use App\Models\Project;
use App\Services\HolidayService;
use App\Services\PdfGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ClientController
{
    use BuildsProjectBillingEntry, BuildsProjectsPageProps;

    public function edit(Request $request, Client $client): Response
    {
        $request->session()->put("back_url_client_{$client->id}", $request->header('Referer'));

        return Inertia::render('ClientForm', [
            'page' => $this->projectsPageProps($request),
            'modal' => [
                'client' => $client->export([
                    'id',
                    'name',
                    'daily_rate',
                ]),
            ],
        ]);
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'daily_rate' => ['required', 'integer', 'min:0'],
        ]);

        $client->update($data);

        $backUrl = $request->session()->pull('back_url_client_'.$client->id, route('projects.index'));

        return redirect()->to($backUrl)->with('success', 'Client mis à jour avec succès.');
    }

    public function restore(Client $client): RedirectResponse
    {
        $client->restore();

        return redirect()->back()->with('success', 'Client restauré avec succès.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        if (! $client->deleted_at) {
            $client->projects()->update(['end_date' => today()]);
            $client->delete();

            return redirect()->back()->with('success', 'Client et ses projets archivés avec succès.');
        }

        if ($client->projects()->exists()) {
            return redirect()->back()->with('error', 'Impossible de supprimer un client ayant des projets.');
        }

        $client->forceDelete();

        return redirect()->back()->with('success', 'Client supprimé avec succès.');
    }

    public function showBilling(Request $request, Client $client, HolidayService $holidays): Response
    {
        $projects = $client->projects()->orderBy('created_at')->get();

        abort_if($projects->isEmpty(), 404);

        $projects->load(['timesheetEntries', 'invoices']);

        $builtProjects = $projects->map(fn (Project $p) => $this->buildProjectBillingEntryWithTotals($p, $client->name))->values();

        return Inertia::render('ProjectBillingPage', [
            'projects' => $builtProjects,
            'holidays' => $this->buildHolidaysForPeriod($holidays, $builtProjects),
            'is_shared' => false,
        ]);
    }

    public function exportBilling(ExportBillingRequest $request, Client $client, HolidayService $holidays, PdfGenerator $pdf): HttpResponse
    {
        $projects = $this->resolveExportProjects($client, $request);
        $sourceUrl = $client->share_token ? route('shares.show', $client->share_token) : config('app.url');

        return $this->buildBillingExportResponse($client, $projects, $request->user()->name, $request->user()->email, $request->validated('from'), $request->validated('to'), $holidays, $pdf, $sourceUrl);
    }

    public function storeShare(Client $client): RedirectResponse
    {
        if (! $client->share_token) {
            $client->update(['share_token' => (string) Str::uuid()]);
        }

        return back();
    }

    public function destroyShare(Client $client): RedirectResponse
    {
        $client->update(['share_token' => null]);

        return back();
    }
}
