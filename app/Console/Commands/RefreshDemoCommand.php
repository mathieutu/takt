<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimesheetEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RefreshDemoCommand extends Command
{
    protected $signature = 'app:demo-refresh';

    protected $description = 'Seed the demo account with realistic data';

    public const string DEMO_EMAIL = 'contact+takt@mathieutu.dev';

    /** Shared pool of available days per month, consumed across all projects. */
    private array $monthSchedules = [];

    public function handle(): void
    {
        $this->cleanExistingDemoData();

        $demo = User::create([
            'name' => 'Compte DEMO',
            'email' => self::DEMO_EMAIL,
            'avatar' => 'https://github.com/mathieutu.png',
        ]);

        $this->seedAgenceCreativeStudio($demo);
        $this->seedAssociationNumeriquePourTous($demo);
        $this->seedLyceeTechniqueJeanMoulin($demo);
        $this->seedStartupHub($demo);
        $this->seedArchivedClient($demo);

        $this->info('Compte de démo recréé avec succès.');
    }

    private function cleanExistingDemoData(): void
    {
        $user = User::where('email', self::DEMO_EMAIL)->first();

        if (! $user) {
            return;
        }

        $clientIds = $user->clients()->withTrashed()->pluck('id');
        $projectIds = Project::whereIn('client_id', $clientIds)->withTrashed()->pluck('id');

        TimesheetEntry::whereIn('project_id', $projectIds)->delete();
        Invoice::whereIn('project_id', $projectIds)->delete();
        Project::whereIn('id', $projectIds)->withTrashed()->forceDelete();
        Client::whereIn('id', $clientIds)->withTrashed()->forceDelete();
        $user->delete();
    }

    /** Date string N months ago. */
    private function d(int $months, int $weeks = 0): string
    {
        return now()->subMonths($months)->subWeeks($weeks)->toDateString();
    }

    /** Date string for day $day of the month $monthsAgo months ago (1-indexed). */
    private function dm(int $monthsAgo, int $dayOfMonth): string
    {
        return now()->subMonths($monthsAgo)->startOfMonth()->addDays($dayOfMonth - 1)->toDateString();
    }

    /** CarbonImmutable N months ago, for invoice created_at. */
    private function dt(int $months, int $weeks = 0): CarbonImmutable
    {
        return now()->subMonths($months)->subWeeks($weeks);
    }

    /**
     * Pick $min–$max unique days from the shared month pool (days 2–26).
     * Each day can only be assigned to one project per month, preventing coverage > 100/day.
     *
     * @return array<int>
     */
    private function getDays(int $monthsAgo, int $min, int $max): array
    {
        if (! isset($this->monthSchedules[$monthsAgo])) {
            $pool = range(2, 26);
            shuffle($pool);
            $this->monthSchedules[$monthsAgo] = $pool;
        }

        $today = (int) now()->format('j');
        $count = min(rand($min, $max), count($this->monthSchedules[$monthsAgo]));
        $days = array_splice($this->monthSchedules[$monthsAgo], 0, $count);

        if ($monthsAgo === 0) {
            $days = array_values(array_filter($days, fn ($day) => $day < $today));
        }

        sort($days);

        return $days;
    }

    /**
     * Generate timesheet entries with a random number of days per month.
     *
     * @param  array<string>  $titles
     * @return array<array<string, mixed>>
     */
    private function generateEntries(int $fromMonthsAgo, int $toMonthsAgo, int $minDays, int $maxDays, array $titles): array
    {
        $entries = [];
        $i = 0;
        $coverages = [100, 100, 100, 100, 75, 50];

        for ($m = $fromMonthsAgo; $m >= $toMonthsAgo; $m--) {
            foreach ($this->getDays($m, $minDays, $maxDays) as $day) {
                $entries[] = [
                    'date' => $this->dm($m, $day),
                    'title' => $titles[$i++ % count($titles)],
                    'coverage' => $coverages[array_rand($coverages)],
                ];
            }
        }

        return $entries;
    }

    private function seedAgenceCreativeStudio(User $user): void
    {
        $agence = $user->clients()->create([
            'name' => 'Agence Créative Studio',
            'daily_rate' => 50000,
            'share_token' => (string) Str::uuid(),
        ]);

        $refonte = $agence->projects()->create([
            'name' => 'Refonte site corporate',
            'daily_rate' => 50000,
            'description' => 'Refonte complète du site vitrine : audit, design, développement et mise en production.',
            'created_at' => now()->subMonths(18),
        ]);
        $refonte->timesheetEntries()->createMany(
            $this->generateEntries(18, 8, 2, 5, [
                'Intégration HTML/CSS',
                'Développement front-end',
                'Revue et corrections',
                'Tests et recette',
            ])
        );
        $refonte->invoices()->createMany([
            ['amount' => 400000, 'paid_at' => $this->d(14), 'created_at' => $this->dt(15),    'notes' => 'Facture ACS-001 — Phase 1 : cadrage et maquettes'],
            ['amount' => 450000, 'paid_at' => $this->d(10), 'created_at' => $this->dt(11),    'notes' => 'Facture ACS-002 — Phase 2 : développement'],
            ['amount' => 250000, 'paid_at' => $this->d(7),  'created_at' => $this->dt(8),     'notes' => 'Facture ACS-003 — Phase 3 : mise en production'],
        ]);
        $refonte->delete();
        $refonte->update(['deleted_at' => $this->dt(8)]);

        $campagne = $agence->projects()->create([
            'name' => 'Campagne réseaux sociaux',
            'daily_rate' => 50000,
            'description' => 'Stratégie éditoriale, création de contenus et production vidéo pour les réseaux sociaux.',
            'created_at' => now()->subMonths(6),
        ]);
        $campagne->timesheetEntries()->createMany(
            $this->generateEntries(6, 2, 2, 5, [
                'Stratégie et planification',
                'Production contenus',
                'Montage vidéo',
                'Bilan et reporting',
            ])
        );
        $campagne->invoices()->createMany([
            ['amount' => 250000, 'paid_at' => $this->d(4), 'created_at' => $this->dt(4, 2), 'notes' => 'Facture ACS-004 — Phase 1 : stratégie et création'],
            ['amount' => 250000, 'paid_at' => $this->d(1), 'created_at' => $this->dt(1, 2), 'notes' => 'Facture ACS-005 — Phase 2 : contenus et bilan'],
        ]);
    }

    private function seedAssociationNumeriquePourTous(User $user): void
    {
        $asso = $user->clients()->create([
            'name' => 'Association Numérique Pour Tous',
            'daily_rate' => 40000,
            'share_token' => (string) Str::uuid(),
        ]);

        $audit = $asso->projects()->create([
            'name' => 'Audit et refonte du SI associatif',
            'daily_rate' => 40000,
            'description' => "Audit du système d'information existant et migration vers des outils libres et collaboratifs.",
            'created_at' => now()->subMonths(20),
        ]);
        $audit->timesheetEntries()->createMany(
            $this->generateEntries(20, 15, 2, 5, [
                'Cartographie et analyse',
                "Rédaction rapport d'audit",
                'Présentation et atelier',
            ])
        );
        $audit->invoices()->createMany([
            ['amount' => 240000, 'paid_at' => $this->d(17), 'created_at' => $this->dt(18), 'notes' => 'Facture ANT-001 — Audit et spécifications'],
            ['amount' => 240000, 'paid_at' => $this->d(13), 'created_at' => $this->dt(14), 'notes' => 'Facture ANT-002 — Déploiement et formation'],
        ]);
        $audit->delete();
        $audit->update(['deleted_at' => $this->dt(14)]);

        $gestion = $asso->projects()->create([
            'name' => 'Outil de gestion des adhérents',
            'daily_rate' => 40000,
            'max_total_budget' => 2000000,
            'description' => "Développement d'une application web sur mesure pour gérer les adhésions, cotisations et événements.",
            'created_at' => now()->subMonths(10),
        ]);
        $gestion->timesheetEntries()->createMany(
            $this->generateEntries(10, 0, 3, 7, [
                'Développement',
                'Revue de code',
                'Tests',
                'Déploiement et livraison',
                'Réunion de suivi',
            ])
        );
        $gestion->invoices()->createMany([
            ['amount' => 720000, 'paid_at' => $this->d(8), 'created_at' => $this->dt(9),          'notes' => 'Facture ANT-003 — Lot 1 : conception et module adhérents'],
            ['amount' => 640000, 'paid_at' => $this->d(5), 'created_at' => $this->dt(6),          'notes' => 'Facture ANT-004 — Lot 2 : cotisations, événements et admin'],
            ['amount' => 360000, 'paid_at' => null,         'created_at' => now()->subDays(40),    'notes' => 'Facture ANT-005 — Lot 3 : tests, déploiement et support'],
        ]);
    }

    private function seedLyceeTechniqueJeanMoulin(User $user): void
    {
        $lycee = $user->clients()->create([
            'name' => 'Lycée Technique Jean Moulin',
            'daily_rate' => 45000,
        ]);

        $bts2425 = $lycee->projects()->create([
            'name' => 'Formation dev web — BTS SIO 2024/2025',
            'daily_rate' => 45000,
            'max_month_budget' => 495000,
            'description' => 'Cours de développement web pour les BTS SIO option SLAM : HTML/CSS, JavaScript, PHP et projet fil rouge.',
            'created_at' => now()->subMonths(21),
        ]);
        $bts2425->timesheetEntries()->createMany(
            $this->generateEntries(21, 12, 4, 10, [
                'Cours — HTML/CSS',
                'Cours — JavaScript',
                'Cours — PHP',
                'Cours — SQL et bases de données',
                'TP encadré',
                'Accompagnement projet étudiant',
            ])
        );
        $bts2425->invoices()->createMany([
            ['amount' => 360000, 'paid_at' => $this->d(19), 'created_at' => $this->dt(20),    'notes' => 'Facture LTM-001 — BTS SIO 2024/2025 — Trimestre 1'],
            ['amount' => 360000, 'paid_at' => $this->d(17), 'created_at' => $this->dt(17, 2), 'notes' => 'Facture LTM-002 — BTS SIO 2024/2025 — Trimestre 2'],
            ['amount' => 360000, 'paid_at' => $this->d(15), 'created_at' => $this->dt(15, 2), 'notes' => 'Facture LTM-003 — BTS SIO 2024/2025 — Trimestre 3'],
            ['amount' => 360000, 'paid_at' => $this->d(13), 'created_at' => $this->dt(13, 2), 'notes' => 'Facture LTM-004 — BTS SIO 2024/2025 — Trimestre 4'],
            ['amount' => 360000, 'paid_at' => $this->d(12), 'created_at' => $this->dt(12, 2), 'notes' => 'Facture LTM-005 — BTS SIO 2024/2025 — Solde'],
        ]);
        $bts2425->delete();
        $bts2425->update(['deleted_at' => $this->dt(12)]);

        $bts2526 = $lycee->projects()->create([
            'name' => 'Formation dev web — BTS SIO 2025/2026',
            'daily_rate' => 45000,
            'max_month_budget' => 495000,
            'description' => 'Cours de développement web pour les BTS SIO option SLAM, année 2025/2026.',
            'created_at' => now()->subMonths(9),
        ]);
        $bts2526->timesheetEntries()->createMany(
            $this->generateEntries(9, 0, 4, 10, [
                'Cours — HTML/CSS',
                'Cours — JavaScript',
                'Cours — PHP',
                'Cours — SQL et bases de données',
                'TP encadré',
                'Accompagnement projet étudiant',
            ])
        );
        $bts2526->invoices()->createMany([
            ['amount' => 900000, 'paid_at' => $this->d(7), 'created_at' => $this->dt(7, 2), 'notes' => 'Facture LTM-006 — BTS SIO 2025/2026 — Trimestres 1 et 2'],
            ['amount' => 900000, 'paid_at' => null,         'created_at' => now()->subDays(10), 'notes' => 'Facture LTM-007 — BTS SIO 2025/2026 — Trimestres 3 et 4'],
        ]);
    }

    private function seedStartupHub(User $user): void
    {
        $startup = $user->clients()->create([
            'name' => 'StartupHub',
            'daily_rate' => 60000,
        ]);

        $mvp = $startup->projects()->create([
            'name' => 'MVP plateforme SaaS',
            'daily_rate' => 60000,
            'max_month_budget' => 660000,
            'description' => "Développement du produit minimum viable d'une plateforme SaaS B2B de gestion de projets agiles.",
            'created_at' => now()->subMonths(12),
        ]);
        $mvp->timesheetEntries()->createMany(
            $this->generateEntries(12, 6, 4, 10, [
                'Sprint — Développement feature',
                'Sprint — Revue et corrections',
                'Tests unitaires et intégration',
                'Déploiement et monitoring',
                'Réunion sprint et planification',
            ])
        );
        $mvp->invoices()->createMany([
            ['amount' => 560000, 'paid_at' => $this->d(9), 'created_at' => $this->dt(10),   'notes' => 'Facture SH-001 — Sprints 1 et 2'],
            ['amount' => 720000, 'paid_at' => $this->d(6), 'created_at' => $this->dt(7),    'notes' => 'Facture SH-002 — Sprints 3 et 4'],
            ['amount' => 400000, 'paid_at' => $this->d(5), 'created_at' => $this->dt(5, 2), 'notes' => 'Facture SH-003 — Lancement et monitoring'],
        ]);

        $api = $startup->projects()->create([
            'name' => 'API publique v2',
            'daily_rate' => 60000,
            'max_total_budget' => 630000,
            'description' => "Refonte complète de l'API publique avec authentification OAuth2, nouveaux endpoints et SDKs.",
            'created_at' => now()->subMonths(3),
        ]);
        $api->timesheetEntries()->createMany(
            $this->generateEntries(3, 0, 3, 5, [
                'Développement endpoint',
                "Tests d'intégration",
                'Documentation OpenAPI',
                'Authentification et sécurité',
            ])
        );
        $api->invoices()->createMany([
            ['amount' => 360000, 'paid_at' => $this->d(1), 'created_at' => $this->dt(1, 2), 'notes' => 'Facture SH-004 — Lot 1 : conception et authentification'],
            ['amount' => 300000, 'paid_at' => null,         'created_at' => now()->subDays(5), 'notes' => 'Facture SH-005 — Lot 2 : endpoints et SDK'],
        ]);
    }

    private function seedArchivedClient(User $user): void
    {
        $leroux = $user->clients()->create([
            'name' => 'Leroux & Fils Conseil',
            'daily_rate' => 55000,
        ]);

        $rh = $leroux->projects()->create([
            'name' => 'Refonte processus RH',
            'daily_rate' => 55000,
            'description' => 'Audit et modernisation des processus RH : recrutement, onboarding et évaluation.',
            'created_at' => now()->subMonths(30),
        ]);
        $rh->timesheetEntries()->createMany(
            $this->generateEntries(30, 28, 1, 3, [
                'Entretiens et cartographie',
                "Rédaction du rapport d'audit",
                'Présentation des recommandations',
            ])
        );
        $rh->invoices()->createMany([
            ['amount' => 165000, 'paid_at' => $this->d(28), 'created_at' => $this->dt(29),    'notes' => 'Facture LRC-001 — Audit RH'],
            ['amount' => 110000, 'paid_at' => $this->d(27), 'created_at' => $this->dt(27, 2), 'notes' => 'Facture LRC-002 — Accompagnement mise en œuvre'],
        ]);
        $rh->delete();
        $rh->update(['deleted_at' => $this->dt(27)]);

        $formation = $leroux->projects()->create([
            'name' => 'Formation management intermédiaire',
            'daily_rate' => 55000,
            'description' => 'Parcours de formation pour les managers de proximité : communication, délégation et gestion des conflits.',
            'created_at' => now()->subMonths(27),
        ]);
        $formation->timesheetEntries()->createMany(
            $this->generateEntries(27, 25, 1, 3, [
                'Session — Communication managériale',
                'Session — Délégation efficace',
                'Session — Gestion des conflits',
            ])
        );
        $formation->invoices()->createMany([
            ['amount' => 330000, 'paid_at' => $this->d(25), 'created_at' => $this->dt(26), 'notes' => 'Facture LRC-003 — Formation management'],
        ]);
        $formation->delete();
        $formation->update(['deleted_at' => $this->dt(24)]);

        $leroux->delete();
        $leroux->update(['deleted_at' => $this->dt(24)]);
    }
}
