<?php

namespace App\Actions;

use App\Models\Client;
use Illuminate\Support\Facades\Validator;

class UpdateClient
{
    /**
     * @param  array{name?: string, daily_rate?: int|string}  $input
     */
    public function __invoke(Client $client, array $input): Client
    {
        $client->update(Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'daily_rate' => ['required', 'integer', 'min:0'],
        ])->validate());

        return $client;
    }
}
