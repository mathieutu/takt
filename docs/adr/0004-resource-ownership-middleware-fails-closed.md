# `EnsureUserOwnsResource` denies access unless every route-bound model proves ownership

## Context and Problem Statement

`EnsureUserOwnsResource` runs on every authenticated route to check that the current user owns the
resources it operates on. A route can bind more than one Eloquent model (e.g. a nested resource).
Should the middleware check only the route's primary model, or every bound parameter?

## Decision Drivers

* A route with a second, unchecked bound parameter belonging to a different account must not slip
  through.
* New models should be safe to add to a route-model-binding signature by default.

## Considered Options

* Check only the primary/main route-bound model
* Inspect every resolved route parameter and deny if any one fails

## Decision Outcome

Chosen option: "Check every resolved route parameter", implemented as
`collect($request->route()->parameters())->contains(fn ($model) => ! $model instanceof HasUser || ! $model->user->is($request->user()))`,
throwing `AccessDeniedHttpException` as soon as any one parameter either doesn't implement the
`HasUser` contract or doesn't belong to the authenticated user.

### Consequences

* Good, because this is fail-closed by default: any new Eloquent model added to a
  route-model-binding signature must explicitly implement `HasUser`, or every request to that route
  is rejected.
* Good, because a model that "forgot" to implement `HasUser` fails loudly and immediately, rather
  than opening a cross-tenant leak.
* Good, because this multi-tenant isolation guarantee is exercised directly by feature tests added
  in `62932cb`.
