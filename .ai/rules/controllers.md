---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Validate inline, Form Request only when shared
Validate inline with `$request->validate()` for single-use rules. Extract a Form Request only when the same rule set is shared by more than one controller action (e.g. `ExportBillingRequest`).

## Single-action controllers: __invoke + Handler suffix
For single-action controllers, use `__invoke()` and suffix the class name `...Handler` (e.g. `ShowHomeHandler`) instead of `...Controller`. Reserve plain multi-method `...Controller` classes for resourceful/multi-action endpoints.

## Route model binding + ownership middleware
Use implicit type-hinted route model binding for resource routes. Enforce per-user ownership centrally via the `EnsureUserOwnsResource` middleware (for models implementing `App\Contracts\HasUser`) rather than manual ownership checks inside each controller action.

## Shape Inertia props via Model::export()
Use the model's `->export(['field', 'relation.field as alias'])` method (mathieutu/exporter) to shape data passed to `Inertia::render()`. Don't create API Resource classes or hand-roll `->toArray()`/`->only()`.

## Use route() for internal URLs
Generate internal URLs/redirects with `route('name', [...])`. Reserve `url()` for non-navigational absolute strings only (e.g. embedding a base URL in a payload).

## updateOrCreate() for idempotent writes
Use `updateOrCreate()` for idempotent create-or-update writes rather than a manual find-then-save.
