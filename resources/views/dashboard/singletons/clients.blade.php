{{-- ================================================================
SECTION : Créer un client
POST {{ route('dashboard.clients.store') }}
Champs : name (string), daily_rate (number)
================================================================ --}}
<h2>Crée un client : </h2>
<form action="{{ route('dashboard.clients.store') }}" method="post">
    @csrf

    <label for="name">Nom</label>
    <input type="text" name="name">

    <label for="daily_rate">TJM (en €)</label>
    <input type="number" placeholder="80 €" min="0" max="100" name="daily_rate">

    <button type="submit">
        Créer un utilisateur
    </button>
</form>

{{-- ================================================================
SECTION : Liste des clients
$clients : collection de Client { id, name, daily_rate, user_id }
================================================================ --}}
<ul>
    @foreach ($clients as $client)
        <li>{{ $client->name }} - {{ $client->daily_rate }} € TJM

            {{-- --------------------------------
            Supprimer un client
            DELETE {{ route('dashboard.clients.destroy', $client->id) }}
            -------------------------------- --}}
            <form action="{{ route('dashboard.clients.destroy', $client->id) }}" method="post">
                @method('DELETE')
                @csrf
                <button type="submit">Supprimer</button>
            </form>

            {{-- --------------------------------
            Modifier un client
            PUT {{ route('dashboard.clients.update', $client->id) }}
            Champs : name (string), daily_rate (number)
            Valeurs actuelles pré-remplies via old() ou $client
            -------------------------------- --}}
            <h2>Modifier un client </h2>
            <form action="{{ route('dashboard.clients.update', $client->id) }}" method="post">
                @csrf
                @method('PUT')

                <label for="name">Nom</label>
                <input type="text" name="name" value="{{ old('name', $client->name) }}">

                <label for="daily_rate">TJM (en €)</label>
                <input type="number" placeholder="80 €" min="0" name="daily_rate"
                    value="{{ old('name', $client->daily_rate) }}">

                <button type="submit">
                    Modifier
                </button>
            </form>
        </li>
    @endforeach
</ul>