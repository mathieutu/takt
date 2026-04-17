@extends('layouts.dashboard')

@section('title', 'Exporter — ' . config('app.name', 'AssoFlow'))

@push('head')
    <style>
        .export-table {
            font-family: arial, sans-serif;
            border-collapse: collapse;
            width: 100%;
            font-size: 0.875rem;
        }

        .export-table th,
        .export-table td {
            border: 1px solid #d1d5db;
            text-align: left;
            padding: 8px 12px;
        }

        .export-table thead th {
            background-color: #1e3a5f;
            color: #fff;
            font-weight: 600;
            white-space: nowrap;
        }

        .export-table tbody tr:nth-child(even) {
            background-color: #f3f4f6;
        }

        .export-table tbody tr:hover {
            background-color: #dbeafe;
        }

        .export-table td.project-cell {
            font-weight: 600;
            background-color: #eff6ff;
            vertical-align: middle;
            border-left: 3px solid #3b82f6;
        }

        .export-table td.number {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .summary-table {
            font-family: arial, sans-serif;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #d1d5db;
            padding: 8px 14px;
            text-align: left;
        }

        .summary-table thead th {
            background-color: #374151;
            color: #fff;
        }

        .summary-table tr.total-row td {
            background-color: #1e3a5f;
            color: #fff;
            font-weight: 700;
        }

        .summary-table td.number {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .badge-coverage {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-full {
            background: #bbf7d0;
            color: #15803d;
        }

        .badge-half {
            background: #fef9c3;
            color: #a16207;
        }

        .badge-other {
            background: #e0e7ff;
            color: #4338ca;
        }
    </style>
@endpush

@section('content')
    {{-- ── Formulaire de filtres ── --}}
    <form method="get" class="p-4 bg-white rounded shadow mb-6 flex flex-wrap gap-4 items-end">
        <label class="flex flex-col gap-1">
            <span class="text-sm font-medium text-gray-700">Projets à exporter</span>
            <select name="projects[]" multiple required id="proj-select"
                    class="border border-gray-300 rounded px-2 py-1 min-w-52">
                @foreach ($user->clients as $client)
                    @continue($client->projects->isEmpty())
                    <optgroup label="{{ $client->name }}">
                        @foreach ($client->projects as $project)
                            <option value="{{ $project->id }}"
                                {{ in_array($project->id, $projects_ids) ? 'selected' : '' }}>
                                {{ $project->name }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </label>

        <label class="flex flex-col gap-1">
            <span class="text-sm font-medium text-gray-700">À partir du</span>
            <input type="date" name="date_start" value="{{ $date_start }}"
                   class="border border-gray-300 rounded px-2 py-1">
        </label>

        <label class="flex flex-col gap-1">
            <span class="text-sm font-medium text-gray-700">Jusqu'au</span>
            <input type="date" name="date_end" value="{{ $date_end }}"
                   class="border border-gray-300 rounded px-2 py-1">
        </label>

        <button type="submit"
                class="px-4 py-2 bg-blue-600 text-white font-semibold rounded hover:bg-blue-700 cursor-pointer">
            Mettre à jour
        </button>

        @if(!$projects->isEmpty())
            @php
                $csvParams = http_build_query([
                    'projects'   => $projects_ids,
                    'date_start' => $date_start,
                    'date_end'   => $date_end,
                ]);
            @endphp
            <a href="{{ route('dashboard.exports.csv') }}?{{ $csvParams }}"
               class="px-4 py-2 bg-green-600 text-white font-semibold rounded hover:bg-green-700 cursor-pointer inline-block">
                Exporter Excel
            </a>
        @endif
    </form>

    @if($projects->isEmpty())
        <p class="text-gray-500 px-4">Sélectionnez au moins un projet pour afficher l'export.</p>
    @else
        <div class="px-4 mb-8 overflow-x-auto">
            <table class="export-table">
                <thead>
                <tr>
                    <th>Projet</th>
                    <th>Client</th>
                    <th>Date</th>
                    <th>Label</th>
                    <th>Couverture</th>
                    <th>Taux / j</th>
                    <th>Montant</th>
                    <th>Commentaires</th>
                </tr>
                </thead>
                <tbody>
                @foreach($projects as $project)
                    @php
                        $activities  = $project->activityTimes;
                        $rate        = $project->daily_rate ?? $project->client->daily_rate ?? 0;
                        $rowCount    = $activities->count();
                        $first       = true;
                    @endphp

                    @if($rowCount === 0)
                        <tr>
                            <td class="project-cell">{{ $project->name }}</td>
                            <td>{{ $project->client->name }}</td>
                            <td colspan="6" class="text-gray-400 italic">Aucune activité sur cette période</td>
                        </tr>
                    @else
                        @foreach($activities as $activity)
                            @php
                                $montant = ($activity->day_coverage / 100) * $rate;
                                $coverageClass = match(true) {
                                    $activity->day_coverage >= 100 => 'badge-full',
                                    $activity->day_coverage >= 50  => 'badge-half',
                                    default                        => 'badge-other',
                                };
                            @endphp
                            <tr>
                                @if($first)
                                    <td class="project-cell" rowspan="{{ $rowCount }}">
                                        {{ $project->name }}
                                    </td>
                                    <td rowspan="{{ $rowCount }}">{{ $project->client->name }}</td>
                                    @php $first = false; @endphp
                                @endif
                                <td>{{ $activity->start_date->format('d/m/Y') }}</td>
                                <td>{{ $activity->label ?? '—' }}</td>
                                <td>
                                        <span class="badge-coverage {{ $coverageClass }}">
                                            {{ $activity->day_coverage }}&nbsp;%
                                        </span>
                                </td>
                                <td class="number">{{ number_format($rate, 2, ',', ' ') }}&nbsp;€</td>
                                <td class="number">{{ number_format($montant, 2, ',', ' ') }}&nbsp;€</td>
                                <td class="text-gray-500 text-xs">{{ $activity->comments ?? '—' }}</td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-4">
            <h2 class="text-base font-bold text-gray-700 mb-2">Résumé</h2>
            <table class="summary-table">
                <thead>
                <tr>
                    <th>Projet</th>
                    <th>Client</th>
                    <th class="number">Nb activités</th>
                    <th class="number">Total jours</th>
                    <th class="number">Taux / j</th>
                    <th class="number">Montant total</th>
                </tr>
                </thead>
                <tbody>
                @php
                    $grandTotalDays   = 0;
                    $grandTotalAmount = 0;
                @endphp

                @foreach($projects as $project)
                    @php
                        $activities  = $project->activityTimes;
                        $rate        = $project->daily_rate ?? $project->client->daily_rate ?? 0;
                        $totalDays   = $activities->sum('day_coverage') / 100;
                        $totalAmount = $totalDays * $rate;
                        $grandTotalDays   += $totalDays;
                        $grandTotalAmount += $totalAmount;
                    @endphp
                    <tr>
                        <td>{{ $project->name }}</td>
                        <td>{{ $project->client->name }}</td>
                        <td class="number">{{ $activities->count() }}</td>
                        <td class="number">{{ number_format($totalDays, 2, ',', ' ') }}</td>
                        <td class="number">{{ number_format($rate, 2, ',', ' ') }}&nbsp;€</td>
                        <td class="number">{{ number_format($totalAmount, 2, ',', ' ') }}&nbsp;€</td>
                    </tr>
                @endforeach

                <tr class="total-row">
                    <td colspan="3">Total général</td>
                    <td class="number">{{ number_format($grandTotalDays, 2, ',', ' ') }}</td>
                    <td class="number">—</td>
                    <td class="number">{{ number_format($grandTotalAmount, 2, ',', ' ') }}&nbsp;€</td>
                </tr>
                </tbody>
            </table>
        </div>
    @endif
@endsection
