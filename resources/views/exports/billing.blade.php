<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facturation {{ $clientName }}</title>
    <style>
        @page { size: A4; margin: 16mm 14mm; }
        html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .project { page-break-before: always; }
        .project:first-of-type { page-break-before: avoid; }
        .month { page-break-inside: avoid; }
    </style>
    <style>{!! file_get_contents(resource_path('css/exports-theme.css')) !!}</style>
    <style>{!! \Illuminate\Support\Facades\Vite::content('resources/css/app.css') !!}</style>
</head>
<body class="bg-default font-sans text-sm text-default">
    @foreach($projects as $project)
        @php
            $totalDays = collect($project['months'])->sum('days_worked');
            $totalWorked = collect($project['months'])->sum(fn ($month) => $month['days_worked'] * $project['daily_rate']);
            $allInvoices = collect($project['months'])->flatMap(fn ($month) => $month['invoices'] ?? []);
            $totalInvoiced = $allInvoices->sum('amount');
            $toInvoice = $totalWorked - $totalInvoiced;
            $openingToInvoice = $project['opening_to_invoice'];
        @endphp

        <div class="project px-2 py-1">
            <header class="mb-8 flex items-start justify-between gap-6 border-b-2 border-primary/30 pb-4">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-primary">Facturation</p>
                    <h1 class="mt-0.5 text-xl font-semibold text-default">{{ $project['name'] }}</h1>
                    <p class="mt-0.5 text-xs text-muted">{{ $clientName }}</p>
                </div>
                <div class="text-right text-xs text-muted">
                    <p class="font-medium text-default">{{ $from }} — {{ $to }}</p>
                    @if($providerName)
                        <p class="mt-2">{{ $providerName }}</p>
                        @if($providerEmail)
                            <p>{{ $providerEmail }}</p>
                        @endif
                    @endif
                </div>
            </header>

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
                                $cellClass = match(true) {
                                    $coverage >= 100 => 'bg-primary/25 text-primary',
                                    $coverage > 0 => 'bg-primary/10 text-primary',
                                    $isOff => 'bg-elevated/80 text-muted',
                                    default => 'text-muted',
                                };
                            @endphp
                            <div class="flex w-8 flex-col items-center gap-0.5">
                                <span class="text-[8px] uppercase leading-none text-muted">{{ $date->translatedFormat('D') }}</span>
                                <span class="text-[9px] font-medium leading-none text-default">{{ $day }}</span>
                                <div class="flex h-6 w-8 items-center justify-center rounded text-[9px] font-semibold {{ $cellClass }}">
                                    @if($coverage)
                                        <span data-coverage="{{ $coverage }}"></span>
                                    @endif
                                </div>
                            </div>
                        @endfor
                    </div>

                    <div class="mb-3 flex items-center gap-3 text-[10px] text-muted">
                        <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-primary/25"></span> Journée pleine</span>
                        <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-primary/10"></span> Partielle</span>
                        <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-elevated/80"></span> Week-end / férié</span>
                        <span class="ml-auto text-default">Jours travaillés : <strong data-days="{{ $month['days_worked'] }}"></strong></span>
                    </div>

                    @if($notedDays->isNotEmpty())
                        <div class="mb-3 space-y-1.5 rounded-lg border border-default bg-muted/20 p-3">
                            <p class="text-[10px] font-medium uppercase tracking-wide text-muted">Notes</p>
                            @foreach($notedDays as $date => $entry)
                                <p class="text-xs leading-snug">
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
                                <tr class="border-b border-default bg-muted/40 text-[10px] text-muted">
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
                                            <span data-currency-cents="{{ $invoice['amount'] }}"></span>
                                        </td>
                                        <td class="px-3 py-1.5">
                                            {{ $invoice['paid_at'] ? 'Payée le '.\Carbon\Carbon::parse($invoice['paid_at'])->translatedFormat('d/m/Y') : 'Non payée' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </section>
            @endforeach

            <footer class="mt-8 rounded-lg border border-default bg-muted/20 p-4">
                <dl class="grid grid-cols-2 gap-x-8 gap-y-3 sm:grid-cols-4">
                    <div>
                        <dt class="text-[10px] uppercase tracking-wide text-muted">Jours travaillés</dt>
                        <dd class="mt-0.5 text-sm font-semibold tabular-nums text-default"><span data-days="{{ $totalDays }}"></span></dd>
                    </div>
                    <div>
                        <dt class="text-[10px] uppercase tracking-wide text-muted">Total facturable</dt>
                        <dd class="mt-0.5 text-sm font-semibold tabular-nums text-default"><span data-currency-cents="{{ $totalWorked }}"></span></dd>
                    </div>
                    <div>
                        <dt class="text-[10px] uppercase tracking-wide text-muted">Total facturé</dt>
                        <dd class="mt-0.5 text-sm font-semibold tabular-nums text-default"><span data-currency-cents="{{ $totalInvoiced }}"></span></dd>
                    </div>
                    <div>
                        <dt class="text-[10px] uppercase tracking-wide text-muted">
                            {{ $openingToInvoice !== 0 ? 'Reste à facturer (période)' : 'Reste à facturer' }}
                        </dt>
                        <dd class="mt-0.5 text-sm font-semibold tabular-nums {{ $toInvoice < 0 ? 'text-error' : 'text-default' }}">
                            <span data-currency-cents="{{ $toInvoice }}"></span>
                        </dd>
                    </div>
                </dl>

                @if($openingToInvoice !== 0)
                    <div class="mt-3 flex items-center justify-between border-t border-default pt-3 text-xs">
                        <span class="text-muted">Solde à facturer avant la période : <strong class="text-default" data-currency-cents="{{ $openingToInvoice }}"></strong></span>
                        <span class="font-semibold text-default">
                            Solde total à facturer :
                            <span class="{{ ($openingToInvoice + $toInvoice) < 0 ? 'text-error' : 'text-default' }}" data-currency-cents="{{ $openingToInvoice + $toInvoice }}"></span>
                        </span>
                    </div>
                @endif
            </footer>
        </div>
    @endforeach

    <script>{!! file_get_contents(public_path('build-exports/billing-pdf.js')) !!}</script>
</body>
</html>
