<?php

namespace App\Mcp\Tools;

use App\Actions\CreateProject as CreateProjectAction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Create a project for an existing client. start_date defaults to today: to record entries in the past, pass a start_date before the first entry. Budgets are integers in euro cents, empty for unlimited.')]
class CreateProject extends TaktTool
{
    public function handle(Request $request, CreateProjectAction $createProject): Response
    {
        $client = $this->findClient($request, (string) $request->get('client_id'));

        $project = $createProject($this->user($request), [...$request->all(), 'client_id' => $client->id]);

        return Response::json($this->projectData($project));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'client_id' => $schema->string()->description('Client id (see list_clients).')->required(),
            'name' => $schema->string()->description('Project name.')->required(),
            'description' => $schema->string()->description('Short description.'),
            'daily_rate' => $schema->integer()->min(0)->description('Project daily rate (euro cents).'),
            'max_month_budget' => $schema->integer()->min(0)->description('Maximum monthly budget (euro cents).'),
            'max_total_budget' => $schema->integer()->min(1)->description('Maximum total budget (euro cents).'),
            'start_date' => $schema->string()->description('Start date, YYYY-MM-DD.'),
        ];
    }
}
