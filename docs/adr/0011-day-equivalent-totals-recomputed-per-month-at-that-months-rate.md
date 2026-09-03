# Day-equivalent billing totals are summed per month at that month's own rate

## Context and Problem Statement

`computeTotals()`'s `_days` figures (`invoiced_days`, `to_pay_days`, `to_invoice_days`) are
day-equivalents of money amounts — an invoice is a negotiated amount, not `days × rate`, so there's
no literal day count to read directly. How should an aggregate amount be turned into a day-equivalent
across months that may have been billed at different rates?

## Decision Drivers

* A rate change partway through a project must not retroactively distort the day-equivalent of
  months billed under the old rate.

## Considered Options

* Divide the aggregate amount by a single rate (e.g. the project's current one)
* Sum, per month, that month's amount divided by that month's own effective daily rate

## Decision Outcome

Chosen option: "Per-month division", because dividing by a single rate would misrepresent any month
invoiced under a different rate than today's. This mirrors the underlying `worked` amount per month
in `buildProjectBillingEntry()`, which already sums each entry at the rate effective on its own date
(`getDailyRateForDate()`, see [ADR-0009](0009-rate-and-budget-history-as-dated-maps.md)) rather than
`days_worked × one rate`, so a rate change taking effect mid-month still bills each day correctly.

### Consequences

* Good, because a historical rate change never distorts a prior month's day-equivalent figures.
* Bad, because the computation is a per-month sum rather than a single division, adding a little
  complexity for the same result in the common case (no rate change).
