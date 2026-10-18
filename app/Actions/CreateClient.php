<?php

namespace App\Actions;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class CreateClient
{
    /**
     * @param  array{name?: string, daily_rate?: int|string}  $input
     */
    public function __invoke(User $user, array $input): Client
    {
        $data = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'daily_rate' => ['required', 'integer', 'min:0'],
        ])->validate();

        return $user->clients()->create($data);
    }
}
