# Modèles

---

## Account

**Rôle :** Entité d'authentification centrale. Représente un compte avec email et mot de passe. Étend `Authenticatable`. Utilise des UUIDs.

Un `Account` est toujours accompagné d'un `User` ou d'une `Organization` (même UUID), selon le type d'inscription.

| Attribut | Type | Description |
|----------|------|-------------|
| `type` | `AccountType` (enum) | `user` ou `organization` |
| `email` | string | Adresse email unique |
| `password` | string (hashed) | Mot de passe hashé |

**Relations :**
- `hasOne(User)` — profil individuel (prénom/nom)
- `hasOne(Organization)` — profil organisation (nom)
- `hasMany(SharedProject)` — projets partagés accessibles par ce compte

---

## User

**Rôle :** Profil d'un compte de type individuel. Partage le même UUID que son `Account`.

| Attribut | Type | Description |
|----------|------|-------------|
| `first_name` | string | Prénom |
| `last_name` | string | Nom de famille |

**Relations :**
- `belongsTo(Account)` — compte parent
- `hasMany(Client)` — clients créés par cet utilisateur
- `hasManyThrough(Project, Client)` — projets via ses clients

---

## Organization

**Rôle :** Profil d'un compte de type organisation. Partage le même UUID que son `Account`.

| Attribut | Type | Description |
|----------|------|-------------|
| `name` | string | Nom de l'organisation |

**Relations :**
- `belongsTo(Account)` — compte parent

---

## Client

**Rôle :** Représente un client pour lequel des projets sont réalisés. Appartient à un utilisateur.

| Attribut | Type | Description |
|----------|------|-------------|
| `name` | string | Nom du client |
| `daily_rate` | integer | TJM par défaut (en euros), répercuté sur les nouveaux projets |
| `user_id` | UUID | Propriétaire (`User`) |

**Relations :**
- `belongsTo(User)` — utilisateur propriétaire
- `hasMany(Project)` — projets de ce client

---

## Project

**Rôle :** Un projet facturé, lié à un client. Peut avoir son propre TJM (différent du client), une description et un budget maximum. Peut être partagé.

| Attribut | Type | Description |
|----------|------|-------------|
| `name` | string | Nom du projet |
| `client_id` | UUID | Client associé |
| `daily_rate` | integer | TJM du projet (peut différer du client) |
| `description` | string\|null | Description libre |
| `max_budget` | decimal(2)\|null | Budget maximum en euros |

**Relations :**
- `belongsTo(Client)` — client associé
- `hasMany(ActivityTime)` — saisies de temps de travail
- `hasMany(BillingEntry)` — entrées de facturation
- `hasMany(SharedProject)` — partages associés
- `hasManyThrough(Account, SharedProject)` — comptes ayant accès au projet
- `morphOne(Share, 'sharing')` — lien de partage public (polymorphe)

---

## ActivityTime

**Rôle :** Représente une saisie de temps de travail sur un projet à une date donnée. La couverture est exprimée en pourcentage de journée (0–100), permettant les demi-journées ou fractions.

| Attribut | Type | Description |
|----------|------|-------------|
| `project_id` | UUID | Projet concerné |
| `label` | string | Libellé de la saisie |
| `start_date` | date | Date de la saisie |
| `day_coverage` | integer | Fraction de journée en % (ex: 50 = demi-journée) |
| `comments` | string\|null | Commentaires libres |

**Relations :**
- `belongsTo(Project)`

---

## BillingEntry

**Rôle :** Représente une entrée de facturation mensuelle pour un projet : montant facturé, date de paiement et notes. Permet de suivre l'encaissement par rapport aux jours travaillés.

| Attribut | Type | Description |
|----------|------|-------------|
| `project_id` | UUID | Projet concerné |
| `month` | date | Mois de référence (ex: `2024-03-01`) |
| `amount_billed` | decimal(2) | Montant facturé en euros |
| `payment_date` | date\|null | Date de paiement reçu |
| `notes` | string\|null | Notes libres |

**Relations :**
- `belongsTo(Project)`

---

## Share

**Rôle :** Lien de partage public unique (UUID) associé à n'importe quelle entité partageable (actuellement : `Project`). Utilise une relation polymorphe. Fournit une méthode `url()` pour générer le lien public.

| Attribut | Type | Description |
|----------|------|-------------|
| `share_type` | string | Classe du modèle partagé (ex: `App\Models\Project`) |
| `share_id` | UUID | ID du modèle partagé |

**Relations :**
- `morphTo()` — entité partagée (polymorphe)

---

## SharedProject

**Rôle :** Table de liaison entre un `Account` et un `Project` partagé. Permet à un compte tiers d'accéder en lecture au rapport d'activité d'un projet dont il n'est pas propriétaire.

| Attribut | Type | Description |
|----------|------|-------------|
| `account_id` | UUID | Compte ayant accès |
| `project_id` | UUID | Projet partagé |

**Relations :**
- `belongsTo(Account)`
- `belongsTo(Project)`

---

## View

**Rôle :** Représente une vue filtrée (plage de dates + commentaires) sur un projet. Permet de sauvegarder des "vues" personnalisées d'un rapport d'activité.

| Attribut | Type | Description |
|----------|------|-------------|
| `title` | string | Titre de la vue |
| `project_id` | UUID | Projet concerné |
| `start_date` | date | Date de début |
| `end_date` | date | Date de fin |
| `comments` | string\|null | Commentaires |

**Relations :**
- `belongsTo(Project)`

---

## Enum : AccountType

| Valeur | Description |
|--------|-------------|
| `user` | Compte individuel (prénom/nom) |
| `organization` | Compte organisation (nom) |
