@extends('layouts.html')

@section('title', 'Exports — ' . config('app.name', 'AssoFlow'))

@push('head')
    @vite(['resources/js/app.js'])
@endpush

@push('body')
    @php
        $allProjectsData = collect();
        foreach ($user->clients as $client) {
            foreach ($client->projects as $project) {
                $allProjectsData->push([
                    'id'          => $project->id,
                    'name'        => $project->name,
                    'client_name' => $client->name,
                ]);
            }
        }
    @endphp
    <div
        id="vue-export"
        data-props="{{ json_encode([
            'indexUrl'           => url('/dashboard/exports'),
            'csvUrl'             => url('/dashboard/exports/csv'),
            'allProjects'        => $allProjectsData->values(),
            'selectedProjectIds' => array_map('intval', (array) $projects_ids),
            'dateStart'          => $date_start ?? '',
            'dateEnd'            => $date_end ?? '',
            'projects'           => $projects->map(fn($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'client_name' => $p->client->name,
                'daily_rate'  => (float) ($p->daily_rate ?? $p->client->daily_rate ?? 0),
                'entries'     => $p->activityTimes->map(fn($a) => [
                    'id'           => $a->id,
                    'start_date'   => $a->start_date->format('Y-m-d'),
                    'day_coverage' => (int) $a->day_coverage,
                    'label'        => $a->label ?? '',
                    'comments'     => $a->comments ?? '',
                ])->values(),
            ])->values(),
        ]) }}"
    ></div>
@endpush
