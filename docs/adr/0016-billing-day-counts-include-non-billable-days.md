# Billing day counts show every worked day; only billable days carry an amount

## Context and Problem Statement

Some clients are non-profits: days given to them (`TimesheetEntry.billable = false`) are a donation,
for which the non-profit issues a tax receipt (Cerfa 11580). The billing PDF must justify what was
worked — yet the per-month `days_worked` field, despite its name, only ever held *billable* days
(it feeds days × rate, see [ADR-0007](0007-non-billable-entries-tracked-but-excluded-from-money.md)),
so given days vanished from every day count of the billing page and PDF. How should they show up?

## Decision Drivers

* A given day is a worked day: day counts must not set it apart from billed ones.
* No money figure (worked amount, to invoice, budgets, average daily rate) may ever include it
  ([ADR-0007](0007-non-billable-entries-tracked-but-excluded-from-money.md)).
* Takt has no reliable basis to value a donation — the amount belongs on the Cerfa, not in Takt.

## Considered Options

* Show billed and non-billed day counts side by side ("5 j + 2 j non facturés")
* Show a single worked-days count including every day; amounts stay billable-only
* A per-entry daily rate (0 for a given day) replacing the `billable` flag

## Decision Outcome

Chosen option: "single worked-days count", because a given day is worked like any other — it simply
doesn't produce an amount. `buildProjectBillingEntry()` exposes `worked_days` (every entry) and
`billable_days` (money base) per month, and `total_worked_days`/`total_billable_days` per project
(`computeTotals()`). The old `days_worked`/`total_days` names are gone, so any stale reader fails
loudly instead of silently mixing the two. Day counts and the activity chart use `worked_days`;
`worked`, `to_invoice`, budgets and the average daily rate stay on billable days. The PDF flags given
days in its notes and hides the money block of a project fully given over the period.

A per-entry rate was rejected: one figure cannot carry both "billed X" and "given, valued X"
(e.g. days sold 700 €, given days valued 360 €), so the `billable` flag would come back anyway.

### Consequences

* Good, because one document serves both billing and donation justification.
* Bad, because a month's "amount (days)" no longer reads as days × rate when it includes given days.
* Bad, because the rename changed the MCP `billing_recap` JSON contract (`days_worked`/`total_days`
  are gone).
