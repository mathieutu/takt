# Features & Routes

---

## Authentication

GitHub OAuth login (configurable) with a local dev fallback.

| Method | URI | Handler | Description |
|--------|-----|---------|-------------|
| GET | `/login` | `AuthController@show` | Login page |
| GET | `/login/redirect` | `AuthController@redirect` | GitHub OAuth redirect (when `AUTH_ENABLED=true`) |
| GET | `/login/callback` | `AuthController@callback` | GitHub OAuth callback |
| POST | `/login/disabled` | `AuthController@disabled` | Direct login by email (when `AUTH_ENABLED=false`) |
| GET | `/logout` | `AuthController@logout` | Logout |

---

## Dashboard

Overview of activity: monthly KPIs, 12-month revenue chart, outstanding invoices, and per-project summaries.

| Method | URI | Handler |
|--------|-----|---------|
| GET | `/` | `ShowDashboardHandler` |

**Data exposed:**
- Days worked and revenue for the current month (with projection based on month progress)
- 12-month stacked bar chart of revenue by project
- Year-over-year comparison (days, revenue)
- Outstanding invoices (total amount, count, overdue count)
- Weighted daily rate across active projects

---

## Timesheet

Monthly calendar grid for entering time per project. Each entry (`TimesheetEntry`) represents a fraction of a workday (0–100%).

| Method | URI | Handler |
|--------|-----|---------|
| GET | `/timesheet` | `ShowTimesheetHandler` |
| PATCH | `/projects/{project}/entries` | `ProjectController@syncEntries` |

The calendar highlights weekends and French public holidays (fetched from the government API and cached annually). Time entries support an optional title and description. Updates are applied optimistically on the client side.

---

## Projects

Full CRUD for projects, with soft delete, restore, and duplication.

| Method | URI | Handler |
|--------|-----|---------|
| GET | `/projects` | `ProjectController@index` |
| GET | `/projects/create` | `ProjectController@create` |
| POST | `/projects` | `ProjectController@store` |
| GET | `/projects/{project}/edit` | `ProjectController@edit` |
| PUT | `/projects/{project}` | `ProjectController@update` |
| DELETE | `/projects/{project}` | `ProjectController@destroy` |
| POST | `/projects/{project}/restore` | `ProjectController@restore` |
| POST | `/projects/{project}/duplicate` | `ProjectController@duplicate` |

Each project belongs to a client and can carry its own daily rate (TJM) overriding the client default. Projects support a monthly budget cap and a total budget cap. The creation form allows inline client creation.

---

## Clients

Manage clients with their default daily rate.

| Method | URI | Handler |
|--------|-----|---------|
| GET | `/clients/{client}/edit` | `ClientController@edit` |
| PUT | `/clients/{client}` | `ClientController@update` |
| DELETE | `/clients/{client}` | `ClientController@destroy` |
| POST | `/clients/{client}/restore` | `ClientController@restore` |

---

## Billing

Track invoices per project: amounts, payment dates, and notes. Monitor monthly and cumulative budget consumption.

| Method | URI | Handler |
|--------|-----|---------|
| GET | `/clients/{client}/billing` | `ClientController@showBilling` |
| POST | `/projects/{project}/invoices` | `ProjectInvoiceController@store` |
| PUT | `/invoices/{invoice}` | `ProjectInvoiceController@update` |
| DELETE | `/invoices/{invoice}` | `ProjectInvoiceController@destroy` |

The billing view shows a monthly breakdown per project: days worked (sum of `coverage / 100`), invoiced amount, and budget warnings.

---

## Sharing

Generate a public read-only link to share a client's billing report with a third party.

| Method | URI | Handler |
|--------|-----|---------|
| POST | `/clients/{client}/share` | `ClientController@storeShare` |
| DELETE | `/clients/{client}/share` | `ClientController@destroyShare` |
| GET | `/shares/{token}` | `ShowSharedHandler` |

The public route `/shares/{token}` is unauthenticated. It renders the same billing page in read-only mode, attributed to the user who shared it.

---

## API

Programmatic access to timesheet data via a Bearer token. The token is generated from the profile page and can be regenerated or revoked at any time.

| Method | URI | Handler | Auth |
|--------|-----|---------|------|
| GET | `/docs/api` | `ShowApiDocHandler` | session |
| PATCH | `/api/projects/{project}/entries` | `ProjectController@syncEntries` | `Bearer token` |

**Endpoint details — `PATCH /api/projects/{project}/entries`:**

Synchronises (creates or updates) timesheet entries for a project. Entries with `coverage: 0` are treated as deletions.

Request body:
```json
{
  "entries": [
    { "date": "2026-01-15", "coverage": 75, "title": "Dev feature", "description": "..." }
  ]
}
```

- `date` — required, `YYYY-MM-DD`
- `coverage` — required, integer 0–100 (percentage of a workday)
- `title` / `description` — optional

Response: `200 { "message": "Entries synchronized." }` | `401` invalid token | `403` project not owned | `422` validation error

---

## Profile

Edit profile, manage API token, and delete account.

| Method | URI | Handler |
|--------|-----|---------|
| GET | `/profile` | `UserController@edit` |
| PUT | `/profile` | `UserController@update` |
| DELETE | `/profile` | `UserController@destroy` |
| POST | `/profile/tokens` | `UserController@regenerateToken` |
| DELETE | `/profile/tokens` | `UserController@deleteToken` |
