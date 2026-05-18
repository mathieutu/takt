<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Se connecter — AssoFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background">
    <div
        id="vue-login"
        data-props="{{ json_encode([
            'action'   => route('login'),
            'csrfToken' => csrf_token(),
            'error'    => $errors->first('#global'),
            'oldEmail' => old('email', ''),
        ]) }}"
    ></div>
</body>
</html>
