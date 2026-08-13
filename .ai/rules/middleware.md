---
paths:
  - 'app/Http/Middleware/**'
---

# Middleware

## Route model binding + ownership middleware
Ownership checks for route-bound models live in `EnsureUserOwnsResource`, keyed on models implementing `App\Contracts\HasUser`, not scattered across controllers.
