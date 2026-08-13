---
paths:
  - 'app/Actions/**'
---

# Actions

## Actions/Services are invokable, container-resolved
Give single-purpose action classes an `__invoke()` method and resolve them via constructor/method type-hint injection on controllers and commands, never `new Xxx()`.
