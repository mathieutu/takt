# Saving a shared link requires an explicit click, not just a page view

## Context and Problem Statement

`SavedShare` gates authorization: `Project::isImportable()` only lists clients the current user has
a valid `SavedShare` for as eligible sources for a delegated project. When should a `SavedShare` be
created — the moment an authenticated user opens a `shares/{token}` link, or only through a
deliberate action?

## Decision Drivers

* Opening a link someone sent "to look at" must never, by itself, grant delegation-source
  authorization over that client.
* A single-state UI (no button, no click) would be simpler.

## Considered Options

* Auto-create a `SavedShare` the moment an authenticated user opens the link
* Require an explicit "Save" click to create a `SavedShare`

## Decision Outcome

Chosen option: "Require an explicit click", because auto-creating on view would turn "I opened a
link someone sent me to look at" into "I'm now authorized to source a delegated project from this
client," conflating passive viewing with a deliberate authorization decision.

### Consequences

* Good, because authorization always traces back to a deliberate user action.
* Neutral, because once a `SavedShare` already exists for a user/client pair, revisiting the link
  still silently resyncs its stored token (e.g. after the owner regenerated `share_token`) — that
  resync carries no new authorization implication, since consent was already given when the entry
  was first created.
