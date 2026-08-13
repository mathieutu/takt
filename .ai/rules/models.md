---
paths:
  - 'app/Models/**'
---

# Models

## Mass assignment via guarded=[]
Leave models fully mass-assignable (`protected $guarded = []`, inherited from the base `Model` class). Do not add `$fillable` allow-lists.

## UUID primary and foreign keys
Use `HasUuids` on models. Primary keys are UUIDs, not auto-increment.

## Local scope methods, no repository layer
Query Eloquent models directly from controllers/services; push reusable query logic into local `scopeXxx(Builder $query, ...): void` methods on the model, not `#[Scope]` attributes, repository classes, or query-object classes.
