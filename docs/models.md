# Data Models

---

## User

The authenticated account. Linked to GitHub via OAuth or created locally.

| Attribute | Type | Description |
|-----------|------|-------------|
| `name` | string | Display name |
| `email` | string | Unique email address |
| `github_id` | string\|null | GitHub user ID |
| `avatar` | string\|null | GitHub avatar URL |
| `api_token` | string\|null | Bearer token for REST API access |

**Relations:**
- `hasMany(Client)` — clients owned by this user
- `hasManyThrough(Project, Client)` — all projects via owned clients
- `hasManyThrough(TimesheetEntry, Project, Client)` — all time entries

---

## Client

A client for whom projects are carried out. Holds a default daily rate applied to new projects.

| Attribute | Type | Description |
|-----------|------|-------------|
| `name` | string | Client name |
| `daily_rate` | integer\|null | Default TJM in euros |
| `user_id` | id | Owner (`User`) |
| `share_token` | string\|null | UUID token for public billing share link |
| `deleted_at` | timestamp\|null | Soft delete |

**Relations:**
- `belongsTo(User)`
- `hasMany(Project)`

---

## Project

A billable project linked to a client. Supports its own daily rate and budget caps.

| Attribute | Type | Description |
|-----------|------|-------------|
| `name` | string | Project name |
| `client_id` | id | Associated client |
| `daily_rate` | integer\|null | Project-level TJM (overrides client default) |
| `description` | string\|null | Free-form description |
| `max_month_budget` | decimal\|null | Monthly budget cap in euros |
| `max_total_budget` | decimal\|null | Total cumulative budget cap in euros |
| `deleted_at` | timestamp\|null | Soft delete |

**Relations:**
- `belongsTo(Client)`
- `belongsToThrough(User, Client)` — owner via client
- `hasMany(TimesheetEntry)`
- `hasMany(Invoice)`

---

## TimesheetEntry

A time entry on a project for a given date. Coverage is expressed as a percentage of a workday (0–100), enabling half-days or any fraction.

| Attribute | Type | Description |
|-----------|------|-------------|
| `project_id` | id | Associated project |
| `date` | date | Entry date |
| `coverage` | integer | Fraction of a day in % (e.g. 50 = half-day) |
| `title` | string\|null | Short label |
| `description` | string\|null | Free-form notes |
| `deleted_at` | timestamp\|null | Soft delete |

**Relations:**
- `belongsTo(Project)`

---

## Invoice

A billing record for a project: amount invoiced, payment date, and notes.

| Attribute | Type | Description |
|-----------|------|-------------|
| `project_id` | id | Associated project |
| `amount` | decimal | Amount invoiced in euros |
| `paid_at` | date\|null | Payment date |
| `notes` | string\|null | Free-form notes |
| `created_at` | timestamp | Invoice creation date (editable) |

**Relations:**
- `belongsTo(Project)`

---

## View

A saved filtered view over a project (date range). Currently unused in the UI.

| Attribute | Type | Description |
|-----------|------|-------------|
| `title` | string | View title |
| `project_id` | id | Associated project |
| `start_date` | date | Start of range |
| `end_date` | date | End of range |
| `comments` | string\|null | Notes |

**Relations:**
- `belongsTo(Project)`
