<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ShareController
{
    public function storeProject(Request $request, Project $project): JsonResponse
    {
        return $this->store($request, $project);
    }

    public function destroyProject(Request $request, Project $project): RedirectResponse
    {
        return $this->destroy($request, $project);
    }

    public function storeClient(Request $request, Client $client): JsonResponse
    {
        return $this->store($request, $client);
    }

    public function destroyClient(Request $request, Client $client): RedirectResponse
    {
        return $this->destroy($request, $client);
    }

    private function store(Request $request, Project|Client $shareable): JsonResponse
    {
        abort_unless($request->user()->can('update', $shareable), 403);

        return response()->json(['url' => $shareable->share()->url()]);
    }

    private function destroy(Request $request, Project|Client $shareable): RedirectResponse
    {
        abort_unless($request->user()->can('update', $shareable), 403);

        $shareable->sharer()->delete();

        return back();
    }
}
