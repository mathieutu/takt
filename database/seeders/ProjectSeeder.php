<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $projects = [
            // Mairie de Lyon (client_id: 1)
            [
                'name'        => 'Refonte du portail citoyen',
                'client_id'   => 1,
                'daily_rate'  => 650,
                'description' => 'Refonte complète du site web municipal avec un espace citoyen sécurisé et des services en ligne.',
            ],
            [
                'name'        => 'Audit accessibilité numérique',
                'client_id'   => 1,
                'daily_rate'  => null,
                'description' => 'Audit RGAA des services numériques de la mairie et plan d\'action correctif.',
            ],

            // Startup InnoTech (client_id: 2)
            [
                'name'        => 'MVP application mobile',
                'client_id'   => 2,
                'daily_rate'  => 800,
                'description' => 'Développement du produit minimum viable pour la plateforme de mise en relation B2B.',
            ],
            [
                'name'        => 'Intégration API partenaires',
                'client_id'   => 2,
                'daily_rate'  => 750,
                'description' => null,
            ],

            // Cabinet Lefebvre & Co (client_id: 3)
            [
                'name'        => 'Automatisation des rapports mensuels',
                'client_id'   => 3,
                'daily_rate'  => 500,
                'description' => 'Mise en place de scripts de génération automatique des rapports comptables mensuels.',
            ],

            // Agence Créative Studio (client_id: 4)
            [
                'name'        => 'Campagne digitale printemps',
                'client_id'   => 4,
                'daily_rate'  => 700,
                'description' => 'Stratégie et exécution de la campagne marketing digitale pour le lancement de la collection printemps.',
            ],
            [
                'name'        => 'Charte graphique rebranding',
                'client_id'   => 4,
                'daily_rate'  => 680,
                'description' => 'Refonte de l\'identité visuelle complète incluant logo, typographies et palette de couleurs.',
            ],

            // École Nationale du Bâtiment (client_id: 5)
            [
                'name'        => 'Plateforme e-learning',
                'client_id'   => 5,
                'daily_rate'  => 450,
                'description' => 'Développement d\'une plateforme de cours en ligne pour les étudiants en alternance.',
            ],

            // Fondation Solidaire (client_id: 6)
            [
                'name'        => 'Système de gestion des bénévoles',
                'client_id'   => 6,
                'daily_rate'  => null,
                'description' => 'Application web de suivi et coordination des bénévoles et des missions associatives.',
            ],
            [
                'name'        => 'Site de collecte de dons',
                'client_id'   => 6,
                'daily_rate'  => 350,
                'description' => 'Refonte du site de dons avec intégration Stripe et suivi des campagnes de financement.',
            ],

            // PME Horizon Digital (client_id: 7)
            [
                'name'        => 'Migration infrastructure cloud',
                'client_id'   => 7,
                'daily_rate'  => 850,
                'description' => 'Migration des serveurs on-premise vers AWS avec mise en place CI/CD et monitoring.',
            ],
        ];

        foreach ($projects as $project) {
            Project::create($project);
        }
    }
}
