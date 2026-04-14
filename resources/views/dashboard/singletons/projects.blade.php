<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Projets — {{ config('app.name', 'AssoFlow') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background">
    <div
        id="vue-projects"
        data-props="{{ json_encode([
            'storeAction' => route('dashboard.projects.store'),
            'baseAction'  => url('/dashboard/projects'),
            'csrfToken'   => csrf_token(),
            'projects'    => $projects->map(fn($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'description' => $p->description ?? '',
                'daily_rate'  => $p->daily_rate ? (float) $p->daily_rate : null,
                'client_id'   => $p->client_id,
                'client_name' => $p->client->name,
                'created_at'  => $p->created_at?->translatedFormat('j M Y') ?? '',
            ])->values(),
            'clients'  => $clients->map(fn($c) => [
                'id'   => $c->id,
                'name' => $c->name,
            ])->values(),
            'errors' => $errors->toArray(),
            'old'    => old(),
        ]) }}"
    ></div>
</body>
</html>
