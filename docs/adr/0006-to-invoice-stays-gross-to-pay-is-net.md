# Billing totals: `to_invoice` stays on gross amounts, `to_pay` uses net amounts

## Context and Problem Statement

`BuildsProjectBillingEntry::computeTotals()` aggregates worked, invoiced, discounted, and paid
amounts into two "still owed" figures: `to_invoice` (work not yet invoiced) and `to_pay` (invoiced
but unpaid). Should both consistently use gross or net amounts, or does each need its own basis?

## Decision Drivers

* A discount is a deliberate write-off applied to work that has *already* been invoiced, not a
  reduction of work that still needs to be invoiced.
* `to_pay` must represent real cash still expected from the client.

## Considered Options

* Use gross amounts (`amount`) for both `to_invoice` and `to_pay`
* Use net amounts (`amount - discount_amount`) for both
* Mix: `to_invoice` stays gross (`worked - invoiced`), `to_pay` uses net (`net_amount` on unpaid
  invoices)

## Decision Outcome

Chosen option: "Mix — gross for `to_invoice`, net for `to_pay`", because netting `to_invoice`
against a discount would understate how much work is still unaccounted for on the books, while a
discounted invoice really does reduce the cash still expected, so `to_pay` must reflect that.

### Consequences

* Good, because each total means exactly what its name says.
* Bad, because the two figures aren't computed the same way — a maintainer must remember the
  reasoning per figure, hence this record.
