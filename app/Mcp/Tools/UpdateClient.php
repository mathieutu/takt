<?php

namespace App\Mcp\Tools;

use App\Actions\UpdateClient as UpdateClientAction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Description('Update the name and default daily rate of a client. Both fields are required.')]
#[IsIdempotent]
class UpdateClient extends TaktTool
{
    public function handle(Request $request, UpdateClientAction $updateClient): Response
    {
        $client = $updateClient($this->findClient($request, (string) $request->get('client_id')), $request->all());

        return Response::json($client->export(['id', 'name', 'daily_rate']));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'client_id' => $schema->string()->description('Client id.')->required(),
            'name' => $schema->string()->description('Client name.')->required(),
            'daily_rate' => $schema->integer()->min(0)->description('Default daily rate (euro cents).')->required(),
        ];
    }
}
