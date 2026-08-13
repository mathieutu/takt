---
paths:
  - 'tests/Feature/Http/**'
---

# Feature Http

## Assert Inertia responses with fluent closures
Use `->assertInertia(fn ($page) => ...)` with `expect()` on extracted props rather than raw `assertJson`/`assertJsonFragment` — this app doesn't expose a JSON API.
