<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\TimesheetEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SyncProjectEntriesHandler
{
    public function __invoke(Request $request, Project $project): RedirectResponse
    {
        $entries = $request->validate([
            '*.coverage' => ['required', 'integer', 'between:0,100'],
            '*.title' => ['nullable', 'string'],
            '*.description' => ['nullable', 'string'],
        ]);

        foreach ($entries as $date => $data) {
            TimesheetEntry::updateOrCreate(
                ['project_id' => $project->id, 'date' => $date],
                $data,
            );
        }

        return redirect()->back();
    }
}
