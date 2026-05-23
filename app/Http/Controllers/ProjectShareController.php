<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectShareController
{
    public function store(Request $request, Project $project): JsonResponse
    {
        abort_unless($request->user()->can('update', $project), 403);

        return response()->json(['url' => $project->share()->url()]);
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        abort_unless($request->user()->can('update', $project), 403);

        $project->sharer()->delete();

        return response()->json();
    }
}
