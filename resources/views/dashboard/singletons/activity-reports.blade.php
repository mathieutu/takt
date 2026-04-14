@php
    $fromParam = request('from');
    $currentDate = $fromParam ? \Carbon\Carbon::parse($fromParam) : \Carbon\Carbon::now();
    $currentYear = $currentDate->year;
    $currentMonth = $currentDate->month;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Saisie CRA — {{ config('app.name', 'AssoFlow') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background">
    <div
        id="vue-activity-reports"
        data-props="{{ json_encode([
            'indexUrl'     => url('/dashboard/reports'),
            'storeUrl'     => url('/dashboard/reports'),
            'baseUrl'      => url('/dashboard/reports'),
            'csrfToken'    => csrf_token(),
            'currentYear'  => $currentYear,
            'currentMonth' => $currentMonth,
            'projects' => $projects->load('client')->map(fn($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'client_name' => $p->client?->name ?? '',
                'daily_rate'  => $p->daily_rate
                    ? (float) $p->daily_rate
                    : ($p->client?->daily_rate ? (float) $p->client->daily_rate : 0),
            ])->values(),
            'selectedProject' => $project ? [
                'id'          => $project->id,
                'name'        => $project->name,
                'client_name' => $project->client?->name ?? '',
                'daily_rate'  => $project->daily_rate
                    ? (float) $project->daily_rate
                    : ($project->client?->daily_rate ? (float) $project->client->daily_rate : 0),
            ] : null,
            'reports' => isset($reports) ? $reports->map(fn($r) => [
                'id'           => $r->id,
                'start_date'   => $r->start_date->format('Y-m-d'),
                'day_coverage' => $r->day_coverage ?? 0,
                'label'        => $r->label ?? '',
                'comments'     => $r->comments ?? '',
            ])->values() : [],
        ]) }}"
    ></div>
</body>
</html>
