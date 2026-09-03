# Takt

Freelance time-tracking and billing app (clients, projects, timesheets, invoices).

## Language

**Share** (emission):
Not a model — the state of a `Client` whose `share_token` is non-null. A client is "shared" when
this token exists, "revoked" when it goes back to `null`. See `docs/models.md` → `Client`.
_Avoid_: Favorite (for emission)

**SavedShare** (reception):
A user's record of another account's `Client.share_token`, kept for later reuse (e.g. as the
source of a delegated project). Distinct from emission: `Client.share_token` is what an owner
_emits_; `SavedShare` is what the _recipient_ stores after receiving it. Always created by an
explicit action (a click); an existing entry silently resyncs on every revisit of the link
(viewing alone never creates an entry — only an explicit click does). Table `shares`, route
prefix `shares/*` (route naming stays generic; the class name stays precise to avoid conflating
"emitting a share" with "saving a received share").
_Avoid_: Favorite, ReceivedShare

**Valid / invalid share**:
A `SavedShare` is valid when its captured `token` still matches the client's current
`share_token` (`SavedShare::isValid()`). Becomes invalid on revocation, token regeneration, or
client deletion (even soft-delete) — all treated identically in the UI ("invalid link"), with no
distinction by cause. An invalid `SavedShare` is excluded from the delegated-project creation
picker but stays visible (and removable) in "Received shares".

**Subcontracted project**:
A subcontractor's own `Project`, created from a delegating freelance's invitation link (with a
suggested name/`daily_rate`). Its `daily_rate` becomes the delegating freelance's cost basis
(`Project::costDailyRate()`) on the project billing the real end client; its timesheet entries are
imported — never edited directly — into that billing project. The billing project itself has no
special name of its own: it's an ordinary `Project` that happens to carry
`subcontracted_project_id`, `hasSubcontractedProject()`, `isImportable()`.
_Avoid_: source project, delegated project (ambiguous: reads as "the project handed to someone",
i.e. the subcontracted one, not the freelance's own billing project)

**Effective date** (daily rate / monthly budget):
The date from which a newly-submitted daily rate or monthly budget value applies going forward —
it never changes what was already billed before that date (see ADR-0009). On the project edit
form, value and effective date are independent: submitting a value without a date applies it from
today; submitting a date without a value backdates/postdates a value of `0` to that date; submitting
neither leaves the existing rate/budget completely untouched (so the rest of the project — e.g. its
name — can be saved without accidentally touching either history).

**Unlimited monthly budget**:
A monthly budget of exactly `0` is not a genuine zero-euro cap — it's normalized to "no cap"
(`null`). There is no way to record a project that may bill €0/month; `0` is reserved to mean
unlimited. This doesn't apply to the daily rate: `0` there is a valid, if unusual, rate (e.g. work
done for free from that date).
