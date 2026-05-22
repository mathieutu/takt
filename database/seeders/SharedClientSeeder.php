<?php

namespace Database\Seeders;

use App\Models\SharedClient;
use Illuminate\Database\Seeder;

class SharedClientSeeder extends Seeder
{
    public function run(): void
    {
        // Partages de clients entre comptes
        $sharedClients = [
            [
                'client_id' => 2, // Startup InnoTech (d'Alice) partagé avec Bob
                'account_id' => '22222222-2222-2222-2222-222222222222',
            ],
            [
                'client_id' => 6, // Fondation Solidaire (de Claire) partagée avec Association Verte
                'account_id' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb',
            ],
            [
                'client_id' => 7, // PME Horizon Digital (de Claire) partagée avec TechCorp
                'account_id' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
            ],
        ];

        foreach ($sharedClients as $data) {
            SharedClient::create($data);
        }
    }
}
