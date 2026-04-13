@use(App\Enums\AccountType)

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Créer son compte</title>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body>
  <form action="{{ route('register') }}" method="post" x-data="{ account_type: '{{ AccountType::User->value }}' }">
    @csrf

    <input type="email" name="email" placeholder="L'email">
    <input type="password" name="password" placeholder="Le mdp">
    <input type="password" name="password_confirmation" placeholder="Confirmez votre mdp">

    <label for="type_user">
      <input type="radio" id="type_user" name="type" value="{{ AccountType::User->value }}" x-model="account_type">
      Je suis un utilisateur
    </label>
    <label for="type_organization">
      <input type="radio" id="type_organization" name="type" value="{{ AccountType::Organization->value }}"
        x-model="account_type">
      Je suis une organisation
    </label>

    <template x-if="account_type === '{{ AccountType::User->value }}'">
      <div>
        <input type="text" name="model.first_name" placeholder="Prénom">
        <input type="text" name="model.last_name" placeholder="Nom de famille">
      </div>
    </template>

    <template x-if="account_type === '{{ AccountType::Organization->value }}'">
      <div>
        <input type="text" name="model.name" placeholder="Nom de l'organisation">
      </div>
    </template>

    <button type="submit">Créer mon compte</button>
  </form>
</body>

</html>
