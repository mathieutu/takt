# Daily rate and monthly budget are dated histories, not single values

## Context and Problem Statement

Every place that bills work — the dashboard, timesheet, billing page, and PDF export — needs to know
the rate/budget that was actually in force on the specific date being billed. Should
`Project.daily_rate`/`max_month_budget` stay single, mutable columns, or become full histories?

## Decision Drivers

* Changing a project's rate or budget today must never retroactively change what past months were
  billed at.
* Every past month must keep billing at whatever rate was in force when it was worked, permanently.

## Considered Options

* Single current-value columns (`daily_rate`, `max_month_budget`)
* Dated history maps (`{effective_date => value}`), resolved per date being billed

## Decision Outcome

Chosen option: "Dated history maps", implemented as `daily_rates`/`monthly_budgets` (JSON columns
cast to `Collection`), resolved via `getDailyRateForDate($date)`/`getMonthlyBudgetForDate($date)` —
the latest entry on or before the date being billed — instead of reading one current value.

### Consequences

* Good, because changing a project's rate or budget today only affects work billed from today
  onward.
* Bad, because the two histories need asymmetric fallback rules for a date older than the whole
  history: `daily_rates` falls back to the earliest known rate, since a project always needs a rate
  to bill against (e.g. a timesheet entry logged before any rate was ever recorded); `monthly_budgets`
  does not — a date before the budget history began stays uncapped (`null`), since "no budget yet" is
  a meaningful state, distinct from retroactively applying the earliest budget (see
  `theoreticalBudgetThrough()`).
