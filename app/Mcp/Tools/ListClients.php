<?php

namespace App\Mcp\Tools;

use App\Models\Client;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the account clients (id, name, default daily rate). Archived clients are excluded unless include_archived is true.')]
#[IsReadOnly]
class ListClients extends TaktTool
{
    public function handle(Request $request): Response
    {
        $clients = $this->user($request)->clients();

        if ((bool) $request->get('include_archived')) {
            $clients->withTrashed();
        }

        return Response::json($clients->orderBy('name')->get()->map(fn (Client $client) => [
            ...$client->export(['id', 'name', 'daily_rate']),
            'archived' => $client->deleted_at !== null,
        ])->all());
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'include_archived' => $schema->boolean()->description('Include archived clients.'),
        ];
    }
}
