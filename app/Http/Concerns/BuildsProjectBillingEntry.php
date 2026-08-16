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

        $firstEntry = $project->timesheetEntries->sortBy('date')->first();
        $projectStart = $firstEntry ? $firstEntry->date : $project->start_date;

        $months = $allMonths->map(function (string $month) use ($timesheetMonths, $invoicesByMonth, $project, $projectStart) {
            $entries = $timesheetMonths->get($month, collect());
            $monthDate = CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth();
            // `entries` below keeps every entry (billable or not) for calendar/PDF display, but
            // `days_worked` — and `worked` — excludes non-billable entries, since it feeds days ×
            // rate money calculations.
            $daysWorked = round(TimesheetEntry::billableCoverageSum($entries) / 100, 2);
            // Summed per entry (not `daysWorked * a single month rate`) so a rate change effective
            // mid-month still bills each day at the rate that was actually in force that day.
            $worked = $entries->where('billable', true)
                ->sum(fn (TimesheetEntry $e) => (int) round($e->coverage / 100 * $project->getDailyRateForDate($e->date)));

            return [
                'month' => $month,
                'daily_rate' => $project->getDailyRateForDate($monthDate),
                'monthly_budget' => $project->getMonthlyBudgetForDate($monthDate),
                'cumulative_budget' => $project->theoreticalBudgetThrough($projectStart, $monthDate),
                'days_worked' => $daysWorked,
                'worked' => $worked,
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

        return [
            'id' => $project->id,
            'name' => $project->name,
            'daily_rate' => $project->daily_rate,
            'max_month_budget' => $project->max_month_budget,
            'max_total_budget' => $project->max_total_budget,
            'is_inactive' => $project->isInactive(),
            'client' => ['id' => $project->client_id, 'name' => $clientNameOverride ?? $project->client->name],
            'months' => $months->values(),
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
     *     months: Collection<int, array{month: string, daily_rate: int, monthly_budget: ?int, cumulative_budget: ?int, days_worked: float, worked: int, entries: Collection, invoices: Collection}>,
     *     months_with_entries_count: int,
     *     total_days: float,
     *     total_worked: int,
     *     total_invoiced: int,
     *     total_invoiced_days: float,
     *     total_discount: int,
     *     to_invoice: int,
     *     to_invoice_days: float,
     *     to_pay: int,
     *     to_pay_days: float,
     *     unbilled_since: ?string,
     * }
     */
    protected function buildProjectBillingEntryWithTotals(Project $project, ?string $clientNameOverride = null): array
    {
        $entry = $this->buildProjectBillingEntry($project, $clientNameOverride);

        $totals = $this->computeTotals(collect($entry['months']));

        return [
            ...$entry,
            'total_days' => $totals['days'],
            'total_worked' => $totals['worked'],
            'total_invoiced' => $totals['invoiced'],
            'total_invoiced_days' => $totals['invoiced_days'],
            'total_discount' => $totals['discount'],
            'to_invoice' => $totals['to_invoice'],
            'to_invoice_days' => $totals['to_invoice_days'],
            'to_pay' => $totals['to_pay'],
            'to_pay_days' => $totals['to_pay_days'],
            'unbilled_since' => $project->unbilledSince()?->toDateString(),
        ];
    }

    /**
     * Aggregates gross worked/invoiced amounts, discount, and net cash still owed over a set of months.
     *
     * `to_invoice` stays on the gross `amount` (worked − invoiced): a discount is a deliberate write-off
     * on already-invoiced work, not unbilled work. `to_pay` uses `net_amount` on unpaid invoices, since
     * it represents real cash still expected.
     *
     * Each month's `worked` already summed each entry at the daily rate effective on its own date
     * (see buildProjectBillingEntry), so summing it here stays correct even if the project's rate
     * changed mid-month or over time — no phantom delta on days that were worked under an older rate.
     *
     * The `_days` figures are day-equivalents of already-realized amounts (invoiced, still to invoice,
     * still to pay) — there's no literal "days this invoice covers" tracked anywhere (an invoice is a
     * negotiated amount, not days × rate), so each is estimated by dividing by that month's own rate
     * and summing, rather than dividing the aggregate amount by a single (e.g. current) rate — which
     * would misrepresent months invoiced under a different rate.
     *
     * @param  Collection<int, array{daily_rate: int, worked: int, days_worked: float, invoices: Collection<int, array{amount: int, discount_amount: int, net_amount: int, paid_at: ?string}>}>  $months
     * @return array{days: float, worked: int, invoiced: int, invoiced_days: float, discount: int, to_invoice: int, to_invoice_days: float, to_pay: int, to_pay_days: float}
     */
    private function computeTotals(Collection $months): array
    {
        $invoices = $months->flatMap(fn (array $m) => $m['invoices']);

        $worked = $months->sum('worked');
        $invoiced = $invoices->sum('amount');
        $days = $months->sum('days_worked');

        // Cast to float: PHP's `/` returns an int on an evenly-divisible pair, which would otherwise
        // make this figure's type flip between int and float depending on the numbers involved.
        $invoicedDays = (float) $months->sum(fn (array $m) => $m['daily_rate'] > 0
            ? collect($m['invoices'])->sum('amount') / $m['daily_rate']
            : 0);
        $unpaidDays = (float) $months->sum(fn (array $m) => $m['daily_rate'] > 0
            ? collect($m['invoices'])->where('paid_at', null)->sum('net_amount') / $m['daily_rate']
            : 0);

        return [
            'days' => $days,
            'worked' => $worked,
            'invoiced' => $invoiced,
            'invoiced_days' => $invoicedDays,
            'discount' => $invoices->sum('discount_amount'),
            'to_invoice' => $worked - $invoiced,
            'to_invoice_days' => $days - $invoicedDays,
            'to_pay' => $invoices->where('paid_at', null)->sum('net_amount'),
            'to_pay_days' => $unpaidDays,
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
    // up parameters at the two call sites (ClientController::exportBilling, ShareController::export).
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

                $priorTotals = $this->computeTotals($priorMonths);
                $periodTotals = $this->computeTotals($periodMonths);

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
