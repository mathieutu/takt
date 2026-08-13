---
paths:
  - 'app/Http/**'
---

# Http

## Controller-first business logic
Keep query/aggregation/calculation logic directly in the controller (private methods) unless it is infrastructural (external API, PDF, file generation) or genuinely reused verbatim across multiple controllers. Cross-controller reuse goes into an `app/Http/Concerns` trait, not a Service/Action.

## Authorization via middleware, not Policies/Gates
Do not introduce Policies, Gates, or `$this->authorize()`/`->can()` calls. Rely on the `EnsureUserOwnsResource` middleware for ownership checks on route-bound models, and use `abort_if()`/`abort_unless()` inline for one-off authorization edge cases.
