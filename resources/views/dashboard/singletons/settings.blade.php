@use(App\Enums\AccountType)

@php
  $hasFormBeenSent = Request::isMethod('put');
  $hasSettingBeenUpdated = $hasFormBeenSent && ($success ?? false);
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Modifier mes paramètres</title>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body>
  <h1>Ca c les paramètres ta grand mere la pute</h1>

  <form action="{{ route('dashboard.settings') }}" method="post" x-data="{ psw: '' }">
    @csrf
    @method('put')

    <input type="email" name="email" placeholder="email">

    <input type="password" name="password" placeholder="mdp" x-model="psw">
    {{-- todo : password_confirmation show only quand password est typed --}}
    <template x-if="psw.trim()">
      <input type="password" name="password_confirmation" placeholder="Confirme ton password ta grand mere">
    </template>

    @if ($account->type === AccountType::User)
      <div>
        <h2>Données de l'utilisateur</h2>

        <input type="text" name="first_name" placeholder="Prénom">
        <input type="text" name="last_name" placeholder="Nom">
      </div>
    @elseif ($account->type === AccountType::Organization)
      <div>
        <h2>Données de l'organisation</h2>
        <input type="text" name="name" placeholder="Nom de l'organisation">
      </div>
    @endif

    <button type="submit">Sauvegarder mes info de salopard</button>
  </form>
</body>

</html>
