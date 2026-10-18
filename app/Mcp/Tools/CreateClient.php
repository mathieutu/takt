<?php

namespace App\Mcp\Tools;

use App\Actions\CreateClient as CreateClientAction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Create a client. The daily rate is an integer in euro cents (50000 = €500).')]
class CreateClient extends TaktTool
{
    public function handle(Request $request, CreateClientAction $createClient): Response
    {
        $client = $createClient($this->user($request), $request->all());

        return Response::json($client->export(['id', 'name', 'daily_rate']));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Client name.')->required(),
            'daily_rate' => $schema->integer()->min(0)->description('Default daily rate (euro cents).')->required(),
        ];
    }
}
