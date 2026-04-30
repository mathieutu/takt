@extends('layouts.dashboard')

@section('title', 'Suivi facturation — ' . config('app.name', 'AssoFlow'))

@section('content')
    <div
        id="vue-tracking"
        data-props="{{ json_encode([
            'clients' => $clients->map(fn($c) => [
                'id'         => $c->id,
                'name'       => $c->name,
                'daily_rate' => (float) $c->daily_rate,
            ])->values(),
            'selectedClientId'   => $selectedClientId,
            'projects'           => $projects->values(),
            'csrfToken'          => csrf_token(),
            'billingStoreAction' => url('/dashboard/tracking/billing'),
            'billingBaseAction'  => url('/dashboard/tracking/billing'),
            'projectBudgetBase'  => url('/dashboard/tracking/projects'),
        ]) }}"
    ></div>
@endsection
