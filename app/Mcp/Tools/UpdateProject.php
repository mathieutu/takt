<?php

namespace App\Mcp\Tools;

use App\Actions\UpdateProject as UpdateProjectAction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Description('Update a project. name, client_id and start_date are required (reuse the current values from get_project). To change the rate or the monthly budget from a given date, pass the value and its effective date (today by default): the history is updated, nothing is overwritten.')]
#[IsIdempotent]
class UpdateProject extends TaktTool
{
    public function handle(Request $request, UpdateProjectAction $updateProject): Response
    {
        $project = $this->findProject($request, (string) $request->get('project_id'));
        $client = $this->findClient($request, (string) $request->get('client_id'));

        $project = $updateProject($project, [...$request->all(), 'client_id' => $client->id]);

        return Response::json($this->projectData($project->refresh()));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->string()->description('Project id.')->required(),
            'name' => $schema->string()->description('Project name.')->required(),
            'client_id' => $schema->string()->description('Id of the owning client.')->required(),
            'start_date' => $schema->string()->description('Start date, YYYY-MM-DD.')->required(),
            'end_date' => $schema->string()->description('End date, YYYY-MM-DD, empty if ongoing.'),
            'description' => $schema->string(),
            'daily_rate' => $schema->integer()->min(0)->description('New daily rate (euro cents).'),
            'daily_rate_effective_date' => $schema->string()->description('Effective date of the new rate, YYYY-MM-DD (today by default).'),
            'max_month_budget' => $schema->integer()->min(0)->description('New maximum monthly budget, euro cents (0 = unlimited).'),
            'monthly_budget_effective_date' => $schema->string()->description('Effective date of the new monthly budget, YYYY-MM-DD (today by default).'),
            'max_total_budget' => $schema->integer()->min(1)->description('Maximum total budget (euro cents).'),
        ];
    }
}
