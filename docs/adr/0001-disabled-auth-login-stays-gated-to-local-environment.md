# The `AUTH_ENABLED=false` login bypass stays gated to `app()->isLocal()`, not just the config flag

## Context and Problem Statement

When `AUTH_ENABLED=false`, the `login/disabled` route lets anyone log in as any existing user by
email alone — a deliberate convenience for local development. What should gate this route, so a
misconfiguration can't turn it into a production authentication bypass?

## Decision Drivers

* A single wrong environment variable (`AUTH_ENABLED=false` in staging/production) must not expose a
  password-less login for every account.
* The bypass should still be effortless in local development, where it's genuinely useful.

## Considered Options

* Gate purely on `config('auth.enabled')`
* Gate on `config('auth.enabled')` and, independently, on `app()->isLocal()`

## Decision Outcome

Chosen option: "Gate on both conditions", implemented as: the route is registered only when
`! config('auth.enabled')` (`routes/web.php`), and the controller action additionally calls
`abort_unless(app()->isLocal(), 403)` before doing anything else.

### Consequences

* Good, because a config mistake alone can't turn this into an authentication bypass outside local
  development — both the flag and the actual runtime environment must agree.
* Neutral, because there are now two independent checks to keep in mind when touching this code
  path.
