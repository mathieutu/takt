@extends('layouts.dashboard')

@section('title', 'Paramètres — ' . config('app.name', 'AssoFlow'))

@section('content')
    <div
        id="vue-settings"
        data-props="{{ json_encode([
            'action'    => route('dashboard.settings'),
            'csrfToken' => csrf_token(),
            'type'      => $account->type->value,
            'email'     => $account->email,
            'firstName' => $account->user?->first_name ?? '',
            'lastName'  => $account->user?->last_name ?? '',
            'orgName'   => $account->organization?->name ?? '',
            'errors'    => $errors->toArray(),
            'success'   => $success ?? false,
        ]) }}"
    ></div>
@endsection
