<?php

namespace Database\Seeders;

use App\Models\SharedClient;
use Illuminate\Database\Seeder;

class SharedClientSeeder extends Seeder
{
    public function run(): void
    {
        $sharedClients = [
            [
                'client_id' => 2, // Startup InnoTech (d'Alice) partagé avec Bob
                'user_id' => '22222222-2222-2222-2222-222222222222',
            ],
        ];

        foreach ($sharedClients as $data) {
            SharedClient::create($data);
        }
    }
}
