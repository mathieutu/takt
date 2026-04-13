<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Se connecter</title>
</head>
<body>
<form action="{{ route('login') }}" method="post">
    @csrf
    @error("#global")
        {{ $message }}
    @enderror

    <input type="email" name="email" placeholder="L'email">
    <input type="password" name="password" placeholder="Le mdp">

    <button type="submit">Se connecter</button>
</form>
</body>
</html>
