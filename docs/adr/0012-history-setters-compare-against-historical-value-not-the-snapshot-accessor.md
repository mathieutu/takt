# Rate/budget history setters compare against `historicalValueAt()`, not the current-value accessor

## Context and Problem Statement

The snapshot setters (`Project::dailyRate()`/`maxMonthBudget()`, see
[ADR-0010](0010-current-rate-accessor-is-display-only.md)) and the dated setters
(`setDailyRateFrom()`/`setMonthlyBudgetFrom()`) need to decide, before writing to
`daily_rates`/`monthly_budgets` (see [ADR-0009](0009-rate-and-budget-history-as-dated-maps.md)),
whether an incoming value is a genuine change — and how to keep that history from accumulating
entries that don't represent one.

## Decision Drivers

* A rate of `0` must be representable on a brand-new project.
* `daily_rates` is NOT NULL; a no-op check that wrongly treats a real value as unchanged must never
  end up leaving it empty.
* The stored history should stay minimal — no entries that don't mark an actual change of value.

## Considered Options (equality check)

* Compare the incoming value against the current-value getter
  (`getDailyRateForDate()`/`getMonthlyBudgetForDate()`)
* Compare the incoming value against `historicalValueAt()` — the raw history lookup, `null` when
  nothing governs that date yet, with no default fallback

## Decision Outcome (equality check)

Chosen option: "Compare against `historicalValueAt()`", because the getter falls back to the
earliest known rate (or `0`) when the history is empty — comparing against it would make setting a
rate of `0` on a brand-new project look like a no-op and silently leave `daily_rates` empty,
violating the column's NOT NULL constraint. This was an actual bug, fixed in `609dd4c` and covered
by a dedicated factory test.

## Considered Options (redundant entries)

* Keep every inserted entry as written
* Prune entries that don't differ from the one immediately before them chronologically
  (`pruneRedundantEntries()`)

## Decision Outcome (redundant entries)

Chosen option: "Prune redundant entries", because inserting a new entry can make a later,
already-existing entry redundant (same value, no longer marking an actual change) — leaving it in
is just noise. For `monthly_budgets` specifically, if pruning collapses the whole history down to a
single `null` entry, it's replaced with a bare `null` rather than kept as a dated entry: a lone
"unlimited from some date" carries no information beyond "there has never been a budget."

### Consequences

* Good, because a rate/budget of a "falsy" value (`0`/`null`) is always representable and never
  silently dropped.
* Good, because the history stays minimal and doesn't grow unbounded across repeated no-op saves.
