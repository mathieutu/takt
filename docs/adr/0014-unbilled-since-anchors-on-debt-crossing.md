# `unbilledSince()` anchors on the debt crossing back into the red, not the last invoice date

## Context and Problem Statement

The project billing page and the dashboard's per-client widget show a "depuis Xj" badge for how long
work has gone unbilled. `Project::unbilledSince()` used to be `daysSinceLastInvoice`: the age of the
client's most recent invoice. That anchor broke in two ways: an advance invoice (paid ahead of work)
reset the counter, making brand-new debt on an otherwise healthy project look months overdue; and a
single day worked on an otherwise dormant project inherited the age of a much older invoice,
understating how fresh the actual debt was.

## Decision Drivers

* The badge must reflect since when there has actually been unbilled work, not since when an
  invoice was last issued.
* An advance invoice or a stale old invoice must not distort the figure for unrelated new debt.

## Considered Options

* Keep anchoring on the last invoice date (`daysSinceLastInvoice`)
* Replay timesheet entries and invoices chronologically, and anchor on the date the running balance
  last crossed from `≤ 0` up to `> 0`

## Decision Outcome

Chosen option: "Replay chronologically and anchor on the debt crossing", implemented as
`unbilledSince()`: timesheet entries are positive deltas (at the rate effective on their date) and
invoices are negative deltas; the method returns the date the running balance last crossed into
debt and stayed there — `null` when nothing is currently owed, even if there was in the past. This
is the true anchor for "depuis Xj", since the last invoice date is not a proxy for "since when has
there been unbilled work."

### Consequences

* Good, because an advance invoice no longer resets the clock on unrelated new debt.
* Good, because a single day worked on a dormant project no longer inherits the age of an unrelated
  old invoice.
* Good, because the behavior is covered by a dedicated test in
  `tests/Feature/Concerns/BuildsProjectBillingEntryTest.php`.
