<?php

namespace App\Http\Concerns;

use App\Http\Requests\ExportBillingRequest;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimesheetEntry;
use App\Services\HolidayService;
use App\Services\PdfGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

trait BuildsProjectBillingEntry
{
    /**
     * @return Collection<int, Project>
     */
    protected function resolveExportProjects(Client $client, ExportBillingRequest $request): Collection
    {
        $projectIds = $request->validated('project_ids');
        $projects = $client->projects()->whereIn('id', $projectIds)->orderedByEndDateThenName()
            ->with(['timesheetEntries', 'invoices'])->get();
        abort_if($projects->count() !== count($projectIds), 404);

        return $projects;
    }

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
                // `entries` below keeps every entry (billable or not) for calendar/PDF display, but
                // `days_worked` — and every total derived from it (total_days, worked, to_invoice, to_pay) —
                // excludes non-billable entries, since it feeds days × rate money calculations.
                'days_worked' => round(TimesheetEntry::billableCoverageSum($entries) / 100, 2),
                'entries' => $entries
                    ->keyBy(fn ($e) => $e->date->toDateString())
                    ->map(fn ($e) => [
                        'coverage' => $e->coverage,
                        'billable' => (bool) $e->billable,
                        'title' => $e->title ?? '',
                        'description' => $e->description ?? '',
                    ]),
                'invoices' => $invoicesByMonth->get($month, collect())->map(fn (Invoice $i) => [
                    'id' => $i->id,
                    'amount' => $i->amount,
                    'discount_amount' => $i->discount_amount,
                    'discount_percent' => $i->discountPercentForDisplay(),
                    'net_amount' => $i->netAmount(),
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
     * @return array{
     *     id: string,
     *     name: string,
     *     daily_rate: int,
     *     max_month_budget: ?int,
     *     max_total_budget: ?int,
     *     is_inactive: bool,
     *     client: array{id: string, name: string},
     *     months: Collection<int, array<string, mixed>>,
     *     months_elapsed: int,
     *     months_with_entries_count: int,
     *     total_days: float,
     *     total_worked: float,
     *     total_invoiced: int,
     *     total_discount: int,
     *     to_invoice: float,
     *     to_pay: int,
     * }
     */
    protected function buildProjectBillingEntryWithTotals(Project $project, ?string $clientNameOverride = null): array
    {
        $entry = $this->buildProjectBillingEntry($project, $clientNameOverride);

        $totals = $this->computeTotals(collect($entry['months']), $entry['daily_rate']);

        return [
            ...$entry,
            'total_days' => $totals['days'],
            'total_worked' => $totals['worked'],
            'total_invoiced' => $totals['invoiced'],
            'total_discount' => $totals['discount'],
            'to_invoice' => $totals['to_invoice'],
            'to_pay' => $totals['to_pay'],
        ];
    }

    /**
     * Aggregates gross worked/invoiced amounts, discount, and net cash still owed over a set of months.
     *
     * `to_invoice` stays on the gross `amount` (worked − invoiced): a discount is a deliberate write-off
     * on already-invoiced work, not unbilled work. `to_pay` uses `net_amount` on unpaid invoices, since
     * it represents real cash still expected.
     *
     * @param  Collection<int, array{days_worked: float, invoices: Collection<int, array{amount: int, discount_amount: int, net_amount: int, paid_at: ?string}>}>  $months
     * @return array{days: float, worked: float, invoiced: int, discount: int, to_invoice: float, to_pay: int}
     */
    private function computeTotals(Collection $months, int $dailyRate): array
    {
        $invoices = $months->flatMap(fn (array $m) => $m['invoices']);

        $worked = $months->sum(fn (array $m) => $m['days_worked'] * $dailyRate);
        $invoiced = $invoices->sum('amount');

        return [
            'days' => $months->sum('days_worked'),
            'worked' => $worked,
            'invoiced' => $invoiced,
            'discount' => $invoices->sum('discount_amount'),
            'to_invoice' => $worked - $invoiced,
            'to_pay' => $invoices->where('paid_at', null)->sum('net_amount'),
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
     * @param  Collection<int, Project>  $projects  Already filtered on project_ids, with timesheetEntries+invoices loaded
     * @return array{
     *     projects: Collection<int, array<string, mixed>>,
     *     clientName: string,
     *     providerName: ?string,
     *     providerEmail: ?string,
     *     from: string,
     *     to: string,
     *     holidays: array<string, string>,
     * }
     */
    // TODO: providerName/providerEmail/from/to/sourceUrl are threaded positionally through this method and
    // buildBillingExportResponse() below — a small DTO (e.g. ExportContext) would remove the risk of mixing
    // up parameters at the two call sites (ClientController::exportBilling, ExportSharedBillingHandler).
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
                $allMonths = collect($entry['months']);
                $priorMonths = $allMonths->filter(fn ($m) => $m['month'] < $from);
                $periodMonths = $allMonths->filter(fn ($m) => $m['month'] >= $from && $m['month'] <= $to)->values();

                $priorTotals = $this->computeTotals($priorMonths, $entry['daily_rate']);
                $periodTotals = $this->computeTotals($periodMonths, $entry['daily_rate']);

                return [
                    ...$entry,
                    'opening_to_invoice' => $priorTotals['to_invoice'],
                    'months' => $periodMonths,
                    'total_days' => $periodTotals['days'],
                    'total_worked' => $periodTotals['worked'],
                    'total_invoiced' => $periodTotals['invoiced'],
                    'total_discount' => $periodTotals['discount'],
                    'to_invoice' => $periodTotals['to_invoice'],
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
     * @param  Collection<int, Project>  $projects  Already filtered on project_ids, with timesheetEntries+invoices loaded
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
