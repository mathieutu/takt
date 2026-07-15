# Export PDF de la page Billing — Plan complet

Ce document est **autonome et complet** : il documente l'implémentation de bout en bout de l'export PDF de la page Billing, y compris toutes les décisions déjà tranchées après une session de grilling approfondie (vérification contre le code réel, pas des suppositions). Pas besoin d'autre document pour implémenter cette feature — tout est ici.

## Suivi d'avancement

> Les agents qui implémentent ce plan doivent **cocher les cases ci-dessous au fur et à mesure**, et committer ces mises à jour avec le code correspondant (ou au minimum avant de rendre la main en fin de session). Ce fichier, versionné dans le repo, est la source de vérité persistante entre les sessions — contrairement aux todo-lists éphémères (TaskCreate/TaskUpdate) qui ne survivent pas d'une session à l'autre. Les points en **gras** sont des points de contrôle utilisateur obligatoires : ne pas les cocher soi-même, attendre le retour explicite de l'utilisateur.

### Phase 0 — Contrat & scaffolding
- [x] `config/services.php` + `.env.example`
- [x] `app/Services/PdfGenerator.php`
- [x] `app/Http/Requests/ExportBillingRequest.php`
- [x] `BuildsProjectBillingEntry::buildBillingExportViewData()` / `buildBillingExportResponse()` / `buildExportFilename()`
- [x] `ClientController::exportBilling()` / `previewBillingExport()` / `resolveExportProjects()`
- [x] `ExportSharedBillingHandler`
- [x] Routes (`clients.billing.export`, `clients.billing.export.preview`, `shares.billing.export`) + throttle
- [x] Wayfinder régénéré (helpers TS disponibles)
- [x] Tests Pest `ExportBillingTest` (tous les cas listés en Phase 0)
- [x] `vendor/bin/pint --dirty --format agent` passé

### Phase 1 — MVP bout en bout
- [x] `resources/views/exports/billing.blade.php` (version minimale, formatage brut)
- [x] `resources/js/types/billing.ts`
- [x] `resources/js/components/ExportBillingModal.vue`
- [x] `ProjectBillingPage.vue` (bouton export, prop `token`, types extraits)
- [x] `ShowSharedHandler.php` (prop `token` ajoutée)
- [x] **Checklist manuelle MVP validée par l'utilisateur** (point de contrôle obligatoire avant Phase 2)

### Phase 2 — Itération design
- [ ] `resources/js/utils/number.ts` (locale `fr-FR` fixée)
- [ ] `resources/js/exports/billing-pdf.ts` (point d'entrée Vite, formatage réel injecté)
- [ ] `vite.config.ts` (entrée ajoutée au tableau `input`)
- [ ] CSS compilé inliné dans le Blade (`Vite::content('resources/css/app.css')`)
- [ ] Design du calendrier finalisé (skills `dataviz` + `frontend-design` activés, cohérent thème `pink`)
- [ ] **Preview HTML validée par l'utilisateur** (boucle d'itération, possiblement plusieurs allers-retours)
- [ ] **Export PDF réel validé par l'utilisateur** (glyphes de coverage, montants, sauts de page)

### Phase 3 — Refactor & finalisation
- [ ] Relecture de cohérence des 3 workstreams (nommage, structure, pas de duplication résiduelle)
- [ ] Décision utilisateur actée sur le sort de la route de preview (gardée gated ou retirée)
- [ ] `vendor/bin/pint --dirty --format agent`, `yarn lint:fix && yarn typecheck`, `php artisan test --compact` (suite complète) tous verts
- [ ] Résidus de la Phase 1 (formatage brut, styles minimaux) nettoyés si remplacés en Phase 2
- [ ] **Feu vert utilisateur obtenu explicitement avant tout commit**
- [ ] Commit effectué

---

## Contexte

La page Billing (`resources/js/pages/ProjectBillingPage.vue`, servie par `ClientController::showBilling` et, en mode partagé, par `ShowSharedHandler`) affiche pour un client la liste de ses projets avec, par mois, les jours travaillés, montants facturés et factures. Il n'existe aujourd'hui aucun moyen de télécharger ces données : le besoin est d'exporter un PDF (page complète, un projet, ou une plage de mois — via une modale de configuration unique) pour trois usages : pièce jointe à une facture, archive interne, et partage libre avec le client (y compris depuis le lien public `shares/{token}`).

Une première version de ce plan a été écrite par un agent sans jamais avoir été implémentée, puis passée au crible dans une session de grilling qui a vérifié chaque hypothèse contre le code réel (vendor Laravel, types Inertia installés, code compilé d'Inertia, doc + README GitHub du service externe) plutôt que de deviner. Plusieurs failles réelles ont été trouvées et corrigées — la liste des décisions verrouillées ci-dessous intègre directement ces corrections (le detail du "avant/après" n'a plus d'intérêt, seule la version finale compte).

## Rôle de ce document & mode de pilotage

- **Un agent orchestrateur pilote l'ensemble** : il lit ce plan, découpe et lance les sous-agents des workstreams (A/B/C ci-dessous), s'assure de la cohérence entre eux (contrat partagé respecté), et **interagit directement avec l'utilisateur** — il ne doit jamais deviner un point ambigu, mais poser la question. Il propose aussi des essais visuels à valider (voir Phase 2 ci-dessous).
- **Approche MVP → itération → refactor**, pas un développement linéaire complet d'un coup :
  1. **Phase 0** — contrat technique (routes, requête de validation, service PDF minimal).
  2. **Phase 1** — MVP bout en bout : ça fonctionne, même moche (template Blade minimal sans design poussé, modale fonctionnelle). Objectif : valider le flux complet (génération → téléchargement → erreur) avant d'investir dans le visuel.
  3. **Phase 2** — itération design du template PDF, en boucle avec l'utilisateur (voir plus bas — c'est le cœur de ce qu'il faut piloter en interaction).
  4. **Phase 3** — refactor & nettoyage une fois le design validé (retirer les échafaudages temporaires, vérifier les conventions, tests finaux).
- **Aucun commit sans confirmation explicite de l'utilisateur**, à n'importe quelle étape, même une fois tout testé et fonctionnel.
- **Politique de test des agents** : tests automatisés uniquement (Pest avec `Http::fake()` — jamais le vrai service ; `yarn typecheck`). **Aucun agent n'ouvre de navigateur, n'appelle le vrai service `pdf.mathieutu.dev`, ni la page web réelle.** Les tests manuels (navigateur, rendu visuel, vrai service) sont exécutés uniquement par l'utilisateur, guidé par les checklists de ce document.
- **Utiliser une todo-list de session** (TaskCreate/TaskUpdate) par workstream pour le suivi fin au jour le jour, **en complément** de la section "Suivi d'avancement" ci-dessus qui, elle, doit être tenue à jour dans ce fichier committé.
- **Modèle recommandé pour les agents d'implémentation** : Sonnet, effort medium — largement suffisant pour ces workstreams (le travail difficile de conception/vérification a déjà été fait dans ce plan). Réserver un effort plus élevé (ou l'orchestrateur lui-même) aux moments qui demandent du jugement : arbitrage d'un point ambigu avec l'utilisateur, itération de design en Phase 2.
- **Activer les skills du projet dès qu'ils sont pertinents** (déjà une règle générale du CLAUDE.md, mais à rappeler explicitement ici vu l'enjeu visuel) : `nuxt-ui` pour la modale, `laravel-best-practices` et `pest-testing` côté backend, `typescript-best-practices`/`vuejs` côté frontend, `wayfinder-development` pour les routes générées. **Pour la Phase 2 (design du template PDF)**, activer en plus `frontend-design` (éviter un rendu générique, viser quelque chose de soigné) et `dataviz` (le calendrier de coverage est une visualisation de type heatmap/légende — le skill donne un cadre pour ça) — le rendu doit rester cohérent avec le thème général de l'app (couleur primaire `pink`, cf. `vite.config.ts`, typographie et densité des autres pages Billing/Dashboard).

---

## Décisions verrouillées (ne pas rouvrir sans repasser par l'utilisateur)

1. **Génération PDF** : service externe `pdf.mathieutu.dev` (`POST /api/gen`), HTML brut envoyé (pas d'URL — évite d'exposer des données via une page authentifiée). Pas d'auth requise par le service. Succès = PDF binaire ; erreur = JSON `{ "error": string }` (400/413/500). Timeout ~60s. Prod utilisera une instance dédiée via `PDF_GEN_API_URL` (la démo publique est dev/test uniquement, non versionnée).
2. **`PdfGenerator`** : classe réduite au strict nécessaire (rendu vue Blade + appel HTTP + bytes), pas les méthodes inutiles de la classe complète de la doc officielle (`url()`, `urls()`, `save()`, `inlineResponse()`, `Responsable`). **Gestion d'erreur : `catch (\Throwable $e)`, pas `catch (\RuntimeException $e)`** — vérifié dans `vendor/laravel/framework` : `Illuminate\Http\Client\ConnectionException` (timeout réseau, DNS, connexion refusée) étend `HttpClientException extends Exception`, **pas** `RuntimeException`. Un simple `catch (\RuntimeException)` laisserait passer tout timeout réseau vers le service externe, un cas pourtant fréquent vu le TTL de 60s.
3. **Template** : vue Blade dédiée côté serveur (pas de Vue/Inertia) — le rendu print diffère du web (notes affichées en clair, pas en tooltip) et n'a jamais besoin d'interactivité.
4. **Contenu** : en-tête minimal (client, projet, période, nom/email prestataire — pas de SIRET/adresse/IBAN, non disponibles en base). Calendrier visuel jour par jour, notes (titre/description) en texte sous le calendrier. Saut de page par projet.
5. **Formatage — réutilisation du vrai JS du site plutôt qu'un port PHP.** Porter `formatCurrency`/`formatDays`/`coverageLabel` en PHP créerait un risque réel de divergence (glyphes Unicode fraction `½ ⅓ ⅔...`, absence de pluralisation, locale). Le service est *"Powered by Puppeteer and headless Chromium"* (README GitHub `mathieutu/pdf-gen`) et son propre exemple charge un `<script src="...">` externe — le JS s'exécute donc bien avant capture. Laravel a un helper natif `Vite::content($asset)` pour inliner un bundle Vite compilé dans une vue Blade. → un point d'entrée Vite dédié importe les vraies fonctions et les exécute côté "navigateur" du service de rendu (détails Workstream B).
6. **Locale de `formatCurrency`** : la fonction utilise aujourd'hui `navigator.languages` (locale du visiteur), qui n'existe pas en headless Chromium serveur. → fixer en dur `'fr-FR'` dans `resources/js/utils/number.ts`. Site 100% français, aucune régression visuelle attendue ; permet aussi au point d'entrée PDF de réutiliser la fonction telle quelle, sans wrapper.
7. **Portée** : un seul bouton "Exporter" ouvrant une modale (checkboxes projets + plage de mois), pas 3 boutons séparés. Défauts : projets avec un montant "à facturer" (`toInvoice = totalWorked - totalInvoiced`, cf. `ProjectBillingPage.vue::projectTotals()`) > 0 cochés (sinon tous cochés, fallback anti-modale-vide) ; plage de mois démarrant à la plus ancienne `lastInvoiceDate()` parmi les projets cochés par défaut (fallback : mois le plus ancien avec données), jusqu'au mois courant.
8. **Disponibilité** : bouton présent sur la vue connectée et sur la vue partagée publique (`shares/{token}`).
9. **Mécanisme de téléchargement — une seule requête, `fetch()` + `Blob`, pas `useHttp`.** Vérifié dans le code compilé d'Inertia (`node_modules/@inertiajs/core/dist/index.js`) : le client HTTP sous-jacent de `useHttp` utilise `XMLHttpRequest` avec `responseType: "text"` et lit `xhr.responseText` — il ne peut **physiquement pas** transporter un PDF binaire sans corruption (décodage texte). Donc pour cet appel précis : `fetch()` manuel, `response.blob()` pour lire le binaire correctement, spinner piloté par un `ref` manuel, téléchargement déclenché via `URL.createObjectURL(blob)` + clic synthétique sur un `<a download>`. Le backend renvoie directement soit le PDF (`200`, `Content-Type: application/pdf`, `Content-Disposition: attachment`), soit une erreur JSON (`502`, `{ message }`) — **dans la même requête, pas de fichier temporaire, pas d'URL signée, pas de route de téléchargement séparée, pas de tâche de nettoyage planifiée.**
10. **Nom du fichier** : `{Client}_{Projet(s)}_{from}_{to}.pdf` (partie projet omise si plusieurs projets sélectionnés).
11. **Rate limiting** : `shares/{token}/export` est accessible sans authentification (token de partage permanent, sans TTL) et appelle un service externe coûteux → `throttle:10,1` sur les deux routes d'export.
12. **Timeout de la requête synchrone** (génération jusqu'à 65s) : reste synchrone, pas de queue (aucun `Job` n'existe dans ce repo — introduire une brique async serait disproportionné pour une feature à faible trafic). **Rappel opérationnel avant mise en prod** : vérifier que le timeout du reverse proxy (nginx/Caddy) et `max_execution_time` PHP-FPM/Octane dépassent bien 65s + marge.
13. **Route de preview HTML (Phase 2 uniquement, voir plus bas)** : une route qui retourne la vue Blade directement en HTML (sans passer par le service PDF externe), gated `local`/`testing` uniquement, pour itérer vite sur le design sans round-trip vers le service externe à chaque changement.

---

## Contrat technique partagé

### Routes

| Contexte | Méthode/URL | Nom | Middleware |
|---|---|---|---|
| Connecté | `GET clients/{client}/billing/export` | `clients.billing.export` | `['auth', EnsureUserOwnsResource::class, 'throttle:10,1']`, même groupe que `clients.billing.show` (`withTrashed()`) |
| Partagé public | `GET shares/{token}/export` | `shares.billing.export` | `['throttle:10,1']` (le token est le gate, comme `shares.show`) |
| Connecté, dev only | `GET clients/{client}/billing/export/preview` | `clients.billing.export.preview` | même groupe que `clients.billing.export`, 404 hors `local`/`testing` |

Les 3 routes produisent un helper Wayfinder généré (`php artisan wayfinder:generate`) — le front n'écrit jamais l'URL à la main.

### Query params (identiques sur les 3 routes)

```
?project_ids[]=1&project_ids[]=2&from=2026-01&to=2026-06
```
Wayfinder sérialise nativement les tableaux en `key[]=v` (cf. `resources/js/wayfinder/index.ts::queryParams()`).

Validation (`ExportBillingRequest`) : `project_ids` requis, tableau non vide, chaque id doit appartenir au client résolu (sinon `404`) ; `from`/`to` au format `Y-m`, `to >= from`.

### Réponses

- **Export réel — succès** : `200`, corps = bytes du PDF, `Content-Type: application/pdf`, `Content-Disposition: attachment; filename="..."`.
- **Export réel — échec génération** : `200` de statut HTTP mais **`502`** applicatif (échec du service externe), JSON `{ "message": string }`. Géré côté front via un `try/catch` + vérification `response.ok` sur le `fetch()` → toast manuel (`useToast().add({ title: message, color: 'error' })`).
- **Preview (dev only)** : `200`, `Content-Type: text/html`, corps = la vue Blade rendue directement (pas d'appel au service externe). Hors `local`/`testing` : `404`.
- **Validation invalide** (`project_ids` manipulé à la main, format `from`/`to` invalide) : `422` standard Laravel — ne devrait pas se produire en usage normal (valeurs construites par la modale).

---

## Phase 0 — Contrat & scaffolding (Workstream A, à faire en premier)

Fichiers à créer/modifier :

1. **`config/services.php`** :
   ```php
   'pdf' => ['api_url' => env('PDF_GEN_API_URL', 'https://pdf.mathieutu.dev/api/gen')],
   ```
   et `.env.example` : `PDF_GEN_API_URL=`.

2. **`app/Services/PdfGenerator.php`** (nouveau) :
   ```php
   <?php

   declare(strict_types=1);

   namespace App\Services;

   use Illuminate\Support\Facades\Http;
   use RuntimeException;

   class PdfGenerator
   {
       private readonly string $apiUrl;

       public function __construct()
       {
           $this->apiUrl = config('services.pdf.api_url');
       }

       public function fromView(string $view, array $data = []): string
       {
           $response = Http::asJson()->timeout(65)->post($this->apiUrl, [
               'html' => view($view, $data)->render(),
           ]);

           if ($response->failed()) {
               throw new RuntimeException("Failed to generate PDF: {$response->body()}");
           }

           return $response->body();
       }
   }
   ```

3. **`app/Http/Requests/ExportBillingRequest.php`** (`php artisan make:request ExportBillingRequest --no-interaction`) :
   ```php
   public function authorize(): bool { return true; } // gate géré par le middleware de route / le token

   public function rules(): array
   {
       return [
           'project_ids' => ['required', 'array', 'min:1'],
           'project_ids.*' => ['string'],
           'from' => ['required', 'date_format:Y-m'],
           'to' => ['required', 'date_format:Y-m', 'after_or_equal:from'],
       ];
   }
   ```

4. **`app/Http/Concerns/BuildsProjectBillingEntry.php`** — ajouter deux méthodes partagées entre `ClientController` et le handler de partage. Séparer la construction des données de vue (réutilisée par l'export réel ET la preview) de l'appel au service PDF :

   ```php
   protected function buildBillingExportViewData(
       Client $client,
       Collection $projects, // déjà filtrée sur project_ids, avec timesheetEntries+invoices chargées
       ?string $providerName,
       ?string $providerEmail,
       string $from,
       string $to,
       HolidayService $holidays,
   ): array {
       $built = $projects->map(fn (Project $p) => $this->buildProjectBillingEntry($p, $client->name))
           ->map(fn (array $entry) => [
               ...$entry,
               'months' => collect($entry['months'])->filter(fn ($m) => $m['month'] >= $from && $m['month'] <= $to)->values(),
           ]);

       return [
           'projects' => $built,
           'clientName' => $client->name,
           'providerName' => $providerName,
           'providerEmail' => $providerEmail,
           'from' => $from,
           'to' => $to,
           'holidays' => $this->buildHolidaysForPeriod($holidays, $built),
       ];
   }

   protected function buildBillingExportResponse(
       Client $client,
       Collection $projects,
       ?string $providerName,
       ?string $providerEmail,
       string $from,
       string $to,
       HolidayService $holidays,
       PdfGenerator $pdf,
   ): Response // Symfony\Component\HttpFoundation\Response — englobe JsonResponse et la réponse binaire
   {
       $viewData = $this->buildBillingExportViewData($client, $projects, $providerName, $providerEmail, $from, $to, $holidays);
       $filename = $this->buildExportFilename($client->name, $viewData['projects'], $from, $to);

       try {
           $bytes = $pdf->fromView('exports.billing', $viewData);
       } catch (\Throwable $e) {
           report($e);

           return response()->json(['message' => 'La génération du PDF a échoué. Réessaie dans quelques instants.'], 502);
       }

       return response($bytes, 200, [
           'Content-Type' => 'application/pdf',
           'Content-Disposition' => 'attachment; filename="'.$filename.'"',
       ]);
   }
   ```
   Helper de nommage (méthode privée) `buildExportFilename(string $clientName, Collection $builtProjects, string $from, string $to): string` : slug du nom client (`Str::slug`), suffixe `_{slug du nom projet}` uniquement si `$builtProjects->count() === 1`, suffixe `_{from}_{to}.pdf`.

   Message d'erreur volontairement générique (pas `$e->getMessage()`) pour ne pas exposer le corps de réponse brut du service externe ; `report($e)` garde la trace technique côté serveur.

5. **`app/Http/Controllers/ClientController.php`** — deux nouvelles méthodes :
   ```php
   public function exportBilling(ExportBillingRequest $request, Client $client, HolidayService $holidays, PdfGenerator $pdf): Response
   {
       $projects = $this->resolveExportProjects($client, $request);

       return $this->buildBillingExportResponse($client, $projects, $request->user()->name, $request->user()->email, $request->validated('from'), $request->validated('to'), $holidays, $pdf);
   }

   public function previewBillingExport(ExportBillingRequest $request, Client $client, HolidayService $holidays): View
   {
       abort_unless(app()->environment(['local', 'testing']), 404);

       $projects = $this->resolveExportProjects($client, $request);
       $viewData = $this->buildBillingExportViewData($client, $projects, $request->user()->name, $request->user()->email, $request->validated('from'), $request->validated('to'), $holidays);

       return view('exports.billing', $viewData);
   }

   private function resolveExportProjects(Client $client, ExportBillingRequest $request): Collection
   {
       $projects = $client->projects()->whereIn('id', $request->validated('project_ids'))->with(['timesheetEntries', 'invoices'])->get();
       abort_if($projects->count() !== count($request->validated('project_ids')), 404);

       return $projects;
   }
   ```
   Le contrôleur doit `use App\Http\Concerns\BuildsProjectBillingEntry;` (déjà fait pour `showBilling`).

6. **`app/Http/Controllers/ExportSharedBillingHandler.php`** (nouveau, miroir de `ShowSharedHandler.php`) :
   ```php
   class ExportSharedBillingHandler
   {
       use BuildsProjectBillingEntry;

       public function __invoke(ExportBillingRequest $request, string $token, HolidayService $holidays, PdfGenerator $pdf): Response
       {
           $client = Client::with('user')->where('share_token', $token)->firstOrFail();
           $projects = $client->projects()->whereIn('id', $request->validated('project_ids'))->with(['timesheetEntries', 'invoices'])->get();
           abort_if($projects->isEmpty() || $projects->count() !== count($request->validated('project_ids')), 404);

           return $this->buildBillingExportResponse($client, $projects, $client->user->name, null, $request->validated('from'), $request->validated('to'), $holidays, $pdf);
       }
   }
   ```
   Pas d'email du prestataire côté partage public (ne pas exposer l'email dans un lien non authentifié) — `providerEmail = null`, le template Blade doit gérer ce cas (n'affiche que ce qui est disponible).

7. **`routes/web.php`** :
   - Dans le groupe `['auth', EnsureUserOwnsResource::class]`, juste après `clients/{client}/billing` :
     ```php
     Route::get('clients/{client}/billing/export', [ClientController::class, 'exportBilling'])->name('clients.billing.export')->withTrashed()->middleware('throttle:10,1');
     Route::get('clients/{client}/billing/export/preview', [ClientController::class, 'previewBillingExport'])->name('clients.billing.export.preview')->withTrashed();
     ```
   - À côté de `Route::get('shares/{token}', ShowSharedHandler::class)->name('shares.show');` :
     ```php
     Route::get('shares/{token}/export', ExportSharedBillingHandler::class)->name('shares.billing.export')->middleware('throttle:10,1');
     ```

8. Lancer `vendor/bin/pint --dirty --format agent` après ces modifications PHP, puis régénérer les routes Wayfinder (`php artisan wayfinder:generate --no-interaction` si le plugin Vite ne l'a pas déjà fait en dev) pour que le Workstream C dispose des helpers TypeScript `clients.billing.export` / `clients.billing.export.preview` / `shares.billing.export`.

**Tests (Pest, `php artisan make:test --pest ExportBillingTest`)** — `Http::fake()` pour simuler le service :
- Utilisateur propriétaire, `project_ids` valides → `200`, `Content-Type: application/pdf`, `Content-Disposition` avec le bon nom de fichier, corps = bytes renvoyés par `Http::fake(['*' => Http::response('%PDF-1.4...', 200)])`.
- Utilisateur non propriétaire → `403` (héritage `EnsureUserOwnsResource`, juste vérifier que la nouvelle route en hérite).
- `project_ids` contenant un id d'un autre client → `404`.
- Service PDF en erreur (`Http::fake(['*' => Http::response(['error' => 'boom'], 500)])`) → `502`, `{ "message": "..." }`.
- Service PDF injoignable (`Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout'))`) → `502` également (vérifie le `catch (\Throwable)`, pas seulement `RuntimeException`).
- Route partagée : token valide → `200` (PDF) ; token invalide → `404`.
- `clients.billing.export.preview` : `200` + `Content-Type: text/html` en environnement `testing` (c'est l'environnement des tests Pest, donc accessible) ; on peut vérifier via un test dédié que la route retourne bien du HTML et pas un appel au service PDF (`Http::fake()` sans aucune requête enregistrée, assert qu'aucune requête HTTP n'a été faite).
- Throttle : 11 requêtes rapides sur `shares.billing.export` → la 11e retourne `429`.

---

## Phase 1 — MVP bout en bout

Une fois la Phase 0 posée, construire en parallèle (ça peut être 2 agents distincts, ou le même agent séquentiellement) :

**Workstream B (minimal)** — `resources/views/exports/billing.blade.php` :
- Document HTML autonome (`<!DOCTYPE html>` complet, `<style>` inline minimal, aucun asset externe à ce stade).
- `@page { size: A4; margin: 15mm; }`, `page-break-before: always` sur chaque `.project:not(:first-of-type)`.
- Par projet : en-tête (nom projet, `{{ $clientName }}`, période, `{{ $providerName }}` + `{{ $providerEmail }}` si non null).
- Par mois (déjà filtré par le contrôleur) : titre du mois, calendrier basique (boucler sur `Carbon::parse($month.'-01')->daysInMonth`, une case par jour, `$holidays` et `$entries` déjà transmis — cf. `BuildsProjectBillingEntry::buildProjectBillingEntry`), notes en clair sous la grille (jours avec `title`/`description` non vides), liste des factures du mois avec statut payé/non payé.
- Pied de projet : totaux — mêmes formules que `ProjectBillingPage.vue::projectTotals()` : `totalDays = sum(days_worked)`, `totalWorked = sum(days_worked * daily_rate)`, `totalInvoiced = sum(invoices.amount)`, `toInvoice = totalWorked - totalInvoiced` — recalculés sur les mois déjà filtrés à la plage exportée (le filtrage est fait dans `buildBillingExportViewData`, les totaux Blade itèrent sur `$project['months']` après filtrage).
- **À ce stade, formatage brut** (montants en euros divisés par 100 avec un simple `number_format`, jours en float tel quel) — pas encore le vrai JS injecté ni le design soigné, l'objectif de cette phase est juste "ça marche bout en bout". Le raffinement du formatage et du visuel arrive en Phase 2.

**Workstream C (minimal)** :
- `resources/js/types/billing.ts` (nouveau) : extraire les types `ProjectWithBilling`, `MonthRow`, `MonthInvoice`, `EntryData` aujourd'hui inline dans `ProjectBillingPage.vue` (lignes ~50-78), les importer dans `ProjectBillingPage.vue` et le nouveau composant.
- `resources/js/components/ExportBillingModal.vue` (nouveau) :
  ```ts
  defineProps<{
    open: boolean, // v-model:open
    projects: ProjectWithBilling[],
    clientId?: string, // requis si !isShared
    shareToken?: string, // requis si isShared
    isShared: boolean,
  }>()
  ```
  Pattern de modale à répliquer : `resources/js/pages/ProjectBillingPage.vue:756-797` (`UModal` + `#body`/`#footer`).

  **Sélection des projets** — `UCheckboxGroup`/`UCheckbox` (Nuxt UI — activer le skill `nuxt-ui`, aucun exemple existant dans le repo pour ce composant précis). Défaut de `selectedProjectIds` : projets où `toInvoice > 0` (cf. décision 7 ; fallback tous cochés si aucun).

  **Plage de mois** — réutiliser le pattern de `resources/js/pages/DashboardPage.vue:461-495` (`UPopover` + `UCalendar type="month" range locale="fr-FR"`) et les helpers `CalendarDate`/`toYearMonth` (~lignes 97-140 du même fichier) ; les extraire dans un utilitaire partagé (`resources/js/utils/date.ts`) plutôt que les dupliquer si pas déjà exportés proprement.

  **URL d'export** — `computed`, exclusivement via les helpers Wayfinder générés en Phase 0 :
  ```ts
  import { exportBilling as exportClientBilling } from '@/wayfinder/routes/clients' // nom exact à confirmer après génération
  import { exportBilling as exportSharedBilling } from '@/wayfinder/routes/shares' // idem

  const exportUrl = computed(() => {
    const query = { project_ids: selectedProjectIds.value, from: rangeFrom.value, to: rangeTo.value }
    return props.isShared
      ? exportSharedBilling(props.shareToken!, { query })
      : exportClientBilling(props.clientId!, { query })
  })
  ```

  **Téléchargement** (cf. décision 9 — `fetch()` + `Blob`, pas `useHttp`) :
  ```ts
  const isExporting = ref(false)

  const extractFilename = (contentDisposition: string | null): string | null =>
    contentDisposition?.match(/filename="(.+)"/)?.[1] ?? null

  const exportPdf = async () => {
    isExporting.value = true

    try {
      const response = await fetch(exportUrl.value)

      if (!response.ok) {
        const body = await response.json().catch(() => null)
        toast.add({ title: body?.message ?? 'Échec de la génération du PDF', color: 'error' })
        return
      }

      const blob = await response.blob()
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = extractFilename(response.headers.get('content-disposition')) ?? 'export.pdf'
      link.click()
      URL.revokeObjectURL(url)
      open.value = false
    } finally {
      isExporting.value = false
    }
  }
  ```
  Bouton d'export (footer) :
  ```vue
  <UButton label="Exporter" icon="i-lucide-download" :loading="isExporting" :disabled="selectedProjectIds.length === 0" @click="exportPdf" />
  ```
  `useToast()` importé depuis `@nuxt/ui/composables` (cf. `resources/js/pages/ProfilePage.vue:3,21,26`).

- **`resources/js/pages/ProjectBillingPage.vue`** :
  - Extraire les types vers `@/types/billing`.
  - Ajouter un bouton "Exporter" (`UButton icon="i-lucide-download"`) dans la barre d'actions (à côté de "Afficher les projets inactifs", lignes ~452-471), visible dans les deux modes — ouvre la modale (`exportModalOpen = ref(false)`).
  - **Gap à corriger** : `ShowSharedHandler.php` ne transmet aujourd'hui pas le `token` à la page Inertia (seulement `shared_by`/`is_shared`/`projects`/`holidays`), pourtant nécessaire pour l'URL d'export en mode partagé. Ajouter `'token' => $token` aux données passées, et un prop `token?: string` sur `ProjectBillingPage.vue`.
  - Rendre `<ExportBillingModal v-model:open="exportModalOpen" :projects="visibleProjects" :client-id="visibleProjects[0]?.client.id" :share-token="token" :is-shared="is_shared" />`.

**Point de contrôle utilisateur (fin de Phase 1)** : l'orchestrateur présente à l'utilisateur ce qui a été fait, lui indique clairement que les tests automatisés sont verts (Pest + typecheck), et lui demande de tester manuellement le flux complet en suivant la checklist "MVP" ci-dessous, **avant** de passer à la Phase 2. Ne pas enchaîner automatiquement sur le design sans ce feu vert.

### Checklist manuelle — validation du MVP (utilisateur)
- [ ] Le bouton "Exporter" ouvre la modale, avec les bons projets/mois cochés par défaut
- [ ] L'export connecté télécharge un PDF, non corrompu, qui s'ouvre dans un lecteur PDF
- [ ] Le contenu est présent (même moche) : projets, mois, calendrier, notes, factures, totaux
- [ ] L'export depuis un lien de partage public fonctionne (et n'affiche pas l'email prestataire)
- [ ] Simuler une panne du service externe (`PDF_GEN_API_URL` invalide) → toast d'erreur, pas de plantage
- [ ] Le nom du fichier téléchargé suit le format `{Client}_{Projet(s)}_{from}_{to}.pdf`

---

## Phase 2 — Itération design du template PDF

**Objectif** : transformer le rendu "fonctionnel mais moche" de la Phase 1 en quelque chose de soigné et cohérent avec le thème général de l'app, en boucle rapide avec l'utilisateur (qui est seul capable de juger visuellement — l'agent n'a pas accès à un navigateur).

**Outil clé — la route de preview** (`clients.billing.export.preview`, posée en Phase 0) : elle retourne la vue Blade directement en HTML, sans passer par le service externe. Ouvrir cette URL dans un navigateur normal permet d'itérer en quelques secondes (rechargement de page, devtools, inspection CSS) au lieu d'attendre un aller-retour vers `pdf.mathieutu.dev` à chaque changement. **Le rendu HTML seul ne garantit pas un rendu PDF identique** (polices disponibles côté Puppeteer, `@page`/`page-break` ne s'observent pas pareil dans un onglet navigateur qu'à l'impression) — une fois le design validé en HTML, il faut *quand même* valider avec un vrai export PDF avant de considérer la Phase 2 terminée.

**Boucle de travail attendue** (l'agent orchestrateur pilote ça, pas juste "implémente et espère") :
1. L'agent propose une itération de design (structure visuelle du calendrier, hiérarchie typographique, couleurs, densité — cohérent avec `resources/js/components/BillingMonthCalendar.vue` et le thème général de l'app plutôt qu'une réinvention).
2. Il l'implémente dans `resources/views/exports/billing.blade.php` (+ assets JS/CSS liés).
3. Il **demande explicitement à l'utilisateur** d'ouvrir l'URL de preview (donner l'URL exacte avec des query params d'exemple) et de donner son retour — ne pas supposer que c'est bon.
4. Ajuste selon le retour, répète jusqu'à validation.
5. Une fois le HTML validé, demande à l'utilisateur de tester un **vrai export PDF** (checklist dédiée ci-dessous) pour vérifier que le rendu Puppeteer correspond.

**Contenu du design à finaliser dans cette phase** (cf. décisions 5 et 6) :
1. **`resources/js/utils/number.ts`** — fixer la locale :
   ```ts
   export const formatCurrency = (amount: number): string => new Intl.NumberFormat('fr-FR', {
     style: 'currency',
     currency: 'EUR',
     maximumFractionDigits: 0,
   }).format(amount / 100).replaceAll(' ', ' ')
   ```
2. **Nouveau point d'entrée Vite** `resources/js/exports/billing-pdf.ts` : importe les vraies `formatCurrency`, `formatDays`, `coverageLabel` (`utils/number.ts`/`utils/date.ts`). Au chargement (script inliné en fin de `<body>`, donc déjà après le DOM), parcourt les éléments marqués par des attributs `data-*` posés par le Blade (`data-currency-cents`, `data-days`, `data-coverage`) et remplace leur `textContent` par la sortie des vraies fonctions JS.
3. **`vite.config.ts`** : ajouter `'resources/js/exports/billing-pdf.ts'` au tableau `input` de `laravel-vite-plugin` (multi-entrées déjà supporté nativement).
4. **`resources/views/exports/billing.blade.php`** : inliner le bundle en fin de `<body>` :
   ```blade
   <script>{!! \Illuminate\Support\Facades\Vite::content('resources/js/exports/billing-pdf.ts') !!}</script>
   ```
5. **Design visuel du calendrier** — porter l'esprit de `resources/js/components/BillingMonthCalendar.vue` (grille de cases, teinte selon coverage, jours fériés/weekends grisés) en CSS print. Activer le skill `dataviz` pour la légende/l'encodage couleur de la heatmap, et `frontend-design` pour éviter un résultat générique — cohérent avec la couleur primaire `pink` de l'app (`vite.config.ts`) sans forcément copier l'UI web à l'identique (le PDF est un document imprimé statique, pas une réplique pixel-perfect).
6. **Réutilisation du vrai CSS compilé de l'app plutôt qu'un CDN ou du CSS réécrit à la main** — décidé après grilling : faire tourner Vue/Nuxt UI pour de vrai dans ce template (SSR invoqué depuis PHP, ou montage côté client dans la page Puppeteer) a été écarté — aucun process Node SSR n'est déployé en prod aujourd'hui (seul le SSG au build de la landing existe), et les composants Nuxt UI embarquent du comportement JS interactif (focus-trap, positionnement floating-ui) inutile pour un document statique. Le compromis retenu : inliner le **CSS compilé réel** de l'app (Tailwind + tokens de thème Nuxt UI, depuis `resources/css/app.css`) via le même helper que pour le JS :
   ```blade
   <style>{!! \Illuminate\Support\Facades\Vite::content('resources/css/app.css') !!}</style>
   ```
   Le Blade utilise alors les mêmes classes utilitaires/tokens de couleur que le reste du site (cohérence visuelle réelle, pas approximée), sans dépendance réseau externe (pas de CDN) et sans pipeline Vue à faire tourner côté serveur. `BillingMonthCalendar.vue` reste une référence visuelle à porter en markup Blade (classes/structure), pas un composant à exécuter tel quel — de toute façon son affichage des notes en tooltip est inadapté au print (cf. décision 4) et demanderait une adaptation même en réutilisant le composant directement.

**Risque connu à surveiller en priorité** : le rendu des glyphes Unicode fraction (`½`, `⅓`, `⅔`...) de `coverageLabel()` dépend des polices installées dans l'environnement Puppeteer du service — non documenté. Premier test PDF réel à regarder en priorité pour ce point (carrés vides = "tofu" = police manquante). Second point à vérifier : le CSS inliné (Tailwind v4 + build Nuxt UI) peut contenir des règles non pertinentes hors du DOM réel de l'app (ex. sélecteurs ciblant des composants absents du template) — sans impact fonctionnel, mais à garder en tête si la taille du HTML envoyé au service devient un problème.

### Checklist manuelle — validation du design (utilisateur, en boucle)
- [ ] Preview HTML : structure, hiérarchie visuelle, cohérence avec le thème de l'app (via le CSS compilé inliné)
- [ ] Export PDF réel : rendu des glyphes de coverage (pas de tofu), montants au format `1 234 €` (pas `€1,234`), calendrier lisible à l'impression, sauts de page corrects par projet

---

## Phase 3 — Refactor & finalisation

Une fois le flux ET le design validés par l'utilisateur :
- Relire l'ensemble du code produit (les 3 workstreams) pour cohérence de style avec le reste du repo (nommage, structure des fichiers, pas de duplication introduite pendant l'itération rapide des phases précédentes).
- **Décider avec l'utilisateur du sort de la route de preview** (`clients.billing.export.preview`) : la garder gated `local`/`testing` (utile pour de futurs ajustements du template) ou la retirer entièrement. Ne pas décider seul.
- `vendor/bin/pint --dirty --format agent`, `yarn lint:fix && yarn typecheck`, `php artisan test --compact` (suite complète, pas juste les nouveaux tests) — tout doit être vert.
- Vérifier qu'aucun résidu de la Phase 1 (formatage brut `number_format`, styles minimaux) ne subsiste si la Phase 2 les a remplacés.
- Présenter le travail terminé à l'utilisateur et **attendre le feu vert explicite avant tout `git commit`**.

---

## Fichiers à créer/modifier (vue d'ensemble)

**Backend**
- `config/services.php`, `.env.example` — config service PDF
- `app/Services/PdfGenerator.php` (nouveau)
- `app/Http/Requests/ExportBillingRequest.php` (nouveau)
- `app/Http/Concerns/BuildsProjectBillingEntry.php` — `buildBillingExportViewData()`, `buildBillingExportResponse()`, `buildExportFilename()`
- `app/Http/Controllers/ClientController.php` — `exportBilling()`, `previewBillingExport()`, `resolveExportProjects()`
- `app/Http/Controllers/ExportSharedBillingHandler.php` (nouveau)
- `routes/web.php` — 3 routes (export connecté, preview, export partagé)
- `resources/views/exports/billing.blade.php` (nouveau)
- `tests/Feature/ExportBillingTest.php` (nouveau)

**Frontend**
- `resources/js/types/billing.ts` (nouveau, types extraits)
- `resources/js/components/ExportBillingModal.vue` (nouveau)
- `resources/js/pages/ProjectBillingPage.vue` — bouton export, prop `token`, types extraits
- `resources/js/utils/number.ts` — fix locale `fr-FR`
- `resources/js/exports/billing-pdf.ts` (nouveau, point d'entrée Vite Phase 2)
- `vite.config.ts` — nouvelle entrée `input`
- `app/Http/Controllers/ShowSharedHandler.php` — ajout `'token' => $token` aux props Inertia

---

## Ordre d'exécution pour l'orchestrateur

1. **Phase 0 seule d'abord** (Workstream A) — fixe le contrat, génère les helpers Wayfinder, rien d'autre ne peut avancer sérieusement sans ça côté frontend (les URLs), même si B peut commencer sa structure Blade en parallèle sur la base du contrat déjà figé ci-dessus.
2. **Phase 1** : B (template minimal) et C (modale + intégration) peuvent avancer en parallèle une fois A posé — B ne dépend pas de C, C dépend des helpers Wayfinder générés par A.
3. **Point de contrôle utilisateur obligatoire** avant Phase 2 (voir checklist MVP).
4. **Phase 2** : itération design, boucle serrée avec l'utilisateur via la route de preview — c'est la phase la plus interactive, ne pas la traiter comme un développement solo.
5. **Point de contrôle utilisateur obligatoire** avant Phase 3 (design validé sur preview HTML **et** sur un vrai export PDF).
6. **Phase 3** : refactor, tests complets, puis attente du feu vert utilisateur pour committer.

Quel que soit l'avancement, si un point du contrat s'avère infaisable ou ambigu une fois dans le code réel : **poser la question à l'utilisateur plutôt que de deviner** — ce plan documente une intention vérifiée à un instant T, pas une vérité absolue si la réalité du code s'y oppose.
