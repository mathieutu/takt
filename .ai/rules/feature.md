---
paths:
  - 'tests/Feature/**'
---

# Feature

## Use factories exclusively for test fixtures
Build all test-owned records via `Model::factory()->create()`/`->for()` chains; never `Model::create([...])` or manual inserts in tests, and don't rely on `$this->seed()`.
