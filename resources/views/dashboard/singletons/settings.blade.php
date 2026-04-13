<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Paramètres — {{ config('app.name', 'AssoFlow') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background">
    <div
        id="vue-settings"
        data-props="{{ json_encode([
            'action'    => route('dashboard.settings'),
            'csrfToken' => csrf_token(),
            'type'      => $account->type->value,
            'email'     => $account->email,
            'firstName' => $account->type->value === 'user' ? ($account->user?->first_name ?? '') : '',
            'lastName'  => $account->type->value === 'user' ? ($account->user?->last_name ?? '') : '',
            'orgName'   => $account->type->value === 'organization' ? ($account->organization?->name ?? '') : '',
            'errors'    => $errors->toArray(),
            'success'   => $success ?? false,
        ]) }}"
    ></div>
</body>
</html>
