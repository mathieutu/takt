<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facturation {{ $clientName }}</title>
    <style>
        @page { size: A4; margin: 15mm; }
        body { font-family: sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 16px 0 4px; text-transform: capitalize; }
        h3 { font-size: 12px; margin: 8px 0 4px; }
        .project { page-break-before: always; }
        .project:first-of-type { page-break-before: avoid; }
        .header { margin-bottom: 12px; border-bottom: 1px solid #ccc; padding-bottom: 8px; }
        .header p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        td, th { border: 1px solid #ccc; padding: 2px 4px; text-align: left; font-size: 11px; }
        .calendar { display: flex; flex-wrap: wrap; gap: 2px; margin-bottom: 6px; }
        .day { width: 26px; text-align: center; border: 1px solid #ddd; padding: 2px 0; font-size: 9px; }
        .day .letter { display: block; color: #888; }
        .day .n { display: block; font-weight: bold; }
        .day .coverage { display: block; color: #a00; }
        .day.weekend, .day.holiday { background: #eee; }
        .notes { margin-bottom: 8px; }
        .notes p { margin: 2px 0; }
        .totals { margin-top: 12px; border-top: 1px solid #333; padding-top: 6px; }
        .totals table td:first-child { font-weight: bold; width: 200px; }
    </style>
</head>
<body>
    @foreach($projects as $project)
        <div class="project">
            <div class="header">
                <h1>{{ $project['name'] }}</h1>
                <p>Client : {{ $clientName }}</p>
                <p>Période : {{ $from }} — {{ $to }}</p>
                @if($providerName)
                    <p>
                        Prestataire : {{ $providerName }}
                        @if($providerEmail)
                            ({{ $providerEmail }})
                        @endif
                    </p>
                @endif
            </div>

            @foreach($project['months'] as $month)
                @php
                    $monthStart = \Carbon\Carbon::createFromFormat('Y-m-d', $month['month'].'-01');
                    $entries = collect($month['entries'] ?? []);
                    $notedDays = $entries->filter(fn ($entry) => trim($entry['title'] ?? '') !== '' || trim($entry['description'] ?? '') !== '');
                @endphp

                <h2>{{ $monthStart->translatedFormat('F Y') }}</h2>

                <div class="calendar">
                    @for($day = 1; $day <= $monthStart->daysInMonth; $day++)
                        @php
                            $date = $monthStart->copy()->day($day);
                            $dateString = $date->toDateString();
                            $entry = $entries->get($dateString);
                            $isWeekend = $date->isWeekend();
                            $isHoliday = array_key_exists($dateString, $holidays ?? []);
                        @endphp
                        <div class="day{{ $isWeekend ? ' weekend' : '' }}{{ $isHoliday ? ' holiday' : '' }}">
                            <span class="letter">{{ $date->translatedFormat('D') }}</span>
                            <span class="n">{{ $day }}</span>
                            <span class="coverage">{{ $entry ? $entry['coverage'].'%' : '' }}</span>
                        </div>
                    @endfor
                </div>

                <p>Jours travaillés : {{ $month['days_worked'] }}</p>

                @if($notedDays->isNotEmpty())
                    <div class="notes">
                        <h3>Notes</h3>
                        @foreach($notedDays as $date => $entry)
                            <p>
                                <strong>{{ $date }}</strong>
                                @if(trim($entry['title'] ?? '') !== '')
                                    — {{ $entry['title'] }}
                                @endif
                                @if(trim($entry['description'] ?? '') !== '')
                                    : {{ $entry['description'] }}
                                @endif
                            </p>
                        @endforeach
                    </div>
                @endif

                @if(collect($month['invoices'] ?? [])->isNotEmpty())
                    <table>
                        <thead>
                            <tr>
                                <th>Facture du</th>
                                <th>Montant</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($month['invoices'] as $invoice)
                                <tr>
                                    <td>{{ $invoice['created_at'] }}</td>
                                    <td>{{ number_format($invoice['amount'] / 100, 2) }} €</td>
                                    <td>{{ $invoice['paid_at'] ? 'Payée le '.$invoice['paid_at'] : 'Non payée' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @endforeach

            @php
                $totalDays = collect($project['months'])->sum('days_worked');
                $totalWorked = collect($project['months'])->sum(fn ($month) => $month['days_worked'] * $project['daily_rate']);
                $allInvoices = collect($project['months'])->flatMap(fn ($month) => $month['invoices'] ?? []);
                $totalInvoiced = $allInvoices->sum('amount');
                $toInvoice = $totalWorked - $totalInvoiced;
                $openingToInvoice = $project['opening_to_invoice'];
            @endphp

            <div class="totals">
                <table>
                    <tr><td>Jours travaillés</td><td>{{ $totalDays }}</td></tr>
                    <tr><td>Total facturable</td><td>{{ number_format($totalWorked / 100, 2) }} €</td></tr>
                    <tr><td>Total facturé</td><td>{{ number_format($totalInvoiced / 100, 2) }} €</td></tr>
                    @if($openingToInvoice !== 0)
                        <tr><td>Solde à facturer avant la période</td><td>{{ number_format($openingToInvoice / 100, 2) }} €</td></tr>
                        <tr><td>Reste à facturer sur la période</td><td>{{ number_format($toInvoice / 100, 2) }} €</td></tr>
                        <tr><td>Solde total à facturer</td><td>{{ number_format(($openingToInvoice + $toInvoice) / 100, 2) }} €</td></tr>
                    @else
                        <tr><td>Reste à facturer</td><td>{{ number_format($toInvoice / 100, 2) }} €</td></tr>
                    @endif
                </table>
            </div>
        </div>
    @endforeach
</body>
</html>
