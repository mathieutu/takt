<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Project details: current configuration, history of daily rates and monthly budgets (effective date => value map), number of entries and invoices.')]
#[IsReadOnly]
class GetProject extends TaktTool
{
    public function handle(Request $request): Response
    {
        $project = $this->findProject($request, (string) $request->get('project_id'));

        return Response::json([
            ...$this->projectData($project),
            'daily_rates' => $project->daily_rates,
            'monthly_budgets' => $project->monthly_budgets,
            'entries_count' => $project->timesheetEntries()->count(),
            'invoices_count' => $project->invoices()->count(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->string()->description('Project id.')->required(),
        ];
    }
}
