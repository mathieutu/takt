# `Project.daily_rate`/`max_month_budget` stay as today-snapshot accessors, scoped to display and input

## Context and Problem Statement

Alongside the dated histories (`daily_rates`, `monthly_budgets`, see [ADR-0009](0009-rate-and-budget-history-as-dated-maps.md)),
several call sites only ever care about "today's" value: project lists, the billing page header,
budget-to-days hints for forward-looking figures, the create form, factories, and demo seeding.
Should every one of these call `getDailyRateForDate(today())`/`getMonthlyBudgetForDate(today())`
directly, or is a dedicated accessor worth keeping?

## Decision Drivers

* Repeating `getDailyRateForDate(today())` at every display/input call site is noisy for the (very
  common) "just show/set today's value" case.
* No billing calculation must ever be allowed to accidentally read a non-date-aware "current" value.

## Considered Options

* Drop the snapshot accessors entirely; force every caller through
  `getDailyRateForDate($date)`/`getMonthlyBudgetForDate($date)`
* Keep `daily_rate`/`max_month_budget` as virtual, today-snapshot accessors
  (`getDailyRateForDate(today())` under the hood), deliberately scoped to display and input only

## Decision Outcome

Chosen option: "Keep narrow, today-snapshot accessors", used only for display (project lists, the
billing page header, budget-to-days hints) and input (the create form, factories, demo seeding,
where "today" is the only sensible effective date). No billing calculation reads from them — every
money computation goes through `getDailyRateForDate($date)`/`getMonthlyBudgetForDate($date)` with
the date actually being billed.

### Consequences

* Good, because the common "today's value" case stays simple at every display/input call site.
* Bad, because `Project` now exposes two parallel APIs for the same underlying data — a future
  contributor must remember that no billing calculation may read from the snapshot accessor.
* Note: `Client.daily_rate` is unrelated — it's a plain, non-historized column (a client-level
  suggested rate offered when creating a new project), not the same concept under the same name.
