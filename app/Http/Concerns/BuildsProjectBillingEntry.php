<?php

namespace App\Http\Concerns;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Services\HolidayService;
use App\Services\PdfGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

trait BuildsProjectBillingEntry
{
    protected function buildProjectBillingEntry(Project $project, ?string $clientNameOverride = null): array
    {
        $invoicesByMonth = $project->invoices
            ->groupBy(fn (Invoice $i) => $i->created_at->format('Y-m'));

        $timesheetMonths = $project->timesheetEntries
            ->groupBy(fn ($e) => $e->date->format('Y-m'));

        $allMonths = $timesheetMonths->keys()
            ->merge($invoicesByMonth->keys())
            ->unique()
            ->sort()
            ->values();

        $months = $allMonths->map(function (string $month) use ($timesheetMonths, $invoicesByMonth) {
            $entries = $timesheetMonths->get($month, collect());

            return [
                'month' => $month,
                'days_worked' => round($entries->sum('coverage') / 100, 2),
                'entries' => $entries
                    ->keyBy(fn ($e) => $e->date->toDateString())
                    ->map(fn ($e) => [
                        'coverage' => $e->coverage,
                        'title' => $e->title ?? '',
                        'description' => $e->description ?? '',
                    ]),
                'invoices' => $invoicesByMonth->get($month, collect())->map(fn (Invoice $i) => [
                    'id' => $i->id,
                    'amount' => $i->amount,
                    'paid_at' => $i->paid_at?->toDateString(),
                    'created_at' => $i->created_at->toDateString(),
                    'notes' => $i->notes,
                ])->values(),
            ];
        });

        $now = CarbonImmutable::now();
        $firstEntry = $project->timesheetEntries->sortBy('date')->first();
        $projectStart = $firstEntry ? $firstEntry->date : $project->start_date;
        $monthsElapsed = max(1, $projectStart->startOfMonth()->diffInMonths($now->startOfMonth()) + 1);

        return [
            'id' => $project->id,
            'name' => $project->name,
            'daily_rate' => $project->daily_rate,
            'max_month_budget' => $project->max_month_budget,
            'max_total_budget' => $project->max_total_budget,
            'is_inactive' => $project->isInactive(),
            'client' => ['id' => $project->client_id, 'name' => $clientNameOverride ?? $project->client->name],
            'months' => $months->values(),
            'months_elapsed' => $monthsElapsed,
            'months_with_entries_count' => $timesheetMonths->count(),
        ];
    }

    /**
     * @param  Collection<int, array{months: array<int, array{month: string}>}>  $builtProjects
     * @return array<string, string>
     */
    protected function buildHolidaysForPeriod(HolidayService $holidays, Collection $builtProjects): array
    {
        $months = $builtProjects->flatMap(fn (array $p) => collect($p['months'])->pluck('month'));

        if ($months->isEmpty()) {
            return [];
        }

        $from = CarbonImmutable::createFromFormat('Y-m-d', $months->min().'-01');
        $to = CarbonImmutable::createFromFormat('Y-m-d', $months->max().'-01')->endOfMonth();

        return $holidays->forPeriod($from, $to);
    }

    /**
     * @param  Collection<int, Project>  $projects  déjà filtrée sur project_ids, avec timesheetEntries+invoices chargées
     */
    protected function buildBillingExportViewData(
        Client $client,
        Collection $projects,
        ?string $providerName,
        ?string $providerEmail,
        string $from,
        string $to,
        HolidayService $holidays,
    ): array {
        $built = $projects->map(fn (Project $p) => $this->buildProjectBillingEntry($p, $client->name))
            ->map(function (array $entry) use ($from, $to) {
                $priorMonths = collect($entry['months'])->filter(fn ($m) => $m['month'] < $from);
                $priorWorked = $priorMonths->sum(fn ($m) => $m['days_worked'] * $entry['daily_rate']);
                $priorInvoiced = $priorMonths->flatMap(fn ($m) => $m['invoices'])->sum('amount');

                return [
                    ...$entry,
                    'opening_to_invoice' => $priorWorked - $priorInvoiced,
                    'months' => collect($entry['months'])->filter(fn ($m) => $m['month'] >= $from && $m['month'] <= $to)->values(),
                ];
            });

        return [
            'projects' => $built,
            'clientName' => $client->name,
            'providerName' => $providerName,
            'providerEmail' => $providerEmail,
            'from' => $from,
            'to' => $to,
            'holidays' => $this->buildHolidaysForPeriod($holidays, $built),
        ];
    }

    /**
     * @param  Collection<int, Project>  $projects  déjà filtrée sur project_ids, avec timesheetEntries+invoices chargées
     */
    protected function buildBillingExportResponse(
        Client $client,
        Collection $projects,
        ?string $providerName,
        ?string $providerEmail,
        string $from,
        string $to,
        HolidayService $holidays,
        PdfGenerator $pdf,
        string $sourceUrl,
    ): Response {
        $viewData = $this->buildBillingExportViewData($client, $projects, $providerName, $providerEmail, $from, $to, $holidays);
        $filename = $this->buildExportFilename($client->name, $viewData['projects'], $from, $to);

        $pdfOptions = [
            'footerTemplate' => view('exports.billing-footer', [
                'linkHref' => $sourceUrl,
                'generatedAt' => now()->translatedFormat('d/m/Y à H:i'),
            ])->render(),
            'margin' => ['top' => '16mm', 'bottom' => '20mm', 'left' => '14mm', 'right' => '14mm'],
        ];

        try {
            $bytes = $pdf->fromView('exports.billing', $viewData, $pdfOptions);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'La génération du PDF a échoué. Réessaie dans quelques instants.'], 502);
        }

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @param  Collection<int, array{name: string}>  $builtProjects
     */
    private function buildExportFilename(string $clientName, Collection $builtProjects, string $from, string $to): string
    {
        $parts = [Str::slug($clientName)];

        if ($builtProjects->count() === 1) {
            $parts[] = Str::slug($builtProjects->first()['name']);
        }

        $parts[] = $from;
        $parts[] = $to;

        return implode('_', $parts).'.pdf';
    }
}
