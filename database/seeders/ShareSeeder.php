<?php

namespace Database\Seeders;

use App\Models\Share;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ShareSeeder extends Seeder
{
    public function run(): void
    {
        // Partage de projets (share_type: App\Models\Project)
        $sharedProjects = [
            ['share_id' => 1],  // Refonte portail citoyen
            ['share_id' => 3],  // MVP application mobile
            ['share_id' => 9],  // Système de gestion des bénévoles
            ['share_id' => 11], // Migration infrastructure cloud
        ];

        foreach ($sharedProjects as $data) {
            Share::create([
                'id'         => Str::random(32),
                'share_type' => \App\Models\Project::class,
                'share_id'   => $data['share_id'],
            ]);
        }

        // Partage de clients (share_type: App\Models\Client)
        $sharedClients = [
            ['share_id' => 2], // Startup InnoTech
            ['share_id' => 6], // Fondation Solidaire
        ];

        foreach ($sharedClients as $data) {
            Share::create([
                'id'         => Str::random(32),
                'share_type' => \App\Models\Client::class,
                'share_id'   => $data['share_id'],
            ]);
        }
    }
}
