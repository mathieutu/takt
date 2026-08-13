---
paths:
  - 'tests/Unit/**'
---

# Unit

## Use factories exclusively for test fixtures
Build all test-owned records via `Model::factory()->create()`/`->for()` chains; never `Model::create([...])` or manual inserts in tests.
