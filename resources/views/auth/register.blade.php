<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Créer un compte — AssoFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background">
    <div
        id="vue-register"
        data-props="{{ json_encode([
            'action'    => url('/register'),
            'csrfToken' => csrf_token(),
            'errors'    => $errors->toArray(),
            'old'       => [
                'email'            => old('email', ''),
                'type'             => old('type', 'user'),
                'model_first_name' => old('model.first_name', ''),
                'model_last_name'  => old('model.last_name', ''),
                'model_name'       => old('model.name', ''),
            ],
        ]) }}"
    ></div>
</body>
</html>
