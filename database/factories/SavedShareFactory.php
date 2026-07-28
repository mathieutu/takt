<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\SavedShare;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SavedShare>
 */
class SavedShareFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'client_id' => Client::factory(),
            'token' => (string) Str::uuid(),
        ];
    }
}
