# Plan — Shares (formerly "favorites")

> Details the extraction and redesign of the "favorites" piece from `docs/plan/subcontracting-light.md`
> (section 2) into a standalone "Shares" feature. Replaces that document's section 2 (and cross-references
> in sections 0, 3, 4, 7, 8, 9) once implemented. Lives for the duration of the implementation, same as
> `docs/plan/subcontracting-light.md`. See `CONTEXT.md` for the `Share`/`SavedShare` glossary entries and
> `docs/adr/0001-explicit-consent-for-saved-shares.md` for the consent rationale.

---

## 0. Context and decisions

`subcontracting-light.md` planned a generic `Favorite` model: a user saves the `share_token`
received from another account as a personal bookmark, later used to source a delegated project
from one of that client's projects. This piece is independent from the rest of the subcontracting
work (import diff, `source_project_id` — sections 3-5 of `subcontracting-light.md`, unchanged
here) and was reworked before implementation:

| Topic | Decision |
|---|---|
| Name | "Favorites" is ambiguous (favorite *of what*?). Renamed **"Shares"**, aligned with the vocabulary already used around `share_token` / the "Share" modal on `ProjectPage.vue`. |
| Model | `Favorite` → **`SavedShare`** (table `shares`, route prefix `shares/*`). Model name stays precise (avoids conflating "emitting a share" with "saving a received share" — see `CONTEXT.md`); route prefix stays generic. |
| Page | A single **"Shares"** page, two sections: *Received shares* (what I've saved from others) and *My shares* (new — reverse direction: who saved my clients). |
| Entry point | No manual "paste a token" form. A "Save to my shares" button lives on the public shared page itself (`shares/{token}`), next to the existing "Shared by X" banner. |
| Consent | Creating a `SavedShare` always requires an explicit click — merely viewing the shared page never creates one, even when authenticated (see ADR 0001: `SavedShare` gates delegated-project creation eligibility, so passive viewing must not grant it). |
| Resync | Revisiting the page when a `SavedShare` already exists for that user/client silently resyncs its stored token (`updateOrCreate`) — no click needed, since consent was already given at creation time. This also fixes staleness after the owner regenerates their `share_token`. |
| Self-share | Blocked server-side: a user cannot save their own client's share link. |
| Soft-deleted client | Treated identically to a revoked/regenerated share — shown as "invalid link" everywhere, no special-cased filtering. |
| My shares — history | Full history, including entries that became invalid, each flagged individually. A client that's no longer shared at all (`share_token` back to `null`) stays listed as long as at least one `SavedShare` still references it (nothing to manage otherwise) — its followers are then shown as revoked rather than "pending resync", since there's no active link left to resync against. Only a client with `share_token` null **and** no `SavedShare` at all disappears from "My shares" entirely. |
| My shares — actions | Read-only. The owner can't remove someone else's `SavedShare` directly — only revoke/regenerate their own `share_token` via the existing share modal on `ProjectPage.vue` (unchanged). |
| Controllers | `ShowSharedHandler` and `ExportSharedBillingHandler` are folded into `ShareController` as `show`/`export`, alongside the authenticated `index`/`store`/`destroy` — one controller for the whole `Share` resource, public and authenticated actions alike. |
| Picker (delegated project creation) | Invalid `SavedShare` entries are filtered out entirely — only valid ones are selectable. |

Result: no `SubcontractingLink` model, no state machine, no dedicated authorization contract, no
6-action invitation controller, no dedicated invitation/acceptance screens (unchanged from
`subcontracting-light.md`). The only "business" pieces are: shares (generic, reusable, this doc),
and the import diff (kept, unchanged, see `subcontracting-light.md` §5).

---

## 1. Database

**Migration** `..._create_shares_table.php`:

```php
Schema::create('shares', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
    $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
    $table->uuid('token'); // the client's share_token at the time this row was (last) synced
    $table->unique(['user_id', 'client_id']);
    $table->timestamps();
});
```

A real FK on `client_id` (referential integrity, cascade on hard delete) rather than a bare
`token` — the captured `token` only serves to detect a later revocation/regeneration. Cascade only
fires on a real SQL delete, never on `Client`'s soft delete (see §2 for how that's handled).

---

## 2. Models

**`app/Models/SavedShare.php`**: `belongsTo(User)`, `belongsTo(Client)`, implements `HasUser`
(ownership = direct `user_id`, no `AccessibleByUser` needed).

```php
public function isValid(): bool
{
    return $this->client?->share_token === $this->token;
}
```

Null-safe on purpose: `Client` uses `SoftDeletes`, so `belongsTo(Client)`'s default global scope
excludes trashed clients — `$this->client` is `null` for a soft-deleted client, and `null !==
$this->token` (a uuid string), so `isValid()` correctly returns `false` without special-casing
the soft-delete case anywhere else.

**`app/Models/Client.php`** — new reverse relation, same style as the existing
`projects(): HasMany`:

```php
public function shares(): HasMany
{
    return $this->hasMany(SavedShare::class);
}
```

Add `@property-read Collection<int, SavedShare> $shares` to the docblock.

---

## 3. Controller and routes

**`app/Http/Controllers/ShareController.php`** — replaces `FavoriteController` (planned),
`ShowSharedHandler`, and `ExportSharedBillingHandler` (existing). Uses
`BuildsProjectBillingEntry` (moved from `ShowSharedHandler`).

```php
// Public — no auth. Ported from ShowSharedHandler, plus the current-viewer save state.
public function show(string $token, HolidayService $holidays): Response
{
    $client = Client::findByShareTokenOrFail($token);
    $projects = $client->projects()->orderedByEndDateThenName()->get();
    abort_if($projects->isEmpty(), 404);
    $projects->load(['timesheetEntries', 'invoices']);

    $viewer = auth()->user();
    $isOwner = $viewer?->id === $client->user_id;
    $existingSavedShare = $viewer && ! $isOwner
        ? $viewer->shares()->firstWhere('client_id', $client->id)
        : null;

    if ($existingSavedShare) {
        // Silent resync on every revisit — no click needed once consent was already given.
        $existingSavedShare->update(['token' => $client->share_token]);
    }

    $builtProjects = $projects->map(fn (Project $p) => $this->buildProjectBillingEntry($p, $client->name))->values();

    return Inertia::render('ProjectBillingPage', [
        'shared_by' => $client->user->name,
        'is_shared' => true,
        'projects' => $builtProjects,
        'holidays' => $this->buildHolidaysForPeriod($holidays, $builtProjects),
        'token' => $token,
        'is_owner' => $isOwner,
        'saved_share_id' => $existingSavedShare?->id,
    ]);
}

// Public, throttled — unchanged from ExportSharedBillingHandler, ported as-is.
public function export(string $token): Response { /* ... */ }

// Authenticated (auth + EnsureUserOwnsResource).
public function index(Request $request): Response
{
    $user = $request->user();

    return Inertia::render('SharesPage', [
        'received_shares' => $user->shares()
            ->with('client.user', 'client.projects:id,client_id,name,daily_rate')
            ->get()
            ->map->export([...]), // includes is_valid via isValid()

        'my_shares' => $user->clients()
            ->where(fn ($q) => $q->whereNotNull('share_token')->orWhereHas('shares'))
            ->with(['shares' => fn ($q) => $q->with('user:id,name')])
            ->get()
            ->map(fn (Client $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'is_shared' => $c->share_token !== null,
                'share_url' => $c->share_token ? route('shares.show', $c->share_token) : null,
                'followers' => $c->shares->map(fn (SavedShare $s) => [
                    'id' => $s->id,
                    'user_name' => $s->user->name,
                    'is_valid' => $s->isValid(),
                    'saved_at' => $s->created_at,
                ]),
            ]),
    ]);
}

public function store(Request $request): RedirectResponse
{
    $data = $request->validate(['token' => ['required', 'uuid']]);

    $client = Client::findByShareTokenOrFail($data['token']);

    abort_if($client->user_id === $request->user()->id, 403); // no self-saving your own share

    $request->user()->shares()->updateOrCreate(
        ['client_id' => $client->id],
        ['token' => $data['token']],
    );

    return back()->with('success', __('Share saved.'));
}

public function destroy(SavedShare $share): RedirectResponse
{
    $share->delete();

    return back()->with('success', __('Share removed.'));
}
```

`store` uses `updateOrCreate` (not `firstOrCreate`): re-clicking "Save" after the owner
regenerated their `share_token` must refresh the stored token, not leave a stale one behind —
`firstOrCreate` would silently no-op on an existing row.

**Routes** (`routes/web.php`):

```php
// Public, outside the auth group — unchanged URLs, now routed to ShareController.
Route::get('shares/{token}', [ShareController::class, 'show'])->name('shares.show');
Route::get('shares/{token}/export', [ShareController::class, 'export'])
    ->name('shares.billing.export')->middleware('throttle:10,1');

// Authenticated, same group as clients/projects.
Route::resource('shares', ShareController::class)->only(['index', 'store', 'destroy']);
```

No collision: the resource only registers `index`/`store`/`destroy` — no `GET shares/{share}`
(`show`) is added by `Route::resource`, so the public `GET shares/{token}` route above remains the
only route matching that pattern.

---

## 4. Authentication flow for the "Save" button (unauthenticated visitor)

On the public shared page, an unauthenticated visitor sees "Log in to save" instead of the save
button. It links to `login?redirect=<current shared-page URL>`.

- `AuthController::redirect()`: reads the `redirect` query param and, if present, sets
  `session(['url.intended' => $redirect])` before calling `Socialite::driver('github')->redirect()`.
- `AuthController::disabled()` (local/dev auth bypass): same treatment, for consistency between
  local and production behavior (both already end in `redirect()->intended('/')`).
- `AuthController::callback()`: unchanged — `redirect()->intended('/')` already picks up the
  stored intended URL.

This is manual plumbing rather than relying on the `auth` middleware's automatic intended-URL
capture, because:
- The shared page itself must stay public (no `auth` middleware on `show`), so there's nothing to
  intercept a mere page view.
- Wrapping the "Save" *action* behind `auth` middleware doesn't work either: `POST shares` behind
  `auth`, followed unauthenticated, does get its URL captured in `url.intended`, but the
  post-login `redirect()->intended('/')` always redirects via a plain 302 — which the browser
  always follows as a `GET`, discarding the original POST body/method. A 307/308 status on the
  *final* redirect can't fix this either: it would only preserve the method of the *current*
  request (the GET OAuth callback), not resurrect a POST body from an earlier, already-completed
  request several steps and an external OAuth round-trip ago.

---

## 5. Frontend

**`resources/js/pages/ProjectBillingPage.vue`** — extend the existing "Shared by X" banner
(`v-if="shared_by"`, ~line 486) with, for a non-owner viewer:
- `!auth.user`: "Log in to save" link → `login?redirect=<currentUrl>`.
- `auth.user && !is_owner && !saved_share_id`: "Save to my shares" button → `POST shares` with
  the current `token`.
- `auth.user && !is_owner && saved_share_id`: "Remove" button → `DELETE shares/{saved_share_id}`.
- `is_owner`: no button (unchanged from today).

**`resources/js/pages/SharesPage.vue`** (new, replaces the planned `FavoritesPage.vue`) — two
`UCard`s stacked, following `ProfilePage.vue`'s pattern (`#header` slot, `UInput readonly` + copy
button, `useConfirm()` for destructive actions):

1. **"Received shares"**: list of `received_shares` — client name, owner name, "active"/"invalid
   link" badge (covers revoked, regenerated, *and* soft-deleted-client cases identically, no
   distinction shown), delete button per row (`DELETE shares/{share}`). No add form — entries are
   only ever created from the shared page's "Save" button (§4-5).
2. **"My shares"** (new): for each entry in `my_shares`, a sub-header (client name + share URL +
   copy button, same pattern as `ProfilePage.vue`), then the list of `followers` — user name,
   "active" (`success` badge) vs "revoked" (`neutral`/`error` badge) per `is_valid`, `saved_at`
   date. Read-only, no actions. Empty state if `my_shares` is empty.

**`resources/js/components/AppHeader.vue`**: "Favorites" menu entry → "Shares", pointing to
`shares.index()`.

**`resources/js/pages/ProjectForm.vue`** — delegated-project source picker (see
`subcontracting-light.md` §4) reads `page.received_shares`, **filtered to `is_valid` entries
only** — an invalid share can't source a delegated project anyway (`isImportable()` would fail),
so it's excluded from the creation flow entirely rather than shown disabled. Cleanup of invalid
entries stays a "Received shares" page concern, not a creation-flow concern.

**Wayfinder**: imports move from `@/wayfinder/routes/favorites` to `@/wayfinder/routes/shares` in
any `.vue` file that references them (generated file; hand-written imports need updating).

---

## 6. Impact on `subcontracting-light.md`

Rename to apply wherever that document references `Favorite`:

| Old | New |
|---|---|
| `Favorite` (model) | `SavedShare` |
| `$user->favorites()` | `$user->shares()` |
| `Favorite::isValid()` | `SavedShare::isValid()` |
| `FavoriteControllerTest` | `ShareControllerTest` |

Specifically:

- **Section 3** (`Project::isImportable()`): `Favorite::isValid()` → `SavedShare::isValid()`;
  `$this->user->favorites()->where('client_id', ...)` → `$this->user->shares()->where('client_id', ...)`.
- **Section 4** (`source_project_id` validation rule): `$user->favorites()->pluck('client_id')` →
  `$user->shares()->pluck('client_id')`. Note the picker also gains the "filter invalid entries"
  behavior from §5 above.
- **Section 7** (demo data): "a `Favorite` for the demo account per subcontractor" → "a
  `SavedShare` for the demo account per subcontractor".
- **Section 9** (docs): `docs/models.md` gets a `SavedShare` entry (and `Client.shares()`)
  instead of `Favorite`.
- Sections 5-6 (import, billing/dashboard): no `Favorite` reference, unchanged.

---

## 7. Tests

**`ShareControllerTest`** (replaces `FavoriteControllerTest`):
- `store` with a valid/invalid token.
- `store` rejects saving a client the current user already owns (self-share guard).
- `store` is idempotent-and-refreshing: calling it twice with a different current token
  (`updateOrCreate`) updates the stored token rather than leaving the first one stale.
- `destroy`, scoped to the owning user only.
- `isValid()` after the source client's `share_token` is revoked, regenerated, and soft-deleted
  (all three end up `false`, none of them throws).
- `show` (public page) silently resyncs an existing `SavedShare`'s token on each authenticated
  visit, without requiring a click.
- `show` never creates a `SavedShare` on mere viewing — only `store` does.
- `index` → `my_shares` only includes clients with a non-null `share_token`, scoped to the
  authenticated user (another user's shared clients never leak in).
- `index` → `my_shares` followers include both valid and stale/invalid entries for the same
  client, each correctly flagged (not filtered).
- `index` → a client whose `share_token` was revoked drops out of `my_shares` entirely, even
  though its `SavedShare` rows still exist in the database.
- `index` → a client that was never shared (`share_token` null) never appears in `my_shares`.
- Redirect-after-login: visiting `login?redirect=<shares/{token} URL>` while logged out, then
  completing auth, lands back on the shared page (covers both the GitHub callback and the local
  `disabled()` bypass).

**`ProjectControllerTest`** / **`ImportSubcontractedEntriesControllerTest`** (in
`subcontracting-light.md`): only the `Favorite::isValid()` reference becomes
`SavedShare::isValid()`; add a case confirming the source-project picker excludes clients whose
only `SavedShare` is invalid.

---

## 8. Verification

- `php artisan test --compact --filter=Share`: all green.
- `php artisan test --compact` in full (regression on the public `shares/{token}` routes, now
  served by `ShareController` instead of the two standalone handlers).
- `vendor/bin/pint --dirty --format agent` after PHP changes.
- `yarn lint:fix && yarn typecheck` after `.vue`/`.ts` changes.
- Manual test (`composer run dev`): share a client from one demo account; open the link from a
  second demo account while logged out — confirm "Log in to save" redirects back to the same page
  post-login; click "Save to my shares"; confirm it now appears in "Received shares" and in the
  first account's "My shares"; regenerate the share token on the first account, revisit the link
  on the second — confirm the entry silently becomes valid again without a click; revoke the
  share entirely — confirm the client disappears from "My shares" and the entry shows "invalid
  link" in "Received shares"; confirm an invalid entry is absent from the delegated-project source
  picker.
