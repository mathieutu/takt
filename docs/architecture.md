# Technical Architecture

---

## Overview

Takt is a monolithic Laravel application rendered as a single-page application via **Inertia.js v3**. The server renders page props directly and the frontend navigates without full page reloads. A thin REST API (`/api/*`) is also exposed for programmatic access, authenticated via Bearer token.

```
Browser ──Inertia visit──▶ Laravel route
                              │
                              ▼
                         Controller / Handler
                              │
                         Inertia::render('PageName', $props)
                              │
Browser ◀─── JSON props ──────┘
              (Vue hydrates in place)
```

---

## Backend

### Directory structure

```
app/
├── Http/
│   ├── Controllers/        # Route handlers
│   ├── Middleware/         # EnsureUserOwnsResource
│   └── Requests/           # Form request validation
├── Models/                 # Eloquent models
└── Services/               # HolidayService (French public holidays)
```

### Controllers

Single-responsibility handlers are used for complex read operations:

| Class | Role |
|-------|------|
| `ShowDashboardHandler` | Compiles all dashboard KPIs and chart data |
| `ShowTimesheetHandler` | Builds the monthly timesheet grid |
| `ShowSharedHandler` | Renders the public shared billing report |

Standard resource controllers (`ProjectController`, `ClientController`, etc.) handle CRUD.

### Middleware

`EnsureUserOwnsResource` is applied to all authenticated routes. It resolves route-bound models and verifies that the authenticated user owns the resource (or its parent) before allowing access.

### Services

`HolidayService` fetches French public holidays from `https://calendrier.api.gouv.fr` and caches them by year. Used by the timesheet to highlight non-working days.

### Key patterns

- **Soft deletes** on `Client`, `Project`, and `TimesheetEntry` — restored via dedicated POST routes.
- **`BelongsToThrough`** (via `staudenmeir/belongs-to-through`) for traversing `TimesheetEntry → Project → Client → User` in a single query.
- **Invokable controllers** (`__invoke`) for single-action handlers.
- **Route model binding** with `withTrashed()` where restore/delete on soft-deleted records is needed.

---

## Frontend

### Directory structure

```
resources/js/
├── Pages/          # Inertia page components (one per route)
├── Components/     # Shared Vue components
├── composables/    # Vue composables
├── types/          # TypeScript type definitions
└── wayfinder/      # Auto-generated typed route helpers
```

### Pages

Each Inertia page is a Vue SFC (`*.vue`) that receives typed props from the server. Pages use `defineProps` and import types generated from PHP models.

### Routing

[Laravel Wayfinder](https://github.com/laravel/wayfinder) generates typed TypeScript route helpers from the PHP routes. Import from `@/wayfinder` to get autocompleted, type-safe route URLs.

```ts
import { route } from '@/wayfinder/routes/projects'
router.visit(route('projects.edit', { project: id }))
```

### State management

No global store. State is local to each page component or passed via Inertia shared props. The timesheet applies **optimistic updates**: entries are updated client-side immediately and synced to the server in the background via `PATCH /projects/{project}/entries`.

### Charts

Revenue charts use **Chart.js** via `vue-chartjs`. Data is prepared server-side and passed as props.

---

## Authentication

Controlled by the `AUTH_ENABLED` config flag:

| Mode | Behaviour |
|------|-----------|
| `true` (default) | GitHub OAuth via Laravel Socialite. Users are created or matched by `github_id`. |
| `false` | Dev mode — the login page accepts any existing user's email without a password. |

The GitHub avatar is stored on the user record and displayed throughout the UI.

---

## Database

Default configuration uses **PostgreSQL** via Docker (`docker-compose.yml`). SQLite is also supported by setting `DB_CONNECTION=sqlite` in `.env`.

Migrations live in `database/migrations/` and follow a date-based naming convention.

---

## Key dependencies

| Package | Role |
|---------|------|
| `inertiajs/inertia-laravel` | Server-side Inertia adapter |
| `laravel/socialite` | GitHub OAuth |
| `staudenmeir/belongs-to-through` | Deep Eloquent relations |
| `mathieutu/exporter` | Data export utilities |
| `laravel/wayfinder` | Type-safe route generation for TypeScript |
| `laravel/pint` | PHP code style fixer |
| `pestphp/pest` | Test framework |
