<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', $title ?? null)</title>
  @vite(['resources/ts/alpine', 'resources/css/app.css'])
  @stack('head')
</head>

<body x-data="{}">
  @stack('body')
</body>

</html>
