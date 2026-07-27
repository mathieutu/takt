# Plan — Sous-traitance inter-comptes (version allégée)

> **Destination finale du fichier** : ce document vit sur la branche `subcontracting-light` (créée depuis
> `main`) le temps de l'implémentation, puis est supprimé au profit de `docs/features.md`, `docs/models.md`
> et `docs/architecture.md`, à l'image de ce qui avait été fait pour `feat/subcontracting`.

---

## 0. Contexte

`feat/subcontracting` répond à un vrai besoin (agir comme intermédiaire, faire travailler un prestataire,
refacturer son temps à son propre tarif, suivre sa marge) mais l'a implémenté comme une mini-plateforme de
collaboration inter-comptes : un modèle `SubcontractingLink` dédié, une state machine à 4 timestamps
(`claimed_at`/`accepted_at`/`revoked_at` + `invited_user_id`), un contrat d'autorisation dédié
(`AccessibleByUser`), un contrôleur d'invitation à 6 actions, et 3 écrans neufs.

Une revue de cette branche (analyse du diff, des tests, de la complexité introduite) a montré que
l'implémentation répond à un besoin plus ambitieux (comptes indépendants, réconciliation bidirectionnelle
continue via une state machine de consentement) que ce qui avait été demandé initialement, avec une charge de
maintenance disproportionnée : nouveau contrat d'autorisation greffé sur un modèle de sécurité jusque-là
uniforme, état dérivé de 4 timestamps plutôt qu'un statut explicite, moteur de diff/merge avec suivi de
baseline, 3 pages neuves, ~1000 lignes de code + 46 tests pour une seule feature. Trois commits de correctifs
sont déjà venus après coup sur la seule logique d'import (dates, baseline, chargement de la modale), signe que
cette zone reste fragile.

Cette branche (`subcontracting-light`) reprend le même besoin en réutilisant au maximum ce qui existe déjà
dans l'application, plutôt que d'ajouter un nouveau sous-système.

### Décisions actées (dans l'ordre où elles ont été prises)

| Sujet | Décision |
|---|---|
| Lien de délégation | Réutiliser le mécanisme de partage en lecture déjà en place sur `Client` (`share_token` + `findByShareTokenOrFail`), plutôt qu'un nouveau modèle de lien avec state machine |
| Import | Garder `BuildSubcontractingImportDiff` (protection contre l'écrasement d'éditions locales — la vraie valeur), mais simplifier l'UI : import global en un clic plutôt qu'une sélection jour par jour |
| Généralisation | Les tokens de partage reçus d'autres comptes sont enregistrés comme des **favoris** personnels (nouvelle entrée de menu) — un carnet d'adresses générique, pas spécifique à la sous-traitance |
| Mémorisation de la source | Un projet délégué garde `source_project_id` en base après le premier rattachement, pour dériver le TJM de coût et exclure ce temps de l'activité personnelle sans avoir à le reconfirmer à chaque import |
| Création | Un projet délégué se crée **directement depuis un favori** (source posée à la création) — pas de flow séparé « créer puis importer » |
| Branche de départ | Repartir de `main`, pas de `feat/subcontracting` : le diff est devenu trop différent pour que partir de la feature branche apporte quoi que ce soit |
| Réutilisation UI | En cas de divergence de choix d'implémentation, réutiliser l'UI déjà écrite sur `feat/subcontracting` (section « Sous-traitance » de `ProjectForm.vue`, `ImportEntriesModal.vue`, modale de partage de `ProjectPage.vue`) plutôt que la réinventer, tant que ça ne complexifie pas la version allégée à terme |
| Sécurité de l'import | `isImportable()` doit rester distinct de `isDelegated()` et revérifier en direct qu'un favori valide (même token que le `share_token` courant du client source) existe encore — un simple `share_token !== null` ne suffit pas : après révocation **puis régénération** d'un nouveau token, l'ancien favori ne doit pas continuer à autoriser l'import silencieusement |

Résultat : plus de modèle `SubcontractingLink`, plus de state machine, plus de contrat d'autorisation dédié,
plus de contrôleur d'invitation à 6 actions, plus d'écrans dédiés à l'invitation/l'acceptation. Ne reste comme
brique « métier » que : les favoris (générique, réutilisable), et le diff d'import (seule complexité
volontairement gardée, avec une UI simplifiée).

---

## 1. Base de travail

`subcontracting-light` est créée depuis `main` (`git branch -f subcontracting-light main`). Toute
réutilisation de code passe par une consultation ponctuelle de `feat/subcontracting`
(`git show feat/subcontracting:chemin/du/fichier`) pour copier/adapter l'UI existante, jamais par un
merge/cherry-pick de la branche entière — le modèle de données change trop.

---

## 2. Favoris — nouvelle brique générique

**Migration** `..._create_favorites_table.php` :

```php
Schema::create('favorites', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
    $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
    $table->uuid('token'); // le share_token du client au moment où le favori a été ajouté
    $table->unique(['user_id', 'client_id']);
    $table->timestamps();
});
```

Un vrai FK sur `client_id` (intégrité référentielle, cascade au moment où le client est supprimé) plutôt
qu'un simple `token` en l'air : le `token` capturé sert uniquement à détecter une révocation/régénération.

**`app/Models/Favorite.php`** : `belongsTo(User)`, `belongsTo(Client)`, implémente `HasUser` (propriétaire =
`user_id` direct, pas besoin d'`AccessibleByUser`).

```php
public function isValid(): bool
{
    return $this->client->share_token === $this->token;
}
```

Un favori devient invalide si le propriétaire du client a révoqué (`share_token` à `null`) ou régénéré
(nouveau token) son partage depuis — affiché comme tel dans `FavoritesPage.vue`, avec juste un bouton
supprimer (pas de tentative de « réparation » automatique : il faut un nouveau lien explicite du
propriétaire).

**`app/Http/Controllers/FavoriteController.php`** (3 actions, ressource) :

```php
public function index(Request $request): Response // liste des favoris + leurs projets (pour les pickers)
public function store(Request $request): RedirectResponse // valide `token`, résout le Client, firstOrCreate
public function destroy(Favorite $favorite): RedirectResponse
```

`store` : `$client = Client::findByShareTokenOrFail($data['token'])`, puis
`$request->user()->favorites()->firstOrCreate(['client_id' => $client->id], ['token' => $data['token']])`.

`index` : pour chaque favori valide, charge `client.projects` (id/name/daily_rate) — c'est cette même
réponse qui alimente le picker « projet source » au moment de créer un projet délégué (§4), pas besoin d'un
endpoint séparé.

**Route** : `Route::resource('favorites', FavoriteController::class)->only(['index', 'store', 'destroy'])`,
dans le groupe `auth + EnsureUserOwnsResource` habituel (aucun cas particulier : propriétaire direct).

**Nouvelle page** `resources/js/pages/FavoritesPage.vue` + entrée de menu dans `AppHeader.vue` (« Favoris ») :
liste des favoris (nom du client, nom du propriétaire, ou « lien invalide » si `!is_valid`), formulaire
« Ajouter un favori » (coller un token/URL reçu), bouton supprimer par ligne.

---

## 3. Projet : source et coût

**Migration** `..._add_source_project_id_to_projects_table.php` :

```php
Schema::table('projects', function (Blueprint $table) {
    $table->foreignUuid('source_project_id')->nullable()->unique()
        ->constrained('projects')->nullOnDelete();
});
```

`unique()` empêche qu'un même projet source alimente deux projets délégués différents.

**`app/Models/Project.php`** :

```php
public function sourceProject(): BelongsTo
{
    return $this->belongsTo(Project::class, 'source_project_id');
}

public function isDelegated(): bool
{
    return $this->source_project_id !== null;
}

/**
 * Distinct de `isDelegated()` : reste vrai en continu une fois la source posée (le TJM de coût reste
 * affiché, ce temps reste exclu de l'activité personnelle, même après révocation — l'historique importé
 * ne doit pas changer de sens rétroactivement). `isImportable()` en revanche revérifie en direct
 * l'autorisation qui a servi à poser cette source : un simple `share_token !== null` ne suffit pas — si le
 * prestataire révoque puis régénère un nouveau token, l'ancien favori (capturé avec l'ancien token) ne doit
 * pas continuer à autoriser l'import silencieusement avec le nouveau. Il faut donc qu'un favori du
 * propriétaire de ce projet, sur le client du projet source, soit toujours valide (même token que
 * `client.share_token` aujourd'hui) — exactement `Favorite::isValid()`.
 */
public function isImportable(): bool
{
    if (! $this->isDelegated()) {
        return false;
    }

    $sourceClient = $this->sourceProject->client;

    return $this->user->favorites()
        ->where('client_id', $sourceClient->id)
        ->get()
        ->contains(fn (Favorite $favorite) => $favorite->isValid());
}

/** The only real cost of a delegated project: what the subcontractor charges on their own source project. */
public function costDailyRate(): ?int
{
    return $this->sourceProject?->daily_rate;
}
```

(remplace `subcontracting()`/`subcontractedFor()`/`isDelegated()`/`isImportable()` d'origine — la logique est
plus simple, mais **`isImportable()` doit rester distinct de `isDelegated()`** : c'est le garde-fou contre un
accès qui continuerait indéfiniment après révocation d'un partage.)

---

## 4. Créer un projet délégué directement depuis un favori

Pas de flow séparé « créer puis attacher/importer » : `source_project_id` est posé **à la création**.

**`app/Http/Controllers/ProjectController::store`** (et `update`, pour pouvoir changer/retirer la source
après coup) — ajouter un champ optionnel validé :

```php
'source_project_id' => [
    'nullable', 'uuid',
    Rule::exists('projects', 'id')->where(fn ($q) => $q
        ->whereIn('client_id', $user->favorites()->pluck('client_id'))
        ->whereNull('source_project_id')), // pas de chaîne : le projet source ne doit pas être lui-même délégué
],
```

Le champ est passé tel quel à `CreateProjectForUser`/`$project->update()`. Aucune logique métier
supplémentaire : la contrainte d'unicité en base + cette règle de validation suffisent (pas besoin
d'`AcceptSubcontractingLink` ni d'action dédiée).

**`resources/js/pages/ProjectForm.vue`** — section « Sous-traitance », réutilisée/adaptée depuis
`feat/subcontracting` :
- **Mode création** : une bascule « Ce projet est délégué » → affiche un picker favori (liste de
  `page.favorites`, déjà chargée) puis un picker projet (projets du favori choisi, déjà présents dans la
  liste des favoris) → pose `source_project_id` dans le payload de `projects.store`.
- **Mode édition, pas de source** : même picker pour lier une source a posteriori (reste possible même après
  création, via `projects.update`).
- **Mode édition, source liée** : nom du projet source + TJM de coût dérivé (affichage repris tel quel de
  l'actuel `ProjectForm.vue`), bouton « Changer »/« Détacher » qui repasse par le même picker ou vide le
  champ.

Le bouton « Copier un lien d'invitation » (pour que le prestataire crée son propre projet, avec `name`/
`daily_rate` suggérés en query string sur `projects.create`) reste indépendant de tout ça — c'est l'autre
sens de la relation (le prestataire crée chez lui, pas de `source_project_id` impliqué pour lui).
`ProjectForm.vue` en mode création lit ces query params au montage pour préremplir le formulaire (aucun
changement backend requis, `projects.create` ignore déjà les query params superflus).

---

## 5. Import — juste diff + application, sans logique d'attachement

Puisque `source_project_id` est toujours posé à la création (§4), l'import redevient une action simple, sans
branchement « première fois vs. suivante ».

**`app/Actions/BuildSubcontractingImportDiff.php`** :

```php
public function __invoke(Project $delegatedProject): Collection
{
    $sourceProject = $delegatedProject->sourceProject;
    // algorithme new/modified/amended/removed inchangé, basé sur imported_source
}
```

**`app/Http/Controllers/ImportSubcontractedEntriesController.php`** — repris de `feat/subcontracting`,
simplifié :

```php
public function show(Project $project, BuildSubcontractingImportDiff $buildDiff): JsonResponse
{
    // isImportable(), pas isDelegated() : un partage révoqué entre-temps doit bloquer l'import, même si
    // le projet reste marqué comme délégué (voir Project::isImportable()).
    abort_if(! $project->isImportable(), 403);

    return response()->json(['diff' => $buildDiff($project)]);
}

public function store(Project $project, BuildSubcontractingImportDiff $buildDiff): RedirectResponse
{
    abort_if(! $project->isImportable(), 403);

    $rows = $buildDiff($project)->reject(fn ($row) => $row['status'] === 'amended');

    DB::transaction(function () use ($rows, $project) {
        $project->update([
            'start_date' => $project->sourceProject->start_date,
            'end_date' => $project->sourceProject->end_date,
        ]);
        // foreach ($rows as $row) : identique à aujourd'hui (delete si 'removed', sinon updateOrCreate + imported_source)
    });

    return back()->with('success', /* même message pluralisé qu'aujourd'hui */);
}
```

**`resources/js/components/ImportEntriesModal.vue`** — repris de `feat/subcontracting`, en gardant la
structure/le style, mais en remplaçant le regroupement par mois + checkboxes par ligne (lignes ~53-187
actuelles) par un résumé global par statut (`new`/`modified`/`removed` comptés, `amended` affiché à part avec
l'avertissement « ne sera pas importé »), et un unique bouton « Importer les jours » qui poste sans body.
Distinguer aussi l'état `is_importable` : si faux (partage révoqué), afficher « Le prestataire a révoqué son
partage — import indisponible » plutôt que le bouton d'import, sans masquer le TJM de coût ni réintégrer le
temps dans l'activité personnelle (ça reste `is_delegated`).

---

## 6. Billing / dashboard / timesheet

Partout où le code lit `$project->subcontracting?->costDailyRate()` ou
`$project->isDelegated()`/`$project->isImportable()` (`BuildsProjectBillingEntry.php`, `ShowHomeHandler.php`,
`TimesheetGrid.vue`) : remplacer par `$project->costDailyRate()` et `$project->isDelegated()`. Ces fichiers
peuvent être largement recopiés depuis `feat/subcontracting` (la logique billing/dashboard elle-même ne
change pas, seule la source de la donnée change).

---

## 7. Données de démo

`CreateDemoData::seedSubcontracting()` : créer les deux comptes prestataires et leurs projets sources comme
aujourd'hui, un `Favorite` pour le compte démo par prestataire (`client_id` + `token` = leur `share_token`),
et poser directement `source_project_id` sur les projets délégués à la création (plus de lien intermédiaire
avec timestamps à seeder).

---

## 8. Tests

- Ne pas reprendre `SubcontractingLinkControllerTest`/`SubcontractingLinkTest`/les cas `AccessibleByUser` de
  `EnsureUserOwnsResourceTest` (le modèle qu'ils testent n'existe plus).
- Nouveau `FavoriteControllerTest` : ajout (token valide/invalide), idempotence (`firstOrCreate`),
  suppression, isolation par utilisateur, `isValid()` après révocation/régénération du partage.
- Nouveau/adapté `ProjectControllerTest` : création avec `source_project_id` valide (favori), rejet si le
  projet source n'appartient pas à un favori, rejet si le projet source est lui-même délégué (anti-chaîne),
  rejet si le projet source est déjà utilisé ailleurs (contrainte unique).
- `ImportSubcontractedEntriesControllerTest` : reprendre les cas new/modified/amended/removed et
  d'idempotence de `feat/subcontracting`, adaptés à la signature simplifiée (plus de sélection de `dates`),
  plus les cas de régression relevés pendant la conception :
  - après `destroyShare()` sur le client du projet source, `show`/`store` répondent 403
    (`isImportable()` devient faux) alors que `isDelegated()`, le TJM de coût, et l'exclusion d'activité
    personnelle restent inchangés ;
  - après révocation **puis régénération** d'un nouveau `share_token` sur ce client, `isImportable()` reste
    faux tant que le donneur d'ordre n'a pas explicitement ré-ajouté le favori avec le nouveau token
    (`Favorite::isValid()` doit rester déterminant, pas juste « un token existe »).

---

## 9. Documentation

Réécrire la section « Subcontracting » de `docs/features.md` (favoris + création liée directement, plus
d'invitation/claim/accept/decline), ajouter `Favorite` à `docs/models.md`, remplacer la section
`SubcontractingLink` par `Project.source_project_id`. `docs/architecture.md` n'a pas besoin de mention
`AccessibleByUser` puisqu'il n'est jamais introduit dans cette version. Ce document
(`docs/plan/subcontracting-light.md`) est supprimé une fois la feature documentée, comme pour
`docs/plan/subcontracting.md`.

---

## 10. Vérification

- `php artisan test --compact --filter=Favorite` et `--filter=ImportSubcontractedEntries` : tous verts.
- `php artisan test --compact` complet (régressions billing/dashboard/timesheet).
- `vendor/bin/pint --dirty --format agent` après les modifs PHP.
- `yarn lint:fix && yarn typecheck` après les modifs `.vue`/`.ts`.
- Test manuel (`composer run dev`) : depuis un projet, copier le lien d'invitation vers
  `projects/create?name=...&daily_rate=...`, l'ouvrir avec un autre compte demo, créer le projet + son
  client, partager ce client, ajouter le token en favori côté donneur d'ordre, créer un nouveau projet
  délégué en choisissant ce favori/projet source, importer les jours, vérifier qu'une ligne `amended` n'est
  jamais écrasée, et que la marge s'affiche sur la page billing.
