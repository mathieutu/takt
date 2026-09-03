# `billable=false` timesheet entries stay in activity tracking, but drop out of every money figure

## Context and Problem Statement

`TimesheetEntry.billable` (default `true`) lets a day be logged as worked without it counting toward
revenue — e.g. unpaid exploratory work, goodwill time. Should a non-billable entry be excluded from
every aggregate (as if it weren't logged at all for reporting), or only from some?

## Decision Drivers

* An activity tracker (e.g. "days worked this month") exists specifically to show time actually
  worked — silently dropping non-billable time from it would understate it, defeating the purpose of
  `billable=false`.
* A money figure (budget bars, unbilled totals, revenue) must never include time that was
  deliberately marked as not billable.

## Considered Options

* Exclude non-billable entries from every aggregate everywhere
* Include them in activity-shaped figures, exclude them from money-shaped figures

## Decision Outcome

Chosen option: "Split by figure type". Activity-shaped figures keep non-billable entries: the
dashboard's `monthDays` worked-days counter and the revenue chart's `chartProjects` activity series
both include them, since they're pure "time actually worked" trackers, never multiplied by a rate.
Money-shaped figures exclude them: `TimesheetEntry::billableCoverageSum()` and every
`coverage × getDailyRateForDate()` computation (`buildProjectBillingEntry()`'s
`days_worked`/`worked`, `ShowHomeHandler`'s `workedDaysCount`/`totalWorkedAmount` and friends) filter
on `billable` first.

### Consequences

* Good, because each figure means exactly what it claims — activity trackers reflect real worked
  time, money figures never leak non-billable time into revenue or budgets.
* Bad, because every new aggregate over `TimesheetEntry` must explicitly decide which category it
  falls into, rather than following one uniform rule.
* Related: `monthRevenue`/`periodRevenue`/`projectedRevenue` on the dashboard are derived from
  `TimesheetEntry` (worked time × rate), never from `Invoice::amount` — so they reflect what was
  worked, unaffected by invoice discounts (see [ADR-0006](0006-to-invoice-stays-gross-to-pay-is-net.md)
  for why invoice-based totals do split on gross vs. net).
