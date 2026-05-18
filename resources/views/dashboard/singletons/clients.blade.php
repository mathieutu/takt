@extends('layouts.dashboard')

@section('title', 'Clients — ' . config('app.name', 'AssoFlow'))

@section('content')
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
@endsection
