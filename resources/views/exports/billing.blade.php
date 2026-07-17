<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facturation {{ $clientName }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700" rel="stylesheet"/>
    <style>
        @page { size: A4; margin: 16mm 14mm 20mm; }
        html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .project { page-break-before: always; }
        .project:first-of-type { page-break-before: avoid; }
        .month { page-break-inside: avoid; }
        .project > thead > tr > td, .project > tbody > tr > td { vertical-align: top; }
        /* Unicode has no "1/1" vulgar fraction glyph — the plain digit "1" (full day) renders
           visually heavier/larger than the fraction glyphs (½, ⅓...) at the same font-size,
           since those are drawn smaller by font design. Scaled down here to balance the two,
           print-only — coverageLabel() itself stays untouched (shared with the web tooltip).
           `transform: scale()` rather than `font-size` — the latter shrinks the line box itself,
           which throws off the parent flex `items-center` vertical centering; a transform scales
           the glyph visually in place without changing the box flexbox aligns against. */
        [data-coverage="100"] { display: inline-block; transform: scale(0.7); }
    </style>
    {{--
        The compiled app.css below defines Tailwind/Nuxt UI's utility classes (.text-muted,
        .bg-primary/25, etc.) in terms of --ui-* custom properties, but never their actual
        color values — those are injected at runtime by the `ui` Vue plugin (see
        resources/js/app.ts), which this standalone export template never runs. So the
        handful of tokens the template actually uses are set here by hand — light mode
        only, values from Nuxt UI's defaults (primary: pink, others: its base palette).
        Update these if the app's primary color (vite.config.ts, `ui.colors.primary`)
        ever changes.
    --}}
    <style>
        :root {
            --ui-bg: #fff;
            --ui-bg-muted: oklch(98.4% 0.003 247.858);
            --ui-bg-elevated: oklch(96.8% 0.007 247.896);
            --ui-border: oklch(92.9% 0.013 255.508);
            --ui-text: oklch(37.2% 0.044 257.287);
            --ui-text-muted: oklch(55.4% 0.046 257.417);
            --ui-primary: oklch(65.6% 0.241 354.308);
            --ui-success: oklch(72.3% 0.219 149.579);
            --ui-error: oklch(63.7% 0.237 25.331);
        }
    </style>
    <style>{!! \Illuminate\Support\Facades\Vite::content('resources/css/app.css') !!}</style>
</head>
<body class="bg-default font-sans text-sm text-default">
    @php
        $periodFromLabel = \Illuminate\Support\Str::ucfirst(\Carbon\Carbon::createFromFormat('Y-m', $from)->translatedFormat('F Y'));
        $periodToLabel = \Illuminate\Support\Str::ucfirst(\Carbon\Carbon::createFromFormat('Y-m', $to)->translatedFormat('F Y'));
        $periodLabel = $from === $to ? $periodFromLabel : $periodFromLabel.' – '.$periodToLabel;
    @endphp

    @foreach($projects as $project)
        @php
            $totalDays = $project['total_days'];
            $totalWorked = $project['total_worked'];
            $totalInvoiced = $project['total_invoiced'];
            $toInvoice = $project['to_invoice'];
            $openingToInvoice = $project['opening_to_invoice'];
        @endphp

        <table class="project" style="width: 100%; border-collapse: collapse;">
            <thead style="display: table-header-group;">
                <tr>
                    <td class="px-2 pb-5 pt-1">
                        <header class="flex items-start justify-between gap-6 border-b-2 border-primary/30 bg-default pb-4">
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-primary">Facturation</p>
                                <h1 class="mt-0.5 text-xl font-semibold text-default">{{ $project['name'] }}</h1>
                                <p class="mt-0.5 text-xs text-muted">{{ $clientName }}</p>
                            </div>
                            <div class="text-right text-xs text-muted">
                                <span class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">{{ $periodLabel }}</span>
                                @if($providerName)
                                    <p class="mt-2">{{ $providerName }}</p>
                                    @if($providerEmail)
                                        <p>{{ $providerEmail }}</p>
                                    @endif
                                @endif
                            </div>
                        </header>
                    </td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="px-2 pb-1">
                        @if(collect($project['months'])->isEmpty())
                            <p class="text-xs text-muted">Aucune donnée sur la période sélectionnée.</p>
                        @endif

                        @foreach($project['months'] as $month)
                            @php
                                $monthStart = \Carbon\Carbon::createFromFormat('Y-m-d', $month['month'].'-01');
                                $entries = collect($month['entries'] ?? []);
                                $notedDays = $entries->filter(fn ($entry) => trim($entry['title'] ?? '') !== '' || trim($entry['description'] ?? '') !== '');
                            @endphp

                            <section class="month mb-7">
                                <h2 class="mb-2.5 text-sm font-semibold capitalize text-default">{{ $monthStart->translatedFormat('F Y') }}</h2>

                                <div class="mb-2 flex flex-wrap gap-1">
                                    @for($day = 1; $day <= $monthStart->daysInMonth; $day++)
                                        @php
                                            $date = $monthStart->copy()->day($day);
                                            $dateString = $date->toDateString();
                                            $entry = $entries->get($dateString);
                                            $coverage = $entry['coverage'] ?? 0;
                                            $isOff = $date->isWeekend() || array_key_exists($dateString, $holidays ?? []);
                                            // A worked day (coverage > 0) whose entry is billable=false gets a
                                            // distinct hatched look — it must stay visible on the calendar for
                                            // client transparency, but must read differently from billable days
                                            // since it's excluded from days_worked/the footer totals below.
                                            $isNonBillable = $coverage > 0 && ! ($entry['billable'] ?? true);
                                            $cellClass = match(true) {
                                                $isNonBillable => 'bg-muted/30 text-muted',
                                                $coverage >= 100 => 'bg-primary/25 text-primary',
                                                $coverage > 0 => 'bg-primary/10 text-primary',
                                                $isOff => 'bg-elevated/80 text-muted',
                                                default => 'text-muted',
                                            };
                                            $cellStyle = $isNonBillable
                                                ? 'background-image: repeating-linear-gradient(45deg, var(--ui-border) 0, var(--ui-border) 1px, transparent 1px, transparent 5px);'
                                                : '';
                                        @endphp
                                        <div class="flex w-9 flex-col items-center gap-0.5">
                                            <span class="text-[9px] uppercase leading-none text-muted">{{ $date->translatedFormat('D') }}</span>
                                            <span class="text-[10px] font-medium leading-none text-default">{{ $day }}</span>
                                            <div class="flex h-6 w-8 items-center justify-center rounded text-sm font-normal {{ $cellClass }}" style="{{ $cellStyle }}">
                                                @if($coverage)
                                                    <span data-coverage="{{ $coverage }}"></span>
                                                @endif
                                            </div>
                                        </div>
                                    @endfor
                                </div>

                                <div class="mb-3 flex items-center gap-3 text-[11px] text-muted">
                                    <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-primary/25"></span> Journée pleine</span>
                                    <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-primary/10"></span> Partielle</span>
                                    <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-elevated/80"></span> Week-end / férié</span>
                                    <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-muted/30" style="background-image: repeating-linear-gradient(45deg, var(--ui-border) 0, var(--ui-border) 1px, transparent 1px, transparent 5px);"></span> Non facturable</span>
                                    <span class="ml-auto text-default">Jours travaillés : <strong data-days="{{ $month['days_worked'] }}"></strong></span>
                                </div>

                                @if($notedDays->isNotEmpty())
                                    <div class="mb-3 space-y-1.5 rounded-lg border border-default bg-muted/20 p-3">
                                        <p class="text-[11px] font-medium uppercase tracking-wide text-muted">Notes</p>
                                        @foreach($notedDays as $date => $entry)
                                            <p class="text-[11px] leading-snug">
                                                <span class="font-medium text-default">{{ \Carbon\Carbon::parse($date)->translatedFormat('d/m') }}</span>
                                                @if(trim($entry['title'] ?? '') !== '')
                                                    — {{ $entry['title'] }}
                                                @endif
                                                @if(trim($entry['description'] ?? '') !== '')
                                                    <span class="text-muted">: {{ $entry['description'] }}</span>
                                                @endif
                                            </p>
                                        @endforeach
                                    </div>
                                @endif

                                @if(collect($month['invoices'] ?? [])->isNotEmpty())
                                    <table class="w-full overflow-hidden rounded-lg border border-default text-xs">
                                        <thead>
                                            <tr class="border-b border-default bg-muted/40 text-[11px] text-muted">
                                                <th class="px-3 py-1.5 text-left font-medium">Facture du</th>
                                                <th class="px-3 py-1.5 text-right font-medium">Montant</th>
                                                <th class="px-3 py-1.5 text-left font-medium">Statut</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($month['invoices'] as $invoice)
                                                <tr class="border-b border-default last:border-b-0">
                                                    <td class="px-3 py-1.5">{{ \Carbon\Carbon::parse($invoice['created_at'])->translatedFormat('d/m/Y') }}</td>
                                                    <td class="px-3 py-1.5 text-right font-medium tabular-nums {{ $invoice['paid_at'] ? 'text-success' : 'text-amber-500' }}">
                                                        @if(($invoice['discount_percent'] ?? null) !== null)
                                                            <span class="inline-flex items-center justify-end gap-1.5">
                                                                <span class="font-normal text-muted line-through" data-currency-cents="{{ $invoice['amount'] }}"></span>
                                                                <span data-currency-cents="{{ $invoice['net_amount'] }}"></span>
                                                                <span class="text-[10px] font-normal leading-none text-muted">−{{ $invoice['discount_percent'] }}%</span>
                                                            </span>
                                                        @else
                                                            <span data-currency-cents="{{ $invoice['amount'] }}"></span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-1.5">
                                                        {{ $invoice['paid_at'] ? 'Payée le '.\Carbon\Carbon::parse($invoice['paid_at'])->translatedFormat('d/m/Y') : 'Non payée' }}
                                                    </td>
                                                </tr>
                                                @if(trim($invoice['notes'] ?? '') !== '')
                                                    <tr class="border-b border-default bg-muted/20 last:border-b-0">
                                                        <td class="px-3 py-1.5 text-[11px] text-muted" colspan="3">{{ $invoice['notes'] }}</td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </section>
                        @endforeach

                        <div style="break-inside: avoid;">
                            @php
                                // Negative = reste à facturer, positive = trop perçu.
                                $openingBalance = -$openingToInvoice;
                                $periodBalance = -$toInvoice;
                                $currentBalance = $openingBalance + $periodBalance;
                                $dailyRate = $project['daily_rate'];
                            @endphp

                            <footer class="mt-8 rounded-lg border border-default p-4">
                                <table class="w-full text-xs" style="border-collapse: collapse;">
                                    <colgroup>
                                        <col style="width: auto;">
                                        <col style="width: 1%;">
                                        <col style="width: 1%;">
                                    </colgroup>
                                    <tbody>
                                        @if($openingBalance !== 0)
                                            <tr>
                                                <td class="py-1.5 text-muted">Solde avant la période</td>
                                                <td class="py-1.5 text-right font-medium text-default">{{ $openingBalance > 0 ? '+' : '−' }}</td>
                                                <td class="py-1.5 pl-1 text-right font-medium tabular-nums text-default" data-currency-cents="{{ abs($openingBalance) }}"></td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <td class="py-1.5 text-muted">
                                                <span data-days="{{ $totalDays }}"></span> travaillés × <span data-currency-cents="{{ $dailyRate }}"></span>/j
                                            </td>
                                            <td class="py-1.5 text-right font-medium text-default">−</td>
                                            <td class="py-1.5 pl-1 text-right font-medium tabular-nums text-default" data-currency-cents="{{ $totalWorked }}"></td>
                                        </tr>
                                        <tr>
                                            <td class="py-1.5 text-muted" style="vertical-align: top;">
                                                Montant facturé
                                                @if(($project['total_discount'] ?? 0) > 0)
                                                    <span class="text-xs text-muted">(−{{ $totalInvoiced > 0 ? round($project['total_discount'] / $totalInvoiced * 100) : 0 }} %)</span>
                                                @endif
                                            </td>
                                            <td class="py-1.5 text-right font-medium text-default" style="vertical-align: top;">+</td>
                                            <td class="py-1.5 pl-1 text-right font-medium tabular-nums text-default" style="vertical-align: top;">
                                                @if(($project['total_discount'] ?? 0) > 0)
                                                    <span class="font-normal text-muted line-through" data-currency-cents="{{ $totalInvoiced }}"></span>
                                                    <span data-currency-cents="{{ $totalInvoiced - $project['total_discount'] }}"></span>
                                                @else
                                                    <span data-currency-cents="{{ $totalInvoiced }}"></span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr style="border-top: 2px solid var(--ui-border);">
                                            <td class="pt-2 text-sm font-semibold text-default">Solde actuel</td>
                                            <td class="pt-2 text-right text-base font-bold text-default">{{ match (true) { $currentBalance < 0 => '-', $currentBalance > 0 => '+', default => ''} }}</td>
                                            <td class="pt-2 pl-1 text-right text-base font-bold tabular-nums text-default" data-currency-cents="{{ abs($currentBalance) }}"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </footer>

                            @if($loop->last)
                                <section class="mt-6 rounded-lg border border-default p-4">
                                    <p class="text-xs font-medium uppercase tracking-wide text-muted">Bon pour accord</p>
                                    <div class="mt-3 rounded-lg border border-dashed border-default" style="height: 3cm;"></div>
                                    <p class="mt-2 text-xs text-muted">Nom, date et signature</p>
                                </section>
                            @endif
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    @endforeach

    <script>{!! \Illuminate\Support\Facades\Vite::content('resources/js/exports/billing-pdf.ts', 'build-exports') !!}</script>
</body>
</html>
