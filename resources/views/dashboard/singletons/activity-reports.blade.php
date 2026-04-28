@php
    $fromParam = request('from');
    $currentDate = $fromParam ? \Carbon\Carbon::parse($fromParam) : \Carbon\Carbon::now();
    $currentYear = $currentDate->year;
    $currentMonth = $currentDate->month;
@endphp

@extends('layouts.dashboard')

@section('title', 'Saisie CRA — ' . config('app.name', 'AssoFlow'))

@section('content')
    <div
        id="vue-activity-reports"
        data-props="{{ json_encode([
            'storeUrl'     => url('/dashboard/reports'),
            'baseUrl'      => url('/dashboard/reports'),
            'csrfToken'    => csrf_token(),
            'currentYear'  => $currentYear,
            'currentMonth' => $currentMonth,
            'projects' => $projects->map(fn($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'client_name' => $p->client?->name ?? '',
                'daily_rate'  => $p->daily_rate
                    ? (float) $p->daily_rate
                    : ($p->client?->daily_rate ? (float) $p->client->daily_rate : 0),
            ])->values(),
            'reports' => $reports->map(fn($r) => [
                'id'           => $r->id,
                'project_id'   => $r->project_id,
                'start_date'   => $r->start_date->format('Y-m-d'),
                'day_coverage' => $r->day_coverage ?? 0,
                'label'        => $r->label ?? '',
                'comments'     => $r->comments ?? '',
            ])->values(),
        ]) }}"
    ></div>
@endsection
