<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', $title ?? null)</title>
  @vite(['resources/css/app.css'])
  @stack('head')
</head>

<body class="@yield('body-class')">
  @stack('body')
</body>

</html>
