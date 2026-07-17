# Plan: Invoice discount (`discount_amount`)

**Status:** Round 5 — implemented, verified (pint/lint/typecheck, `ShowHomeHandlerTest`), ready to commit.
**Branch:** `feat/invoice-discount`

## Round 5 (KPI card layout iteration, post round 4) — complete

Round 4's KPI cards still looked unbalanced live: uneven heights, dead space in the
"Travaillé en {mois}" and "Facturation" cards, content clustered left instead of
spread. Explored two alternative layouts (a single-card "ruban" with divider-separated
stat groups, and a 2-card "Activité"+"Facturation" merge) — both rejected live
("le ruban n'a pas résolu le problème", "les cartes fusionnées ça marche pas du
tout"). Landed back on **3 separate `UCard`s** (as before) but keeping the layout
fixes discovered along the way:

- **Root cause of the "everything clusters left" bug**: `items-start` on a
  `flex-col` container constrains the CROSS axis (width), not the main axis — it was
  added trying to fix vertical top-alignment but instead stopped children from
  stretching to the column's full width, so `justify-between` rows had no free space
  to distribute. Fix: drop `items-start`, rely on default `stretch`.
- **Grid columns**: `sm:grid-cols-[1fr_1.3fr_1fr]` (not equal thirds) — the period
  card carries 3 stat+tooltip groups and a bar, so it earns a bit more width; the
  other two get comparable width to each other.
- **"Travaillé en {mois}" card**: restructured from a single stacked value to a
  2-value spread row (Travaillé / Théorique*) matching the anatomy of the other two
  cards, so all three read as "label → spread values → detail" consistently.
- **Consulted the `dataviz` skill** for the stat-tile contract: dropped
  `tabular-nums` from hero/display figures (reserved for genuine tabular columns
  per the skill's guidance — proportional figures read better at display size).
- **Progress bar → 3 adjacent hoverable segments** (Perçu / reste du Facturé /
  reste du Travaillé) instead of 2 overlaid absolutely-positioned fills — needed so
  each part of the bar can carry its own hover tooltip (matching the number above
  it), which overlaid fills can't do (only the topmost layer would ever receive
  hover).
- **Deduplication**: the "hover a number → per-client breakdown" tooltip content was
  now needed in 6 places (3 stats × on-the-number and on-the-bar-segment). Extracted
  `resources/js/components/ClientAmountTooltip.vue` (title + items + `#footer`
  slot) — each of the 6 sites went from ~15 duplicated lines to ~6.
- **Red-above-10k semantics corrected**: user clarified the >10k€ warning belongs
  **only** in the "À facturer par client" tooltip (per-client, so you can see *which*
  client), not on the aggregate visible KPI number — moved accordingly.
- **Facturation card**: added `unbilledByClient[].daysSinceLastInvoice` (days since
  that client's last invoice, `null` if never invoiced) — shown per-client in the "À
  facturer" tooltip (`· depuis Xj` / `· jamais facturé`). Chosen over re-adding the
  removed invoice-count/overdue-count text (deliberately dropped in round 4, not a
  regression) because it answers a different, non-redundant question: not "how many
  unpaid invoices" but "which clients have gone quiet."
- **Month card tooltip**: the "Projeté : X — basé sur Y% du mois écoulé" detail
  lives only in the tooltip now (not duplicated as a card caption); the card itself
  just shows the two spread values, and the tooltip also gained a "Voir la
  timesheet du mois" button (`timesheet({ query: { month: props.to } })`).

*Note: a couple of "Travaillé"/"Théorique" labels drifted out of sync with each
other during hand-editing mid-session (one copy said "Théorique", its duplicate said
"Travaillé") — caught and normalized while deduplicating. Two inline labels
still read "Théorique" (kept as-is per the user's own edit, unconfirmed whether
intentional vs. leftover — worth a final glance before considering this fully settled).

## Round 4 (live dashboard iteration, post round 3) — complete

After round 3 shipped, the user drove a fast live-iteration pass on the Dashboard KPI cards specifically (no `ProjectBillingPage` changes this round). Iterated directly against the running app rather than through more up-front discussion, per the pattern that's worked best so far in this feature.

- **KPI grid**: back to 3 equal-width columns (`sm:grid-cols-3`, no `items-start`) — the 4-column/2-span layout from round 3 made "Travaillé en {mois}" too narrow (text wrapped). Equal-height across cards is now guaranteed structurally by grid stretch, not content-matching.
- **"Sur la période" card**: triptyque reordered to **Perçu | Facturé net (large) | Travaillé** (was Travaillé|Facturé|Perçu) to match the funnel semantics of the progress bar underneath (perçu ⊆ facturé ⊆ travaillé, drawn solid→light→track left to right). Added a 2-segment progress bar (perçu solid over facturé/40 over the travaillé-as-100%-track). Trend badge and per-client breakdowns split into **3 independent tooltips** (one per stat: Perçu/Facturé/Travaillé), each with its own client list; the Travaillé tooltip carries the trend-vs-previous-period line (it's mathematically `periodRevenue`'s trend, not a card-wide figure) plus a "Voir la timesheet du mois" button. Facturé's tooltip shows `X% du travaillé facturé (brut)` (new `kpis.periodGrossInvoiced` — gross, deliberately not net) and Perçu's shows `X% du facturé (net) perçu`, both bottom-anchored like the trend line, colored via a new `ratioColorClass` scale (red <60%, orange <80%, green ≥80%).
- **"Factures impayées" → "Facturation"**: renamed; "À facturer" (`kpis.unbilledAmount`, new — gross, sum of `projects[].unbilled`) and "Impayé" shown at equal visual weight side by side, each with its own tooltip (per-client list / per-invoice list), rows now link directly to the client's billing page (`showBilling`). ">10k€ à facturer" now renders in red. The invoice-count/overdue-count lines were deliberately dropped (not a regression — user confirmed not needed).
- **Chart card header**: absorbed the period's day-count/daily-rate/occupation summary line (moved out of the KPI card). Month-level occupation moved to the bar chart's hover tooltip footer instead of a static header line (`total / workingDays` computed per hovered month) — the user didn't want month occupation duplicated as static text once it's a click/hover away.
- **Dead code removed** as a consequence of the above: `kpis.fillRate`, `kpis.workingDays`, `kpis.trendRevenue`, `kpis.prevMonthRevenue`, `kpis.outstandingCount`, `kpis.overdueCount` and their backend computations (`$prevMonthStart`, `$prevMonthRevenue`, `$trendRevenue`, `$outstandingCount`, `$overdueCount`) — all fully unused after the redesign, not deferred.
- New backend aggregates: `kpis.periodGrossInvoiced`, `kpis.unbilledAmount`, `unbilledByClient`, `periodInvoicedByClient`, `periodPaidByClient` (each `{clientName, amount}`, mirroring `periodRevenueByClient`'s shape) — `unbilledByClient` and `outstandingInvoices` also carry `clientId` now, for the billing-page links.
- Verification: `vendor/bin/pint --dirty` passed, `yarn lint:fix` clean, `yarn typecheck` clean, `ShowHomeHandlerTest` 6/6 passing.

## Round 3 (post-live-review revisions, batch 3) — complete

Collected verbatim from the user after testing round 2 live, then clarified through a plain-conversation back-and-forth (not a pre-baked menu) before any code was touched, per this project's established pattern.

### Clarified design decisions (from conversation, not guessed)

- **Gross vs net scope, confirmed**: `to_invoice` (reste à facturer) stays **gross** — unchanged, matches the original documented design. Everywhere else that tracks budget consumption now factors in discounts (see next point).
- **Budget-consumption formula**: the user proposed "travaillé − facturé brut + facturé remisé", which algebraically simplifies to **travaillé − remises accordées** (since `facturé brut − facturé remisé = remise totale`). Adopted as: `consommé = totalWorked − totalDiscount` (and the cumulative-by-month equivalent, summing discounts on invoices up to that month). This keeps the base théorique (budget is consumed by work done, not by invoicing lag) but corrects it down once a discount has actually reduced what will be charged. Not yet fired/uninvoiced months are unaffected (discount = 0 there).
- **Chart line unchanged, but travaillé/facturé/perçu visibility restored** — the user did NOT want "Perçu" removed as a concept, only the extra chart *line* (confirmed: round 2's single-line chart stays). Re-added a compact 3-number breakdown (Travaillé / Facturé / Perçu) in the Dashboard's "Sur la période" card. This required a new backend aggregate (`kpis.periodPaid`, grouped by `paid_at` within the period) since round 2 had fully removed all `paid_at`-based aggregation.
- **"Théorique" wording**: replaced with **"Travaillé"** everywhere it appeared as a bare qualifier — this is the actual, meaningful label (a value derived from worked days × daily rate, independent of invoicing) and needed no separate explanatory tooltip once renamed correctly.
- **Card/tooltip duplication**: the user corrected the initial framing — the tooltip on the "Sur la période" card wasn't pure duplication, it added real extra info (working-days denominator, previous-period absolute amount) alongside the repeated percentages. Resolved by folding the extra info directly into the card body (occupation line now includes "sur X j. ouvrés"; trend badge carries the previous amount as a native `title` hover) and removing the now-fully-redundant duplicate block from the tooltip, which is left with only its unique content (per-client breakdown).
- **KPI card widths**: confirmed OK to abandon equal-width cards. "Sur la période" (now carrying the triptyque) spans 2 of 4 grid columns; the other two cards keep 1 column each. Resolves the "Factures impayées" card-height mismatch by construction — no more forced-equal-width row to create dead space against.
- **Explicit formula-style labels**: adopted the compact format `12 000 € (20 j × 600 €/j)` for `ProjectBillingPage.vue`'s client header ("Rémunération" → "Montant travaillé", now showing the days × rate breakdown inline instead of a separate "TJ moyen" stat).

### Implemented

- [x] `ProjectBillingPage.vue` — `remainingToConsume`, `totalConsumptionPercent`, and the cumulative-by-month equivalents (`isCumulativeOverBudget`, `cumulativeRemainingToConsume`, `cumulativeConsumptionPercent`) now subtract discounts from worked value (new `getCumulativeDiscount`/`getCumulativeConsumed` helpers).
- [x] `ProjectBillingPage.vue:318` (tooltip) — hoisted `monthlyNetInvoiced` into a `computed` shared by the chart dataset and the tooltip callback; tooltip now reads `monthlyNetInvoiced.value[ctx.dataIndex]` (that month's actual net amount) instead of `ctx.parsed.y` (the cumulative line value). Label text dropped "cumulé" accordingly.
- [x] `ProjectBillingPage.vue` client header — "Rémunération"/"TJ moyen" merged into "Montant travaillé" with an inline `(20 j × 600 €/j)` breakdown.
- [x] `ProjectBillingPage.vue:843/853` — `Remise (%)` field now precedes `Remise (€)` in the invoice form.
- [x] `ProjectBillingPage.vue:692` — added `min-w-0` to the notes `<span>` so `truncate` actually works instead of overflowing.
- [x] `DashboardPage.vue` — removed the "X € remisés cumulés" line from the projects table; `projects[].discount` dropped from the backend response (`ShowHomeHandler.php`) and its test assertion, now fully unused.
- [x] `DashboardPage.vue` — KPI grid switched to `sm:grid-cols-4`; "Sur la période" card is `sm:col-span-2`, the other two cards `sm:col-span-1` each.
- [x] `DashboardPage.vue` "Sur la période" card — added the Travaillé/Facturé/Perçu triptyque; "théorique" wording replaced by "Travaillé"/"Travaillé par client"; occupation line now includes the working-days denominator; trend badge carries the previous-period amount via `title`; tooltip's duplicate occupation/trend block removed (per-client breakdown only).
- [x] `ShowHomeHandler.php` — added `kpis.periodPaid` (net amount summed by `paid_at` within the selected period, mirroring `periodNetInvoiced`'s `created_at`-based grouping but for payments).
- [x] `tests/Feature/Http/Controllers/ShowHomeHandlerTest.php` — removed the now-dead `projects.0.discount` assertion; added a test for `kpis.periodPaid` (paid-within-period counts regardless of issue date; issued-within-period-but-unpaid does not count).
- [x] Full verification: `php artisan test --compact` → 63/64 passing (1 pre-existing/environmental `ExampleTest` failure, unrelated — see Round 2 log), `vendor/bin/pint --dirty --format agent` → passed, `yarn lint:fix` → clean, `yarn typecheck` → clean.

**Not done in this round** (out of scope / lower priority, flagged for a possible follow-up): the "vocabulary audit" was applied to the two spots explicitly flagged (Dashboard "Sur la période" card, `ProjectBillingPage` client header) rather than exhaustively across every amount label in the app — worth a dedicated pass if more ambiguous labels turn up during live review.

**Next step whenever resumed**: user has not yet looked at round 3 live (dev server was running on `localhost:8084`, confirmed still up). Ask for a live review before considering any of rounds 1–3 final, then ask about committing — nothing from this feature has been committed yet.

## Round 2 (post-live-review revisions) — complete

After the round-1 implementation below (2-line chart, 4-card KPI grid), the user tested it live and pushed back on several points. **Do not treat the 2-line chart / gross-vs-net-per-KPI-card designs described earlier in this doc as current** — they were walked back. Current direction, confirmed by the user:

- Chart (Dashboard **and** ProjectBillingPage): back to **one line only**, labeled "Facturé", showing the **net** amount (after discount) grouped by invoice **issue month** (`created_at`) — not by `paid_at`. The two-line gross/net-by-payment-date design added complexity without payoff once seen live.
- Dashboard "Sur la période" card: headline is now the **net invoiced amount over the period** (`kpis.periodNetInvoiced`), not the theoretical work-value (`kpis.periodRevenue`, which stays as smaller/secondary text). No more separate "Facturé"/"Perçu" two-row block in the card body.
- Everywhere a discount delta line appears ("X € de remise"), replace with a **percentage** ("X % de remise") — confirmed to apply everywhere, since showing gross+net explicitly makes the absolute € delta redundant, but the % is not.
- Bug: on `ProjectBillingPage.vue`'s tfoot "Facturé" total, the struck-through gross amount, net amount, and day-count annotation rendered with **no space between them** ("16 800 €16 000 €(28 j)") — root cause: Vue's whitespace-condense mode drops newline-only whitespace between inline `<span>`s in templates; fix is wrapping them in an `inline-flex gap-*` span (already applied as the fix pattern, see task 16 below).
- PDF footer "Montant facturé" line looks broken with 4 stacked lines and the "+" sign floating vertically centered across the whole stack instead of aligned with the gross amount (which is what the balance calculation actually uses). Fix: move the discount % into the **label** column (e.g. "Montant facturé (−20 %)") instead of a 3rd/4th stacked line under the amount, and align "+" to the top (gross) row.
- Dashboard "Factures impayées" (middle KPI card) has much less content than its 2 neighbors, so in the equal-height grid row it shows a lot of dead vertical space at the bottom. Fix: add `items-start` to the KPI grid container (`grid grid-cols-1 gap-4 sm:grid-cols-3`) so cards size to their own content instead of stretching to match the tallest sibling.

### Progress checklist (round 2)

- [x] **Backend** `ShowHomeHandler.php`: reverted — `chart.invoiced` removed, `chart.net` groups by `created_at` again (net amount), `kpis.periodInvoiced`/`kpis.periodPaid` replaced by single `kpis.periodNetInvoiced` (net by `created_at` within period).
- [x] `tests/Feature/Http/Controllers/ShowHomeHandlerTest.php` updated to match (2 tests, both passing: `chart.net` reflects net-by-created_at regardless of payment; `kpis.periodNetInvoiced` reflects net-by-created_at within period).
- [x] `DashboardPage.vue`: chart back to single "Facturé" line (from `chart.net`); tooltip filter/label updated; type updated (`chart.invoiced` removed, `kpis.periodInvoiced`/`periodPaid` → `kpis.periodNetInvoiced`).
- [x] `DashboardPage.vue`: "Sur la période" card — headline is now `kpis.periodNetInvoiced` with caption "Facturé net sur la période"; théorique (`periodRevenue`) demoted into the existing secondary days/TJ/mois line; Facturé/Perçu two-row block removed; tooltip's per-client breakdown (still théorique, unchanged data source) now has a small "Théorique par client" caption to disambiguate from the new headline metric.
- [x] `ProjectBillingPage.vue`: chart back to single "Facturé" line (net, by invoice month) — removed `grossInvoicedInMonth`/`netPaidInMonth`/paid_at-based `chartMonths` extension; restored `monthNetInvoiced` helper; tooltip label updated.
- [x] `ProjectBillingPage.vue`: tfoot "Facturé" total spacing bug fixed (wrapped gross/net/days-annotation in `<span class="inline-flex items-center justify-end gap-1.5">`).
- [x] `ProjectBillingPage.vue`: tfoot "Facturé" total discount line — "€ de remise" → "% de remise" (new `discountPercent(gross, discount)` helper added near other helpers).
- [x] `ProjectBillingPage.vue`: "À payer" card discount line — same € → % change (reuses `discountPercent()`, factored into `totalDiscountPercent(project)`/`unpaidDiscountPercent(project)` helpers to keep template lines under the 120-char ESLint limit).
- [x] `DashboardPage.vue` "Factures impayées" KPI card discount line — added `discountPercent()` helper (same formula as ProjectBillingPage.vue's) and switched to "% de remise incluse".
- [x] `resources/views/exports/billing.blade.php`: "Montant facturé" footer row fixed — discount % moved into the label `<td>` (e.g. "Montant facturé (−20 %)"), amount `<td>` back to 2 lines max (gross struck-through + net), `vertical-align: top` added to both the sign (`+`) and amount `<td>`s so "+" aligns with the gross (top) line.
- [x] `DashboardPage.vue`: `items-start` added to the KPI grid container, fixing "Factures impayées" card's dead vertical space.
- [x] Full verification: `php artisan test --compact` → 62/63 passing (the 1 failure, `ExampleTest`, is pre-existing/environmental — confirmed via `git stash` comparison, unrelated to this work), `vendor/bin/pint --dirty --format agent` → passed, `yarn lint:fix` → clean (two line-length violations fixed by extracting `totalDiscountPercent`/`unpaidDiscountPercent` helpers), `yarn typecheck` → clean.

**Nothing has been committed yet.** Working tree has uncommitted changes across: `app/Http/Controllers/ShowHomeHandler.php`, `tests/Feature/Http/Controllers/ShowHomeHandlerTest.php`, `resources/js/pages/DashboardPage.vue`, `resources/js/pages/ProjectBillingPage.vue`, `resources/views/exports/billing.blade.php`, this doc. Next step whenever resumed: report the round-2 summary to the user and ask whether to commit.

> This document is the living source of truth for this feature. Whoever implements it (human or agent) must keep the checklist below up to date — check items off as they land, add sub-items if scope shifts, and note any deviation from the plan directly here (with the reasoning), rather than silently diverging.

## Problem

The Billing module tracks a balance `to_invoice = worked − invoiced` per project, shown on `ProjectBillingPage.vue` and on the client-facing PDF export. Some invoices carry a commercial discount (-20%, -50%…), decided in an external tool that actually issues the real invoices — takt only tracks billing, it doesn't issue invoices.

Two designs were explored and rejected before the one below:
- Storing the net (post-discount) amount directly in `amount` → `to_invoice` drifts forever on discounted invoices, since the system thinks work remains unbilled when the discount was actually a deliberate, final write-off.
- Storing a `discount_percent` and reconstructing the nominal amount via `amount × pct / (100 − pct)` → **mathematically breaks at 100% discount** (division by zero / indeterminate form: at `amount = 0`, no nominal amount can be reconstructed), and introduces unnecessary floats.

## Chosen design

**`amount` becomes the gross/nominal invoiced amount** (what the invoice covers of the work, before discount) — this is what you'd naturally enter first, and it's already known (it's the worked amount shown on the same page). A new `discount_amount` field (cents, integer, default `0`, **not nullable**) stores the discount in absolute value. The amount actually received is a plain subtraction: `net_amount = amount − discount_amount`, shown for verification but never stored.

Why this works:
- **No division, no floats** — everything stays integer cents; `amount` and `discount_amount` are both entered directly, never derived from each other via a fragile formula.
- **100% discount is trivial**: `discount_amount = amount` → `net_amount = 0`. No singularity.
- **`to_invoice` keeps its original formula unchanged**: `to_invoice = totalWorked − sum(amount)`, since `amount` (gross) already represents the full work covered by the invoice, discounted or not. No "discount" line needed in the balance calculation — the discount only affects real cash (`to_pay`), never the work/invoiced balance.
- **The percentage (-20%, -50%) becomes a pure display concern**, computed after the fact for a badge, never used in a calculation — so its imprecision is harmless.

What actually changes with discounts: **`to_pay`** (and the dashboard's `outstandingAmount`) — cash still owed on unpaid invoices — must use the **net** amount, not gross. That's the only real-cash figure in the module, and the only one the discount must affect numerically.

## Existing duplication found (to factor while touching this code)

The `to_invoice`-style balance is computed independently in **4 places**:
1. `app/Http/Concerns/BuildsProjectBillingEntry.php::buildBillingExportViewData()` (PDF export, server-side).
2. `resources/js/pages/ProjectBillingPage.vue::projectTotals()` (live Billing page, client-side).
3. `resources/js/components/ExportBillingModal.vue::projectToInvoice()` (export modal preselection, client-side).
4. `app/Http/Controllers/ShowHomeHandler.php` (dashboard `unbilled`/`outstandingAmount`, fully separate logic, doesn't use the `BuildsProjectBillingEntry` trait at all).

Decision: factor 1–3 into a single PHP method (`computeTotals()` in the trait), reused by the page-live path (new `buildProjectBillingEntryWithTotals()`), the PDF export, and the export modal (which will just read the precomputed `to_invoice` from props instead of recomputing it). The dashboard (4) stays a separate implementation (out of scope to merge with the trait) but gets the same gross/net correction applied by hand.

## Implementation checklist

### Backend — schema & model
- [x] Migration `add_discount_amount_to_invoices_table`: `unsignedInteger('discount_amount')->default(0)->after('amount')` — not nullable, default `0`.
- [x] `app/Models/Invoice.php`: cast `discount_amount => integer`; add `netAmount(): int` (`amount - discount_amount`); add `discountPercentForDisplay(): ?int` (cosmetic only, `null` when `discount_amount <= 0`, else `round(discount_amount / amount * 100)`); update PHPDoc.
- [x] `app/Http/Controllers/ProjectInvoiceController.php`: add `'discount_amount' => ['required', 'integer', 'min:0', 'lte:amount']` to `store()` and `update()`.

### Backend — billing calculations
- [x] `app/Http/Concerns/BuildsProjectBillingEntry.php`:
  - [x] Invoice mapping (`buildProjectBillingEntry`, invoices `map()`): add `discount_amount`, `discount_percent`, `net_amount`.
  - [x] New private `computeTotals(Collection $months, int $dailyRate): array` returning `days/worked/invoiced/discount/to_invoice/to_pay` (`to_pay` sums `net_amount` on unpaid invoices; `to_invoice` stays `worked - invoiced` on gross `amount`).
  - [x] `buildBillingExportViewData()`: reuse `computeTotals()` for `$priorMonths`/`$periodMonths` instead of inline sums; expose `total_discount`.
  - [x] New `buildProjectBillingEntryWithTotals()`: `buildProjectBillingEntry()` + `computeTotals()` over all months (no period filter) — for the live page.
- [x] `app/Http/Controllers/ClientController.php::showBilling()`: use `buildProjectBillingEntryWithTotals()` instead of the raw `buildProjectBillingEntry()`.

### Backend — dashboard (separate code path, no trait)
- [x] `app/Http/Controllers/ShowHomeHandler.php`:
  - [x] `outstandingAmount` (line ~105): switch to `$outstanding->sum(fn ($i) => $i->netAmount())` (real cash owed, same correction as `to_pay`).
  - [x] Add `outstandingDiscount` (`$outstanding->sum('discount_amount')`).
  - [x] `outstandingInvoices` (lines ~109-116): add `discountAmount`/`discountPercent` per entry.
  - [x] `chartBilled` stays gross (`sum('amount')`, consistent with `unbilled`); add parallel `chartDiscount` series (`sum('discount_amount')` per month).
  - [x] `unbilled` per project (line ~194): formula unchanged (`max(0, worked - sum(amount))`) — automatically benefits from the gross convention, same drift fix as the Billing page, for free. Add `discount` per project (`$p->invoices->sum('discount_amount')`).
  - [x] `monthRevenue`/`periodRevenue`/`projectedRevenue`: **no change** — these come from `TimesheetEntry`, never from `Invoice::amount`. Worth a comment so nobody "fixes" them later thinking they're missing the discount.

### Frontend — types
- [x] `resources/js/types/billing.ts`: `MonthInvoice` +`discount_amount`, `discount_percent`, `net_amount`; `ProjectWithBilling` +`total_days`, `total_worked`, `total_invoiced`, `total_discount`, `to_invoice`, `to_pay`.
- [x] `resources/js/pages/DashboardPage.vue` (`DashboardProps` type): `kpis.outstandingAmount` becomes net, +`kpis.outstandingDiscount`, `chart.discount: number[]`, `projects[].discount: number`, `outstandingInvoices[].discountAmount`/`discountPercent`.

### Frontend — Billing page & export modal
- [x] `ProjectBillingPage.vue::projectTotals()`: stop recomputing `totalDays`/`totalWorked`/`totalInvoiced`/`toInvoice`/`toPay` — read them from the (now server-computed) props; add `totalDiscount`.
- [x] `monthInvoiced()`: unchanged (gross per-month sum, not discount-related).
- [x] Invoice form (`form`, `openAddInvoice`, `openEditInvoice`, `submitInvoice`): add `discount_amount` (default `'0'`, never null/nullable) and a local-only `discountPercentInput` convenience field (not submitted) with light two-way sync (editing % recomputes €, editing € recomputes % for display — no watch loop, two separate handlers). Show a non-editable "net amount received" preview computed client-side (`amount - discount_amount`).
- [x] `ExportBillingModal.vue::projectToInvoice()`: remove; read `project.to_invoice` directly from props (now precomputed server-side).

### Backend/Frontend — data plumbing for visuals (no visual design yet)
- [ ] `resources/views/exports/billing.blade.php`: the balance arithmetic (`$openingToInvoice`, `$toInvoice`, `$totalWorked`, `$totalInvoiced`) stays **unchanged** — `amount` already being gross, no "discount" line is needed there. Per-invoice `discount_amount`/`discount_percent`/`net_amount` and the aggregate `$project['total_discount']` are available in the view data, ready to be surfaced once the visual design is decided (see UX/UI phase below).

### UX/UI design study (before final visual integration)
- [x] Spin up a dedicated design/UX pass to decide **how** discount info is shown, consistently, across `ProjectBillingPage.vue`, `DashboardPage.vue`, and `resources/views/exports/billing.blade.php`. Visual study published as an Artifact, comparing 3 variants (badge pill / struck-through amount / discreet parenthetical) with a concrete recommendation. User validated the direction in conversation (not by picking a variant blind) before any code was touched.

**Decisions validated by the user:**
- **Per-invoice amounts** (only place `discount_percent`/`net_amount` exist meaningfully at the single-invoice level): **Variant B** — the gross amount struck through, followed by the net amount shown in the normal (paid/unpaid-colored) style, with a small faint `−X%` tag. **Overrides the study's own recommendation** (Variant A, neutral badge pill) and the user's own earlier answer in this same conversation picking Variant A — the user reviewed the artifact directly and changed their mind in favor of Variant B, explicitly accepting the trade-off already flagged (it reintroduces a net amount in the read-only UI, which the original design deliberately avoided outside the invoice form). No strike-through/net pair when `discount_percent === null` (plain amount as before).
- **Aggregates** (project totals, dashboard KPI, PDF footer — where only a summed `total_discount` *amount* exists, never a meaningful percentage): a small muted/discreet text line (e.g. "− 660,00 € de remise"), never a badge, never a `%`. Explicitly *not* shown on "À facturer" (stays pure-gross by design, untouched by discounts) — only on "À payer" / "Montant facturé" / dashboard KPI / PDF footer, where a discount actually happened.
- **PDF** (`billing.blade.php`): same two treatments, reproduced as static markup (bordered `span` for the pill, no hover/interactivity) — `billing.blade.php` already hand-defines `--ui-*` tokens standalone, reuse them (`--ui-bg-elevated`, `--ui-border`, `--ui-text-muted`) for a faithful pill/discreet-text rendering.

**Deviation from the study's chart recommendation (user's explicit call, overriding the study):** the design study proposed leaving the dashboard's "Facturé" chart line on gross `chart.billed` and only adding a discreet second tooltip line for `chart.discount`. The user overruled this: the line represents money actually earned, so it must show **net**, not gross — and for consistency, the same change applies to `ProjectBillingPage.vue`'s own activity chart (which has an equivalent cumulative "Facturé" line, client-side, via `monthInvoiced()`). Both lines are renamed **"Perçu"** to avoid implying gross. This reopens two files already marked complete above:
  - [x] `app/Http/Controllers/ShowHomeHandler.php`: `$chartBilled` renamed `$chartNet`, now sums `$i->netAmount()` instead of `$i->amount`; Inertia `chart.billed` key renamed `chart.net`. `chart.discount` untouched (still exposed, no longer needed for a tooltip line but harmless to keep).
  - [x] `resources/js/pages/DashboardPage.vue` (`DashboardProps` type): `chart.billed: number[]` → `chart.net: number[]`.
  - [x] `resources/js/pages/DashboardPage.vue` (chart): line dataset reads `chart.net`, label `'Facturé'` → `'Perçu'`; tooltip `filter`/`label` callbacks updated to match the new label.
  - [x] `resources/js/pages/ProjectBillingPage.vue` (chart only, **not** the "Facturé" table column which stays gross per the entry above): new local helper summing `net_amount` per month for the chart's line dataset only; label `'Facturé'` → `'Perçu'`; tooltip callback updated to match.
  - [x] Dashboard test extended to cover `chart.net` reflecting net amounts on a discounted invoice.

**Visual implementation (this pass):**
- [x] `ProjectBillingPage.vue`: Variant B (struck-through gross + net amount + discreet `−X%`) on invoice rows (`m.invoices`, when `inv.discount_percent !== null`); discreet "− X de remise" line under the "Facturé" tfoot total (from `project.total_discount`, only when `> 0`); discreet "− X de remise sur facture(s) non payée(s)" under the "À payer" card (discount summed over unpaid invoices only, computed client-side from already-available per-invoice `discount_amount`/`paid_at`); **no** discount mention on "À facturer" (stays gross, untouched).
- [x] `DashboardPage.vue`: discreet "− X de remise incluse" under the "Factures impayées" KPI value (from `kpis.outstandingDiscount`, only when `> 0`); Variant B (struck-through gross + net, computed client-side as `amount − discountAmount` since `outstandingInvoices[]` has no server-computed net field) in the KPI's tooltip rows; discreet "· X remisés cumulés" next to the existing `unbilled` badge in the projects table (from `projects[].discount`, only when `> 0`), kept visually separate from the colored `unbilled` badge per the study ("describes a past fact, not the current balance state").
- [x] `resources/views/exports/billing.blade.php`: Variant B (struck-through gross + net amount + discreet `−X%`, two separate `data-currency-cents` spans since the PDF's amount-formatting script targets one element per attribute) next to invoice amounts in the per-invoice table (when `discount_percent !== null`); discreet line under "Montant facturé" in the balance footer (from `$project['total_discount']`, only when `> 0`). Balance arithmetic itself stays untouched.

**Mid-flight variant change:** the design study and the user's own first answer (in this conversation) both picked Variant A (neutral badge pill) for per-invoice amounts — recorded above and already implemented once. Before the implementing agents' work was verified/committed, the user reviewed the published artifact directly and switched to **Variant B** (struck-through gross + net) instead, explicitly accepting the trade-off already flagged (reintroduces a net amount into read-only UI). The two in-flight agents (Vue pages, PDF) were redirected mid-task via a follow-up instruction scoped strictly to the per-invoice markup — the aggregate discreet-text treatment and the chart net/Perçu change were already correct and untouched by the correction. `tests/Feature/ExportBillingTest.php`'s PDF discount test was updated accordingly (it now asserts the net amount *does* appear in the HTML, inverting its previous assertion written for the deferred-display state).

### Tests (Pest)
- [x] `tests/Unit/Models/InvoiceTest.php` (new, `--unit`): `netAmount()`/`discountPercentForDisplay()` incl. 100%-discount edge case.
- [x] `database/factories/InvoiceFactory.php` (new — doesn't exist yet).
- [x] `tests/Feature/Http/Controllers/ProjectInvoiceControllerTest.php` (new): validation incl. `lte:amount`, default `0`.
- [x] `tests/Feature/Concerns/BuildsProjectBillingEntryTest.php` (new — placed under `Feature` rather than `Unit` as originally sketched: this repo's `tests/Pest.php` only binds `RefreshDatabase` `.in('Feature')`, and the trait needs DB-backed Eloquent relations): non-drift regression — a 100%-discounted invoice matching the month's worked amount yields `to_invoice === 0`; `to_pay` excludes the discounted portion of an unpaid invoice.
- [x] `tests/Feature/ExportBillingTest.php` (extended): the blade template doesn't render `discount_amount`/`net_amount` yet (deferred to the UX/UI pass below), so the added test instead asserts PDF generation succeeds for a discounted invoice and that the gross `amount` still renders correctly in the HTML (not the net).
- [x] `tests/Feature/Http/Controllers/ShowHomeHandlerTest.php` (new): `kpis.outstandingAmount`/`outstandingDiscount` reflect net on a discounted unpaid invoice (and are unaffected when there's no discount); `projects[].unbilled`/`projects[].discount` don't drift on a 100%-discounted invoice.

## Follow-up UX pass: gross/net dual visibility (chart, KPIs, aggregates)

The user came back after the first visual pass explicitly unsatisfied with how it was reached ("il y a eu implémentation sans qu'on puisse discuter de l'user experience"): variants had been picked from a pre-built menu rather than grounded in a real conversation about audience and intent. This follow-up pass was done via a structured interview (`grilling` skill) before touching any code. Key groundwork established:

- **Audience per surface**: `DashboardPage.vue` is 100% internal (no sharing concept). `ProjectBillingPage.vue` is dual-audience — it can be shared with the client (`is_shared`/`shared_by`/`token`), and the client explicitly wants to see the discount, not have it hidden: transparency is the deliberate choice on both internal and shared views, and on the PDF export too.
- **Both amounts, no interaction, percentage secondary**: re-examining why "Variant B" (struck-through gross + net) was preferred over the badge (Variant A) — the real reason is wanting *both* the gross and net amounts visible at a glance without hovering/clicking; the discount percentage matters less and stays a small secondary detail. This principle, previously applied only per-invoice, now **also applies to aggregates** (project "Facturé" total, "À payer" card, dashboard "Factures impayées" KPI, PDF footer "Montant facturé"): each now shows gross (muted, struck-through) *and* net (primary) explicitly, instead of one total + a muted delta line requiring mental subtraction. The delta line is kept underneath for both.
- **Chart gets two lines, not one**: the dashboard's cumulative € line had drifted, across earlier sessions, from gross "Facturé" → net "Perçu" (renamed) using invoice *issue* month. The user wants **both** lines back, each with a different temporal semantic:
  - **"Facturé"**: gross amount, cumulative, grouped by invoice **issue month** (`created_at`) — invoicing activity.
  - **"Perçu"**: net amount, cumulative, grouped by **payment month** (`paid_at`), not issue month — real cash timing. This is a deliberate semantic change (previously grouped by `created_at` too), and the resulting lag between the two lines is the intended value: it makes payment delay visible, not just the discount amount.
  - Same two-line treatment applied to `ProjectBillingPage.vue`'s own per-client chart (previously only had the net line).
- **KPI naming/triad conflict**: the dashboard's "Revenus"/"Sur la période" KPIs are computed from `TimesheetEntry` (work done × daily rate) — a **third, purely theoretical** notion, decoupled from invoicing/payment entirely. Once real "Facturé"/"Perçu" figures also appear on the page, keeping this figure labeled "Revenus" risked implying it was real money. Resolved by restructuring the KPI grid from 4 cards to 3:
  1. **"Travaillé en {mois}"** (was "Jours en {mois}" + "Revenus en {mois}", merged): days worked + theoretical € equivalent + projection — this is what the user uses to anticipate monthly earnings from work done, decoupled from billing status.
  2. **"Factures impayées"** — unchanged in scope, now shows gross+net explicitly (see above).
  3. **"Sur la période"** (enriched): kept days/TJ moyen/CA théorique moyen (still wanted, still meaningful as a pace indicator), **added** explicit "Facturé" (gross) and "Perçu" (net) over the selected period — the actual answer to "how much real CA have I made."
  - Occupation %/trend indicators were explicitly deprioritized by the user ("m'intéressent moins, mais pourquoi pas") on both surviving cards — kept, but demoted to a small secondary line rather than a prominent element. The standalone daily "trend vs previous day-count" (`kpis.trendDays`) was dropped entirely (redundant with the revenue trend once the two cards merged), not just visually demoted.

**Backend changes**: `ShowHomeHandler.php` — `chart.invoiced` added (gross by `created_at`), `chart.net` semantics changed (net by `paid_at`, was by `created_at`), dead `chart.discount` removed (never read by the frontend). New `kpis.periodInvoiced`/`kpis.periodPaid` (gross/net over the selected `[rollingYearStart, windowEnd]` range, mirroring the chart split). `kpis.trendDays`/`monthDays`-trend computation removed (unused once the KPI cards merged).

**Frontend changes**: `DashboardPage.vue` (chart datasets, KPI grid, tooltip breakdown merged into one `monthBreakdown`), `ProjectBillingPage.vue` (chart gets a gross "Facturé" line back + local per-client "Perçu" computed from `paid_at` instead of the month bucket; `chartMonths` extended to include payment months not otherwise present; aggregates show gross+net explicitly), `resources/views/exports/billing.blade.php` (footer "Montant facturé" line shows gross struck-through + net, mirroring the per-invoice pattern already used there).

- [x] Backend: `chart.invoiced` (gross by `created_at`), `chart.net` recomputed by `paid_at`, `chart.discount` removed.
- [x] Backend: `kpis.periodInvoiced`/`kpis.periodPaid` for the enriched period KPI card.
- [x] Backend: `ShowHomeHandlerTest` updated/extended for the new chart and KPI semantics.
- [x] Frontend: `DashboardPage.vue` chart — two lines ("Facturé"/"Perçu"), tooltip callbacks updated.
- [x] Frontend: `DashboardPage.vue` KPI grid — 4 cards → 3 ("Travaillé en {mois}" merged, "Factures impayées" unchanged, "Sur la période" enriched); occupation%/trend de-emphasized; `trendDays` removed.
- [x] Frontend: `DashboardPage.vue` "Factures impayées" — gross+net shown explicitly.
- [x] Frontend: `ProjectBillingPage.vue` chart — "Facturé" gross line restored, "Perçu" net line now grouped by `paid_at`, axis extended for payment months.
- [x] Frontend: `ProjectBillingPage.vue` aggregates ("Facturé" tfoot total, "À payer" card) — gross+net shown explicitly.
- [x] `billing.blade.php` PDF footer "Montant facturé" — gross+net shown explicitly.

## Risks / things to watch

- **Backward compatibility**: existing invoices get `discount_amount = 0` via the column default — every calculation (`to_invoice`, `to_pay`, dashboard `unbilled`/`outstandingAmount`) stays byte-for-byte identical for them. No manual data migration needed.
- **`lte:amount` validation** structurally prevents a negative `net_amount` — no extra defensive check needed in `netAmount()`.
- **`to_pay` / dashboard `outstandingAmount` semantics change** (net instead of gross) — deliberate and correct, but it's the one existing numeric output that changes for *future* discounted invoices; verify explicitly in tests that non-discounted invoices are unaffected.
- **`buildProjectBillingEntryWithTotals()` (unbounded history) vs `buildBillingExportViewData()` (bounded `[from, to]`)** both reuse `computeTotals()` but over different month subsets — check in tests that totals reconcile when the PDF period covers the project's full history.
- **Dashboard stays a 4th, separate implementation** (`ShowHomeHandler.php` doesn't use the trait) — out of scope to merge here, but must receive the same gross/net correction by hand, or it'll silently regress relative to the Billing page.
- **Scope boundary**: this plan covers the technical/data layer only. Visual integration of discount badges is deliberately deferred to the dedicated UX/UI study above — don't freelance a design ad hoc while doing the data plumbing.
