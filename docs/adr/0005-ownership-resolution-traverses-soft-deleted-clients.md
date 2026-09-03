# Ownership lookups traverse soft-deleted clients, they don't stop at them

## Context and Problem Statement

`Invoice::user()` and `Project::client()` need to resolve their owner through `Client`, and clients
are routinely soft-deleted while still holding historical projects and invoices (see
[ADR-0003](0003-client-destroy-end-dates-projects-and-gates-force-delete.md)). Should this
resolution stop at a soft-deleted client, or look through it?

## Decision Drivers

* `EnsureUserOwnsResource` (see [ADR-0004](0004-resource-ownership-middleware-fails-closed.md))
  authorizes every route-bound model via `HasUser::user()->is($request->user())` — a `null` owner
  is treated as belonging to no one.
* Archiving a client is a normal, frequent action and must not itself change who can access the
  projects and invoices that were attached to it.

## Considered Options

* Default relation behavior: resolving ownership through a soft-deleted client returns `null`
* Explicitly traverse trashed clients: `Project::client()` adds `withTrashed()`, `Invoice::user()`
  adds `withTrashed(['clients.deleted_at'])`

## Decision Outcome

Chosen option: "Explicitly traverse trashed clients", because otherwise resolving the owner of an
invoice or project whose client has since been archived would return `null`, and
`EnsureUserOwnsResource` would then lock the owner out of their own historical billing data the
moment a client is archived.

### Consequences

* Good, because archiving a client never itself changes who can see the projects and invoices that
  were attached to it.
