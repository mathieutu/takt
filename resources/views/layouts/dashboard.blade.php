@extends('layouts.html')

@push('head')
    @vite(['resources/js/app.js'])
@endpush

@push('body')
@php
    $account = \App\Models\Account::authenticated();
    $user = $account?->user;
    $sharedProps = [
        'user' => [
            'name'  => $user ? $user->first_name . ' ' . $user->last_name : $account?->email ?? '',
            'email' => $account?->email ?? '',
            'role'  => $account?->type?->value ?? '',
        ],
        'currentPath' => '/' . request()->path(),
    ];
@endphp

<div class="flex h-screen overflow-hidden bg-background">

    {{-- Header (full width, fixed top) --}}
    <div
        id="vue-header"
        data-props="{{ json_encode($sharedProps) }}"
    ></div>

    {{-- Sidebar (desktop only, fixed below header) --}}
    <div
        id="vue-sidebar"
        data-props="{{ json_encode($sharedProps) }}"
        class="hidden md:block fixed left-0 top-14 z-40 h-[calc(100vh-3.5rem)]"
    ></div>

    {{-- Contenu principal --}}
    <main class="mt-14 md:ml-14 flex-1 overflow-y-auto">
        @yield('content')
    </main>

</div>
@endpush
