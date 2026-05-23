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
        $d = fn (int $days): string => now()->subDays($days)->toDateString();

        $alice = User::create([
            'name' => 'Alice Martin',
            'email' => 'alice.martin@example.com',
        ]);

        $mairie = $alice->clients()->create(['name' => 'Mairie de Lyon', 'daily_rate' => 60000]);
        $innotech = $alice->clients()->create(['name' => 'Startup InnoTech', 'daily_rate' => 75000]);
        $cabinet = $alice->clients()->create(['name' => 'Cabinet Lefebvre & Co', 'daily_rate' => 50000]);

        $portail = $mairie->projects()->create([
            'name' => 'Refonte du portail citoyen',
            'daily_rate' => 65000,
            'description' => 'Refonte complète du site web municipal avec un espace citoyen sécurisé et des services en ligne.',
        ]);
        $portail->timesheetEntries()->createMany([
            ['title' => 'Cadrage et spécifications',   'date' => $d(28), 'coverage' => 50,  'description' => 'Atelier avec les équipes métier de la mairie.'],
            ['title' => 'Maquettes UX/UI',             'date' => $d(21), 'coverage' => 100],
            ['title' => 'Développement frontend',      'date' => $d(14), 'coverage' => 100, 'description' => 'Intégration des maquettes validées.'],
            ['title' => 'Développement backend / API', 'date' => $d(7),  'coverage' => 100],
            ['title' => 'Tests et recette',            'date' => $d(2),  'coverage' => 50,  'description' => 'Recette client avec 3 cycles de corrections.'],
        ]);

        $portail->share();

        $audit = $mairie->projects()->create([
            'name' => 'Audit accessibilité numérique',
            'daily_rate' => 60000,
            'description' => "Audit RGAA des services numériques de la mairie et plan d'action correctif.",
        ]);
        $audit->timesheetEntries()->createMany([
            ['title' => 'Analyse des pages existantes', 'date' => $d(25), 'coverage' => 33],
            ['title' => 'Rédaction rapport RGAA',       'date' => $d(10), 'coverage' => 50, 'description' => 'Rapport avec 47 non-conformités identifiées.'],
        ]);

        $mvp = $innotech->projects()->create([
            'name' => 'MVP application mobile',
            'daily_rate' => 80000,
            'description' => 'Développement du produit minimum viable pour la plateforme de mise en relation B2B.',
        ]);
        $mvp->timesheetEntries()->createMany([
            ['title' => 'Architecture technique',      'date' => $d(27), 'coverage' => 33,  'description' => 'Choix stack React Native + Node.js.'],
            ['title' => 'Sprint 1 - Authentification', 'date' => $d(20), 'coverage' => 100],
            ['title' => 'Sprint 2 - Dashboard',        'date' => $d(13), 'coverage' => 100, 'description' => 'Inclusion des graphiques et filtres.'],
            ['title' => 'Sprint 3 - Messagerie',       'date' => $d(5),  'coverage' => 100],
        ]);

        $mvp->share();

        $api = $innotech->projects()->create([
            'name' => 'Intégration API partenaires',
            'daily_rate' => 75000,
        ]);
        $api->timesheetEntries()->createMany([
            ['title' => 'Étude des APIs partenaires', 'date' => $d(22), 'coverage' => 33,  'description' => 'Documentation Stripe, Twilio, Salesforce.'],
            ['title' => 'Développement connecteurs',  'date' => $d(8),  'coverage' => 100],
        ]);

        $rapports = $cabinet->projects()->create([
            'name' => 'Automatisation des rapports mensuels',
            'daily_rate' => 50000,
            'description' => 'Mise en place de scripts de génération automatique des rapports comptables mensuels.',
        ]);
        $rapports->timesheetEntries()->createMany([
            ['title' => 'Analyse des templates existants', 'date' => $d(29), 'coverage' => 33],
            ['title' => 'Développement scripts Python',    'date' => $d(15), 'coverage' => 50, 'description' => 'Scripts de génération PDF via WeasyPrint.'],
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
