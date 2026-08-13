---
paths:
  - 'app/Services/**'
---

# Services

## Actions/Services are invokable, container-resolved
Resolve Services via constructor/method type-hint injection on controllers and commands, never `new Xxx()`.

## No repository/query-object layer
Query Eloquent models directly rather than through repository or query-object classes; push reusable query logic into model scopes instead.
