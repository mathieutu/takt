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
*emits*; `SavedShare` is what the *recipient* stores after receiving it. Always created by an
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
