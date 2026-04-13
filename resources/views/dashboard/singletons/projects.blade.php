<p>Projet</p>

<h3>Create Projects :</h3>

<form action="{{ route('dashboard.projects.store') }}" method="post">
    @csrf

    <label for="name">Name :</label>
    <input type="text" name="name">

    <label for="description">Description :</label>
    <input type="text" name="description">

    <label for="daily_rate">daily rate :</label>
    <input type="number" min="0" name="daily_rate">

    <label for="client_id">Client</label>
    <select name="client_id" id="client">
        @foreach($clients as $client)
            <option value="{{ $client->id }}">{{ $client->name }}</option>
        @endforeach
    </select>

    <button type="submit">Enregister</button>
</form>
<ul>
    @foreach($projects as $project)
        <h3>Name:</h3>
        <p>{{ $project->name }}</p>
        <h3>Description : </h3>
        <p>{{ $project->description }}</p>
        <h3>Daily_rate : </h3>
        <p>{{ $project->daily_rate }}</p>
        <p>Client : {{ $project->client->name }}</p>
        <form action="{{ route('dashboard.projects.destroy', $project->id) }}" method="post">
            @csrf
            @method('DELETE')

            <button type="submit">Supprimer</button>
        </form>

        <h3>Modifier:</h3>
        <form action="{{ route('dashboard.projects.update', $project->id) }}" method="post">

            @csrf
            @method('PUT')

            <label for="name">Name :</label>
            <input type="text" name="name" value="{{ old('name', $project->name) }}">

            <label for="description">Description :</label>
            <input type="text" name="description" value="{{ old('description',$project->description) }}">

            <label for="daily_rate">daily rate :</label>
            <input type="number" min="0" name="daily_rate" value="{{ old('daily_rate', $project->daily_rate) }}">

            <select name="client_id" id="client_id">
                @foreach($clients as $client)
                    <option
                        value="{{ $client->id }}" {{ $client->id === $project->client_id ? 'selected' : '' }}>{{ $client->name }}</option>
                @endforeach
            </select>

            <button type="submit">Modifier</button>
        </form>
    @endforeach
</ul>
