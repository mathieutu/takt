---
paths:
  - 'app/Http/Concerns/**'
---

# Concerns

## Shape Inertia props via Model::export()
Use the model's `->export([...])` method (mathieutu/exporter) to shape data passed to `Inertia::render()` from shared Concerns traits, not API Resources.
