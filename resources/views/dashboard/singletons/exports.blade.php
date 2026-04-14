@extends('layouts.html', [
    'title' => 'Exporter',
])
@push('body')
  @csrf
  <form method="get" class="p-4">
    <label for="proj-select" class="grid gap-2">
      Projets à exporter
      <select name="projects" multiple required id="proj-select">
        @foreach ($user->clients as $client)
          @continue(!$client->projects->empty())
          <optgroup label="{{ $client->name }}">
            @foreach ($client->projects as $project)
              <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
          </optgroup>
        @endforeach
      </select>
    </label>

    <label for="date_start">
        Exporter à partir de
        <input type="date" name="date_start" id="date_start">
    </label>
    <label for="date_end">
        jusqu'à
        <input type="date" name="date_end" id="date_end">
    </label>

    <button type="submit" class="mt-8 p-4 bg-blue-300 cursor-pointer">Mettre à jour</button>
  </form>
@endpush
