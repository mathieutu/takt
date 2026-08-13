---
paths:
  - 'routes/*.php'
---

# Routes

## Middleware via route groups
Assign middleware in `routes/web.php`/`routes/api.php` via `Route::middleware()` groups and per-route `->middleware()`, not via controller `HasMiddleware` or `#[Middleware]` attributes.
