# MCP server over HTTP, authenticated with the existing API token, OAuth deferred

## Context and Problem Statement

An agent must be able to do what a user does in the UI (create clients and projects, configure them,
add timesheet entries and invoices) and export a billing recap. The app already had a per-user
`api_token` (`auth:api` guard, `routes/api.php`) but only one endpoint. We needed a transport, an
authentication scheme, and a way to share the write logic with the existing controllers.

## Decision Drivers

* No new dependency beyond `laravel/mcp`; Passport (tables, keys, consent page) is heavy for a self-hosted app.
* Claude Code and Claude Desktop (through `mcp-remote`) accept a static bearer header; claude.ai custom connectors only accept OAuth.
* Ownership is enforced by [ADR-0004](0004-resource-ownership-middleware-fails-closed.md) on route-bound models, which a JSON-RPC endpoint does not have.
* An agent that gets it wrong must stay recoverable.

## Considered Options

* HTTP MCP (`Mcp::web`) behind the existing `api_token`
* HTTP MCP behind OAuth (Passport, `Mcp::oauthRoutes()`)
* Local stdio MCP (`Mcp::local`)

## Decision Outcome

Chosen option: "HTTP MCP behind the existing `api_token`", because it works today with Claude Code and
Desktop at zero extra dependency. Authentication lives only in the `->middleware('auth:api')` call of
`routes/ai.php`, so OAuth can replace it later without touching the tools.

* Tools in `App\Mcp\Tools` resolve every id through `TaktTool::findClient()/findProject()/findInvoice()`, scoped to the authenticated user's own records (the middleware of ADR-0004 cannot run without route parameters). A miss is an `AuthorizationException` so its message reaches the agent.
* Write logic shared with the UI is extracted into `App\Actions` (`CreateProject`, `UpdateProject`, `UpdateClient`, `UpsertEntries`, `SaveInvoice`), which validate their own input; controllers and tools both call them.
* No deletion, public share or profile tool is exposed. `UpsertEntries` still deletes a day when given an empty entry, as `syncEntries` always did. It accepts `dry_run` and is capped at `UpsertEntries::MAX_ENTRIES` entries per call, and writes atomically.
* `billing_recap` returns the period data as JSON plus a `pdf_url`: a 10-minute signed route (`exports.billing.signed`, `SignedBillingExportHandler`) that needs no session. The signature is only issued after the ownership check, and the route keeps the `throttle:10,1` of the other exports.

### Consequences

* Good, because Claude Code and Desktop work immediately, and the agent can import months of entries (for example from git history) in one validated, previewable call.
* Good, because the UI and the agent cannot drift apart: both go through the same Actions.
* Bad, because claude.ai custom connectors do not work until OAuth is added.
* Bad, because the `api_token` is unscoped: a leaked token gives the agent's full write access (regenerate it from the profile page).
* Bad, because anyone holding a `pdf_url` can download that PDF until it expires.
