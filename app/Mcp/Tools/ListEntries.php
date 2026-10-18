<?php

namespace App\Mcp\Tools;

use App\Models\TimesheetEntry;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the timesheet entries of a project, one per day, optionally between two dates (inclusive). coverage is a percentage of a day (0-100).')]
#[IsReadOnly]
class ListEntries extends TaktTool
{
    public function handle(Request $request): Response
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $entries = $this->findProject($request, (string) $request->get('project_id'))
            ->timesheetEntries()
            ->when($request->get('from'), fn ($query, $from) => $query->whereDate('date', '>=', $from))
            ->when($request->get('to'), fn ($query, $to) => $query->whereDate('date', '<=', $to))
            ->orderBy('date')
            ->get();

        return Response::json($entries->map(fn (TimesheetEntry $entry) => [
            'date' => $entry->date->toDateString(),
            'coverage' => $entry->coverage,
            'billable' => $entry->billable,
            'title' => $entry->title,
            'description' => $entry->description,
        ])->all());
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->string()->description('Project id.')->required(),
            'from' => $schema->string()->description('First date included, YYYY-MM-DD.'),
            'to' => $schema->string()->description('Last date included, YYYY-MM-DD.'),
        ];
    }
}
