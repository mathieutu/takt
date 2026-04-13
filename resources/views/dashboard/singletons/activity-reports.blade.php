@extends('layouts.html')

@push('body')
  <form method="get" @change="$el.submit()">
    <select name="project_id">
      <option disabled selected>Selectionnez un projet</option>

      @foreach ($projects as $project_item)
        <option value="{{ $project_item->id }}" {{ $project?->id == $project_item->id ? 'selected' : null }}>
          {{ $project_item->name }}
        </option>
      @endforeach
    </select>
  </form>
  @isset($project)
    <ul>
      @foreach ($reports as $report)
        <li class="grid grid-cols-[1fr_auto] p-4 gap-8">
          <div class="h-90 overflow-y-scroll">
            @dump($report)
          </div>

          <form action="{{ route('dashboard.activity_reports.destroy', [
              'report' => $report,
          ]) }}"
            method="post">
            @csrf
            @method('delete')
            <button type="submit" class="p-2 bg-red-400 cursor-pointer">delete</button>
          </form>
        </li>
      @endforeach
    </ul>

    <form action="{{ route('dashboard.activity_reports.store') }}" method="post">
      @csrf
      <input type="hidden" name="project_id" value="{{ $project->id }}">

      <input type="text" name="label" placeholder="Nommination" value="{{ old('label') }}" />

      <label>
        Journée :
        <input type="date" name="start_date" value="{{ old('start_date') }}">
      </label>

      <label x-data="{ coverage: {{ old('day_coverage', '0') }} }">
        Temps de travail (<span x-text="Math.floor(coverage * 24 / 100)"></span>h)
        <input type="range" name="day_coverage" min="0" max="100" x-model="coverage">
      </label>

      <textarea name="comments" placeholder="Commentaires">{{ old('comments') }}</textarea>

      <button type="submit">Ajouter</button>
    </form>
  @endisset
@endpush
