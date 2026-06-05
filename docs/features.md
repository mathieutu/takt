# Features & Routes

**AssoFlow** est une application de gestion d'activité et de facturation pour indépendants et associations. Elle permet de suivre le temps de travail par projet/client, gérer la facturation, partager des rapports et exporter les données.

---

## Authentification

Gestion des comptes : inscription (utilisateur individuel ou organisation), connexion, déconnexion et suppression de compte.

| Méthode | URI | Controller | Action |
|---------|-----|------------|--------|
| GET | `/login` | `AuthController` | `showLogin` |
| POST | `/login` | `AuthController` | `login` |
| GET | `/register` | `AuthController` | `showRegister` |
| POST | `/register` | `AuthController` | `register` |
| POST | `/me/logout` | `AuthController` | `logout` |
| DELETE | `/me` | `AccountController` | `delete` |

**Page Vue :** `LoginPage.vue`, `RegisterPage.vue`

L'inscription crée un `Account` (email/password) puis, selon le type choisi, un `User` (prénom/nom) ou une `Organization` (nom), partageant le même UUID.

---

## Dashboard

Vue d'ensemble de l'activité : KPIs du mois/année, graphique des 12 derniers mois, dernières saisies, clients et projets actifs.

| Méthode | URI | Controller | Action |
|---------|-----|------------|--------|
| GET | `/` | — | Redirige vers `/dashboard` |
| GET | `/dashboard` | `DashboardController` | `index` |

**Page Vue :** `DashboardPage.vue`

Données exposées : jours et CA du mois courant, CA annuel, nombre de clients actifs (90 derniers jours), graphique mensuel sur 12 mois, 7 dernières saisies d'activité.

---

## Rapports d'activité

Calendrier mensuel de saisie du temps de travail par projet. Chaque saisie (`ActivityTime`) représente une portion de journée (0–100%) sur un projet donné.

| Méthode | URI | Controller | Action |
|---------|-----|------------|--------|
| GET | `/dashboard/reports` | `ActivitiesController` | `index` |
| POST | `/dashboard/reports` | `ActivitiesController` | `store` |
| GET | `/dashboard/reports/{report}` | `ActivitiesController` | `get` |
| PUT | `/dashboard/reports/{report}` | `ActivitiesController` | `update` |
| DELETE | `/dashboard/reports/{report}` | `ActivitiesController` | `destroy` |

**Page Vue :** `ActivityReportPage.vue`

La vue calendrier affiche les jours du mois en colonnes (avec détection des week-ends et jours fériés via l'API gouvernementale). Les projets propres et partagés sont listés en lignes. Les stats mensuelles (jours/CA par projet) sont calculées côté client.

---

## Partage de rapports

Génération d'un lien public unique pour partager le rapport d'activité d'un projet avec un tiers (lecture seule).

| Méthode | URI | Controller | Action |
|---------|-----|------------|--------|
| POST | `/dashboard/projects/{project}/share` | `ShareController` | `generate` |
| DELETE | `/dashboard/projects/{project}/share` | `ShareController` | `revoke` |
| GET | `/share/{share}` | `ShareController` | `apply` |

**Pages Vue :** bouton dans `DashboardPage.vue` / `ProjectsPage.vue`, vue publique `SharedActivityReportPage.vue`

Le partage utilise un modèle polymorphe `Share`. La route `/share/{share}` est publique (sans authentification).

---

## Clients

Gestion des clients (création, édition, suppression). Les clients sont affichés dans la sidebar et dans le dashboard.

| Méthode | URI | Controller | Action |
|---------|-----|------------|--------|
| POST | `/dashboard/clients` | `ClientController` | `store` |
| PUT | `/dashboard/clients/{client}` | `ClientController` | `update` |
| DELETE | `/dashboard/clients/{client}` | `ClientController` | `destroy` |

**Composant :** dialogs intégrés dans `DashboardPage.vue`

Chaque client appartient à un `User` et possède un TJM (`daily_rate`) par défaut, répercuté sur les nouveaux projets.

---

## Projets

Gestion des projets liés à un client. Un projet peut avoir un TJM propre (différent du client), une description et un budget maximum.

| Méthode | URI | Controller | Action |
|---------|-----|------------|--------|
| GET | `/dashboard/projects` | `ProjectController` | `index` |
| POST | `/dashboard/projects` | `ProjectController` | `store` |
| PUT | `/dashboard/projects/{project}` | `ProjectController` | `update` |
| DELETE | `/dashboard/projects/{project}` | `ProjectController` | `destroy` |

**Page Vue :** `ProjectsPage.vue`

La création d'un projet permet de créer un nouveau client à la volée. La liste inclut les projets partagés avec le compte authentifié.

---

## Suivi de facturation (Tracking)

Vue de suivi financier par client et par projet : jours travaillés par mois, montants facturés, dates de paiement et budget max.

| Méthode | URI | Controller | Action |
|---------|-----|------------|--------|
| GET | `/dashboard/tracking` | `TrackingController` | `index` |
| POST | `/dashboard/tracking/billing` | `TrackingController` | `storeBilling` |
| PUT | `/dashboard/tracking/billing/{entry}` | `TrackingController` | `updateBilling` |
| PUT | `/dashboard/tracking/projects/{project}/max-budget` | `TrackingController` | `updateProjectBudget` |

**Page Vue :** `TrackingPage.vue`

Filtrage par client (`?client_id=X`). Pour chaque projet, un tableau croise les mois et les `BillingEntry` associées. Les jours travaillés sont calculés depuis les `ActivityTime` (somme des `day_coverage / 100`).

---

## Paramètres du compte

Édition du profil : email, nom/prénom (ou nom d'organisation), et changement de mot de passe optionnel.

| Méthode | URI | Controller | Action |
|---------|-----|------------|--------|
| GET | `/dashboard/settings` | `SettingsController` | `edit` |
| PUT | `/dashboard/settings` | `SettingsController` | `update` |

**Page Vue :** `SettingsPage.vue`

Les champs affichés s'adaptent au type de compte (`User` vs `Organization`). Un message flash confirme la sauvegarde.
