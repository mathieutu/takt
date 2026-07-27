# Saving a shared link requires an explicit click, not just a page view

`SavedShare` gates authorization: `Project::isImportable()` only lists clients the current user
has a valid `SavedShare` for as eligible sources for a delegated project. We considered creating
a `SavedShare` automatically the moment an authenticated user opens a `shares/{token}` link, which
would have simplified the UI to a single state (no button, no click). We rejected it: it would
turn "I opened a link someone sent me to look at" into "I'm now authorized to source a delegated
project from this client," conflating passive viewing with a deliberate authorization decision.

Initial creation of a `SavedShare` therefore requires an explicit "Save" click. Once a
`SavedShare` already exists for a user/client pair, revisiting the link *does* silently resync its
stored token (e.g. after the owner regenerated `share_token`) — that resync carries no new
authorization implication, since consent was already given when the entry was first created.
