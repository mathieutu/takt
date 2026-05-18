@extends('layouts.dashboard')

@section('title', 'Tableau de bord — ' . config('app.name', 'AssoFlow'))

@section('content')
    <div
        id="vue-dashboard"
        data-props="{{ json_encode([
            'entries'  => $entries,
            'clients'  => $clients,
            'projects' => $projects,
        ]) }}"
    ></div>
@endsection
