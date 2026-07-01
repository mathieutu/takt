<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="/logo.svg">
    <meta name="description" content="Gestion du temps et facturation pour freelances. Feuille de temps, projets, suivi de facturation et partage client.">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ config('app.name') }}">
    <meta property="og:description" content="Gestion du temps et facturation pour freelances. Feuille de temps, projets, suivi de facturation et partage client.">
    <meta property="og:url" content="{{ request()->url() }}">
    <meta property="og:image" content="{{ asset('og-image.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ config('app.name') }}">
    <meta name="twitter:description" content="Gestion du temps et facturation pour freelances. Feuille de temps, projets, suivi de facturation et partage client.">
    <meta name="twitter:image" content="{{ asset('og-image.png') }}">
    @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
    <x-inertia::head>
        <title>{{ config('app.name') }}</title>
    </x-inertia::head>
    <script>
        const theme = localStorage.getItem('vueuse-color-scheme') || 'auto'
        if (theme === 'dark' || (theme === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
    @if(app()->isProduction())
    <!-- Privacy-friendly analytics by Plausible -->
    <script async src="https://e.mathieutu.dev/js/pa-R65tyUjkaNzke-vlTzJNJ.js"></script>
    <script>
        window.plausible=window.plausible||function(){(plausible.q=plausible.q||[]).push(arguments)},plausible.init=plausible.init||function(i){plausible.o=i||{}};
        plausible.init({
            customProperties: {
                @php($user = auth()->user())
                userEmail: @json($user ? $user->email : null),
            }
        })
    </script>
    @endif
</head>
<body>
<div class="isolate">
    <x-inertia::app/>
</div>
</body>
</html>
