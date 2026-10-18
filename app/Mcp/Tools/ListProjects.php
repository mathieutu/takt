<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the account projects, optionally filtered by client. Only projects active today are listed unless include_inactive is true (ended, upcoming, or belonging to archived clients).')]
#[IsReadOnly]
class ListProjects extends TaktTool
{
    public function handle(Request $request): Response
    {
        $user = $this->user($request);

        $projects = (bool) $request->get('include_inactive')
            ? Project::query()
                ->whereIn('client_id', $user->clients()->withTrashed()->select('id'))
                ->with(['client' => fn ($query) => $query->withTrashed()])
            : $user->projects()->active();

        if ($clientId = $request->get('client_id')) {
            $projects->where('client_id', $clientId);
        }

        return Response::json(
            $projects->orderBy('projects.name')->get()->map(fn (Project $project) => $this->projectData($project))->all()
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'client_id' => $schema->string()->description('Only list the projects of this client.'),
            'include_inactive' => $schema->boolean()->description('Include inactive projects.'),
        ];
    }
}
