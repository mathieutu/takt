---
paths:
  - 'database/migrations/**'
---

# Migrations

## UUID primary and foreign keys
Use `$table->uuid('id')->primary()` for primary keys and `$table->foreignUuid('x_id')->constrained('table')` (with explicit `cascadeOnDelete()`/`restrictOnDelete()` and `cascadeOnUpdate()`) for foreign keys, not auto-increment `id()`/`foreignId()`/`foreignIdFor()` or manual `foreign()->references()->on()`.

## Real down() migrations
Always implement a genuine `down()` that reverses schema changes and any data transformation done in `up()` — don't omit it or leave it as a no-op.
