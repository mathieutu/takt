<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->seedAlice();
        $this->seedBob();
    }

    private function seedAlice(): void
    {
        $alice = User::create([
            'name' => 'Alice Martin',
            'email' => 'alice.martin@example.com',
        ]);

        $mairie = $alice->clients()->create(['name' => 'Mairie de Lyon', 'daily_rate' => 600]);
        $innotech = $alice->clients()->create(['name' => 'Startup InnoTech', 'daily_rate' => 750]);
        $cabinet = $alice->clients()->create(['name' => 'Cabinet Lefebvre & Co', 'daily_rate' => 500]);

        $portail = $mairie->projects()->create([
            'name' => 'Refonte du portail citoyen',
            'daily_rate' => 650,
            'description' => 'Refonte complète du site web municipal avec un espace citoyen sécurisé et des services en ligne.',
        ]);
        $portail->activityTimes()->createMany([
            ['label' => 'Cadrage et spécifications',   'start_date' => '2026-01-06', 'day_coverage' => 2, 'comments' => 'Atelier avec les équipes métier de la mairie.'],
            ['label' => 'Maquettes UX/UI',             'start_date' => '2026-01-13', 'day_coverage' => 3],
            ['label' => 'Développement frontend',      'start_date' => '2026-01-27', 'day_coverage' => 5, 'comments' => 'Intégration des maquettes validées.'],
            ['label' => 'Développement backend / API', 'start_date' => '2026-02-10', 'day_coverage' => 4],
            ['label' => 'Tests et recette',            'start_date' => '2026-02-24', 'day_coverage' => 2, 'comments' => 'Recette client avec 3 cycles de corrections.'],
        ]);

        $portail->share();

        $audit = $mairie->projects()->create([
            'name' => 'Audit accessibilité numérique',
            'description' => "Audit RGAA des services numériques de la mairie et plan d'action correctif.",
        ]);
        $audit->activityTimes()->createMany([
            ['label' => 'Analyse des pages existantes', 'start_date' => '2026-01-05', 'day_coverage' => 1],
            ['label' => 'Rédaction rapport RGAA',       'start_date' => '2026-01-12', 'day_coverage' => 1, 'comments' => 'Rapport avec 47 non-conformités identifiées.'],
        ]);

        $mvp = $innotech->projects()->create([
            'name' => 'MVP application mobile',
            'daily_rate' => 800,
            'description' => 'Développement du produit minimum viable pour la plateforme de mise en relation B2B.',
        ]);
        $mvp->activityTimes()->createMany([
            ['label' => 'Architecture technique',      'start_date' => '2026-01-05', 'day_coverage' => 1, 'comments' => 'Choix stack React Native + Node.js.'],
            ['label' => 'Sprint 1 - Authentification', 'start_date' => '2026-01-12', 'day_coverage' => 3],
            ['label' => 'Sprint 2 - Dashboard',        'start_date' => '2026-01-26', 'day_coverage' => 4, 'comments' => 'Inclusion des graphiques et filtres.'],
            ['label' => 'Sprint 3 - Messagerie',       'start_date' => '2026-02-09', 'day_coverage' => 3],
        ]);

        $mvp->share();

        $api = $innotech->projects()->create([
            'name' => 'Intégration API partenaires',
            'daily_rate' => 750,
        ]);
        $api->activityTimes()->createMany([
            ['label' => 'Étude des APIs partenaires', 'start_date' => '2026-02-02', 'day_coverage' => 1, 'comments' => 'Documentation Stripe, Twilio, Salesforce.'],
            ['label' => 'Développement connecteurs',  'start_date' => '2026-02-09', 'day_coverage' => 4],
        ]);

        $rapports = $cabinet->projects()->create([
            'name' => 'Automatisation des rapports mensuels',
            'daily_rate' => 500,
            'description' => 'Mise en place de scripts de génération automatique des rapports comptables mensuels.',
        ]);
        $rapports->activityTimes()->createMany([
            ['label' => 'Analyse des templates existants', 'start_date' => '2026-01-08', 'day_coverage' => 1],
            ['label' => 'Développement scripts Python',    'start_date' => '2026-01-15', 'day_coverage' => 2, 'comments' => 'Scripts de génération PDF via WeasyPrint.'],
        ]);
    }

    private function seedBob(): void
    {
        User::create([
            'name' => 'Bob Dupont',
            'email' => 'bob.dupont@example.com',
        ]);
    }
}
