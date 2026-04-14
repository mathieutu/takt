<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Rapports d'activité — {{ config('app.name', 'AssoFlow') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background">
    <div
        id="vue-activity-reports"
        data-props="{{ json_encode([
            'indexUrl'  => url('/dashboard/reports'),
            'storeUrl'  => url('/dashboard/reports'),
            'baseUrl'   => url('/dashboard/reports'),
            'csrfToken' => csrf_token(),
            'projects'  => $projects->map(fn($p) => [
                'id'   => $p->id,
                'name' => $p->name,
            ])->values(),
            'selectedProject' => $project ? [
                'id'   => $project->id,
                'name' => $project->name,
            ] : null,
            'reports' => isset($reports) ? $reports->map(fn($r) => [
                'id'           => $r->id,
                'label'        => $r->label ?? '',
                'start_date'   => $r->start_date->format('Y-m-d'),
                'day_coverage' => $r->day_coverage ?? 0,
                'comments'     => $r->comments ?? '',
            ])->values() : [],
        ]) }}"
    ></div>
</body>
</html>
