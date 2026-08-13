---
paths:
  - app/Providers/AppServiceProvider.php
---

# Providers

## Strict Eloquent mode enabled
`Model::shouldBeStrict()` is enabled globally: lazy loading, silently discarded attribute fills, and missing attribute access all throw. Write queries/relations accordingly (eager-load what you access).
