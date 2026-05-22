<?php

namespace Database\Seeders;

use App\Models\SharedProject;
use Illuminate\Database\Seeder;

class SharedProjectSeeder extends Seeder
{
    public function run(): void
    {
        $sharedProjects = [
            [
                'user_id' => '22222222-2222-2222-2222-222222222222', // Bob
                'project_id' => 1, // Refonte portail citoyen (d'Alice)
            ],
            [
                'user_id' => '33333333-3333-3333-3333-333333333333', // Claire
                'project_id' => 3, // MVP application mobile (d'Alice)
            ],
        ];

        foreach ($sharedProjects as $data) {
            SharedProject::create($data);
        }
    }
}
