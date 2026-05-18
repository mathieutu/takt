<?php

namespace Database\Seeders;

use App\Models\View;
use Illuminate\Database\Seeder;

class ViewSeeder extends Seeder
{
    public function run(): void
    {
        $views = [
            // Projet 1 - Refonte portail citoyen
            [
                'title'      => 'Vue globale du projet',
                'project_id' => 1,
                'start_date' => '2026-01-06',
                'end_date'   => '2026-03-31',
                'comments'   => 'Vue complète de toutes les phases du projet de refonte.',
            ],
            [
                'title'      => 'Phase 1 - Design',
                'project_id' => 1,
                'start_date' => '2026-01-06',
                'end_date'   => '2026-01-23',
                'comments'   => null,
            ],

            // Projet 3 - MVP application mobile
            [
                'title'      => 'Roadmap Q1 2026',
                'project_id' => 3,
                'start_date' => '2026-01-05',
                'end_date'   => '2026-03-27',
                'comments'   => 'Planning des 3 sprints de développement du MVP.',
            ],
            [
                'title'      => 'Sprint 1 & 2',
                'project_id' => 3,
                'start_date' => '2026-01-12',
                'end_date'   => '2026-02-06',
                'comments'   => null,
            ],

            // Projet 6 - Campagne digitale
            [
                'title'      => 'Calendrier campagne printemps',
                'project_id' => 6,
                'start_date' => '2026-02-02',
                'end_date'   => '2026-03-31',
                'comments'   => 'Vue de la campagne marketing de bout en bout.',
            ],

            // Projet 9 - Gestion bénévoles
            [
                'title'      => 'Planning développement',
                'project_id' => 9,
                'start_date' => '2026-01-08',
                'end_date'   => '2026-03-06',
                'comments'   => null,
            ],

            // Projet 11 - Migration cloud
            [
                'title'      => 'Plan de migration complet',
                'project_id' => 11,
                'start_date' => '2026-01-05',
                'end_date'   => '2026-03-31',
                'comments'   => 'Vue d\'ensemble de toutes les étapes de migration vers AWS.',
            ],
            [
                'title'      => 'Phase critique - Bases de données',
                'project_id' => 11,
                'start_date' => '2026-02-02',
                'end_date'   => '2026-02-28',
                'comments'   => 'Période de migration des données sensibles avec maintenance réduite.',
            ],
        ];

        foreach ($views as $view) {
            View::create($view);
        }
    }
}
