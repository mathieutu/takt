<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Clients — {{ config('app.name', 'AssoFlow') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background">
    <div
        id="vue-clients"
        data-props="{{ json_encode([
            'storeAction' => route('dashboard.clients.store'),
            'baseAction'  => url('/dashboard/clients'),
            'csrfToken'   => csrf_token(),
            'clients'     => $clients->map(fn($c) => [
                'id'         => $c->id,
                'name'       => $c->name,
                'daily_rate' => (float) $c->daily_rate,
                'created_at' => $c->created_at?->translatedFormat('j M Y') ?? '',
            ])->values(),
            'errors' => $errors->toArray(),
            'old'    => old(),
        ]) }}"
    ></div>
</body>
</html>
