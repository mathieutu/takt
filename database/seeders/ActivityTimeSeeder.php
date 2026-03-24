<?php

namespace Database\Seeders;

use App\Models\ActivityTime;
use Illuminate\Database\Seeder;

class ActivityTimeSeeder extends Seeder
{
    public function run(): void
    {
        $activities = [
            // Projet 1 - Refonte portail citoyen
            ['project_id' => 1, 'label' => 'Cadrage et spécifications',     'start_date' => '2026-01-06', 'day_coverage' => 2, 'comments' => 'Atelier avec les équipes métier de la mairie.'],
            ['project_id' => 1, 'label' => 'Maquettes UX/UI',               'start_date' => '2026-01-13', 'day_coverage' => 3, 'comments' => null],
            ['project_id' => 1, 'label' => 'Développement frontend',        'start_date' => '2026-01-27', 'day_coverage' => 5, 'comments' => 'Intégration des maquettes validées.'],
            ['project_id' => 1, 'label' => 'Développement backend / API',   'start_date' => '2026-02-10', 'day_coverage' => 4, 'comments' => null],
            ['project_id' => 1, 'label' => 'Tests et recette',              'start_date' => '2026-02-24', 'day_coverage' => 2, 'comments' => 'Recette client avec 3 cycles de corrections.'],

            // Projet 2 - Audit accessibilité
            ['project_id' => 2, 'label' => 'Analyse des pages existantes',  'start_date' => '2026-01-05', 'day_coverage' => 1, 'comments' => null],
            ['project_id' => 2, 'label' => 'Rédaction rapport RGAA',        'start_date' => '2026-01-12', 'day_coverage' => 1, 'comments' => 'Rapport avec 47 non-conformités identifiées.'],

            // Projet 3 - MVP application mobile
            ['project_id' => 3, 'label' => 'Architecture technique',        'start_date' => '2026-01-05', 'day_coverage' => 1, 'comments' => 'Choix stack React Native + Node.js.'],
            ['project_id' => 3, 'label' => 'Sprint 1 - Authentification',   'start_date' => '2026-01-12', 'day_coverage' => 3, 'comments' => null],
            ['project_id' => 3, 'label' => 'Sprint 2 - Dashboard',          'start_date' => '2026-01-26', 'day_coverage' => 4, 'comments' => 'Inclusion des graphiques et filtres.'],
            ['project_id' => 3, 'label' => 'Sprint 3 - Messagerie',         'start_date' => '2026-02-09', 'day_coverage' => 3, 'comments' => null],

            // Projet 4 - Intégration API
            ['project_id' => 4, 'label' => 'Étude des APIs partenaires',    'start_date' => '2026-02-02', 'day_coverage' => 1, 'comments' => 'Documentation Stripe, Twilio, Salesforce.'],
            ['project_id' => 4, 'label' => 'Développement connecteurs',     'start_date' => '2026-02-09', 'day_coverage' => 4, 'comments' => null],

            // Projet 5 - Automatisation rapports
            ['project_id' => 5, 'label' => 'Analyse des templates existants', 'start_date' => '2026-01-08', 'day_coverage' => 1, 'comments' => null],
            ['project_id' => 5, 'label' => 'Développement scripts Python',  'start_date' => '2026-01-15', 'day_coverage' => 2, 'comments' => 'Scripts de génération PDF via WeasyPrint.'],

            // Projet 6 - Campagne digitale
            ['project_id' => 6, 'label' => 'Stratégie et calendrier éditorial', 'start_date' => '2026-02-02', 'day_coverage' => 1, 'comments' => null],
            ['project_id' => 6, 'label' => 'Création des visuels',          'start_date' => '2026-02-09', 'day_coverage' => 2, 'comments' => '15 visuels pour réseaux sociaux et bannières web.'],
            ['project_id' => 6, 'label' => 'Paramétrage campagnes Google Ads', 'start_date' => '2026-02-16', 'day_coverage' => 1, 'comments' => null],

            // Projet 7 - Charte graphique
            ['project_id' => 7, 'label' => 'Benchmark concurrentiel',       'start_date' => '2026-01-07', 'day_coverage' => 1, 'comments' => null],
            ['project_id' => 7, 'label' => 'Proposition 3 concepts logo',   'start_date' => '2026-01-14', 'day_coverage' => 2, 'comments' => 'Concept C retenu après vote interne client.'],
            ['project_id' => 7, 'label' => 'Déclinaisons et guidelines',    'start_date' => '2026-02-04', 'day_coverage' => 3, 'comments' => null],

            // Projet 8 - Plateforme e-learning
            ['project_id' => 8, 'label' => 'Installation Moodle',           'start_date' => '2026-01-05', 'day_coverage' => 1, 'comments' => null],
            ['project_id' => 8, 'label' => 'Personnalisation thème',        'start_date' => '2026-01-12', 'day_coverage' => 2, 'comments' => 'Adaptation charte graphique de l\'école.'],
            ['project_id' => 8, 'label' => 'Formation équipe pédagogique',  'start_date' => '2026-02-03', 'day_coverage' => 1, 'comments' => 'Formation de 8 formateurs.'],

            // Projet 9 - Gestion bénévoles
            ['project_id' => 9, 'label' => 'Recueil des besoins',           'start_date' => '2026-01-08', 'day_coverage' => 1, 'comments' => 'Interviews de 5 coordinateurs bénévoles.'],
            ['project_id' => 9, 'label' => 'Développement application',     'start_date' => '2026-01-22', 'day_coverage' => 5, 'comments' => null],
            ['project_id' => 9, 'label' => 'Déploiement et formation',      'start_date' => '2026-02-26', 'day_coverage' => 1, 'comments' => null],

            // Projet 10 - Site dons
            ['project_id' => 10, 'label' => 'Intégration Stripe',          'start_date' => '2026-01-15', 'day_coverage' => 2, 'comments' => 'Paiement unique et dons récurrents.'],
            ['project_id' => 10, 'label' => 'Pages campagnes de dons',     'start_date' => '2026-01-26', 'day_coverage' => 2, 'comments' => null],

            // Projet 11 - Migration cloud
            ['project_id' => 11, 'label' => 'Audit infrastructure existante', 'start_date' => '2026-01-05', 'day_coverage' => 2, 'comments' => '12 serveurs physiques à migrer.'],
            ['project_id' => 11, 'label' => 'Architecture AWS',             'start_date' => '2026-01-19', 'day_coverage' => 2, 'comments' => null],
            ['project_id' => 11, 'label' => 'Migration bases de données',   'start_date' => '2026-02-02', 'day_coverage' => 3, 'comments' => 'Migration PostgreSQL avec RDS Multi-AZ.'],
            ['project_id' => 11, 'label' => 'Mise en place CI/CD',         'start_date' => '2026-02-16', 'day_coverage' => 2, 'comments' => 'GitHub Actions + déploiement automatique sur ECS.'],
            ['project_id' => 11, 'label' => 'Tests de charge et bascule',  'start_date' => '2026-03-02', 'day_coverage' => 2, 'comments' => null],
        ];

        foreach ($activities as $activity) {
            ActivityTime::create($activity);
        }
    }
}
