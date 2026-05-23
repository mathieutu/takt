<?php

namespace App\Http\Controllers;

use App\Http\Requests\SyncTimesheetEntriesRequest;
use App\Models\Project;
use App\Models\TimesheetEntry;
use Illuminate\Http\RedirectResponse;

class SyncProjectEntriesHandler
{
    public function __invoke(SyncTimesheetEntriesRequest $request, Project $project): RedirectResponse
    {
        abort_unless($request->user()->projects()->whereKey($project->id)->exists(), 403);

        foreach ($request->validated() as $date => $data) {
            TimesheetEntry::updateOrCreate(
                ['project_id' => $project->id, 'date' => $date],
                $data,
            );
        }

        return redirect()->back();
    }
}
