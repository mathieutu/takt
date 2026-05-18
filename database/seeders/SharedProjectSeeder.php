<?php

namespace Database\Seeders;

use App\Models\SharedProject;
use Illuminate\Database\Seeder;

class SharedProjectSeeder extends Seeder
{
    public function run(): void
    {
        // Bob (22222222) et Claire (33333333) ont accès au projet d'Alice (projet 1 & 3)
        // TechCorp (aaaaaaaa) a accès au projet de migration (projet 11)
        $sharedProjects = [
            [
                'account_id' => '22222222-2222-2222-2222-222222222222',
                'project_id' => 1, // Refonte portail citoyen
            ],
            [
                'account_id' => '33333333-3333-3333-3333-333333333333',
                'project_id' => 3, // MVP application mobile
            ],
            [
                'account_id' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
                'project_id' => 11, // Migration cloud
            ],
            [
                'account_id' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb',
                'project_id' => 9, // Gestion bénévoles
            ],
        ];

        foreach ($sharedProjects as $data) {
            SharedProject::create($data);
        }
    }
}
