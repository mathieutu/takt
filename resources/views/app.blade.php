<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="/logo.svg">
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
        plausible.init()
    </script>
    @endif
</head>
<body>
<div class="isolate">
    <x-inertia::app/>
</div>
</body>
</html>
