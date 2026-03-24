<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            // Clients d'Alice (11111111-...)
            ['name' => 'Mairie de Lyon',          'daily_rate' => 600,  'user_id' => '11111111-1111-1111-1111-111111111111'],
            ['name' => 'Startup InnoTech',         'daily_rate' => 750,  'user_id' => '11111111-1111-1111-1111-111111111111'],
            ['name' => 'Cabinet Lefebvre & Co',    'daily_rate' => 500,  'user_id' => '11111111-1111-1111-1111-111111111111'],

            // Clients de Bob (22222222-...)
            ['name' => 'Agence Créative Studio',   'daily_rate' => 680,  'user_id' => '22222222-2222-2222-2222-222222222222'],
            ['name' => 'École Nationale du Bâtiment', 'daily_rate' => 420, 'user_id' => '22222222-2222-2222-2222-222222222222'],

            // Clients de Claire (33333333-...)
            ['name' => 'Fondation Solidaire',      'daily_rate' => 350,  'user_id' => '33333333-3333-3333-3333-333333333333'],
            ['name' => 'PME Horizon Digital',      'daily_rate' => 800,  'user_id' => '33333333-3333-3333-3333-333333333333'],
        ];

        foreach ($clients as $client) {
            Client::create($client);
        }
    }
}
