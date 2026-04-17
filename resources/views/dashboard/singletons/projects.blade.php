@extends('layouts.dashboard')

@section('title', 'Projets — ' . config('app.name', 'AssoFlow'))

@section('content')
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
@endsection
