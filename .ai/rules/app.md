---
paths:
  - 'app/**'
---

# App

## Dependency injection, not service location
Type-hint dependencies via constructor or method injection. Reserve `app()`/`resolve()` for framework runtime checks (`isProduction()`, `runningUnitTests()`), not for resolving services.

## Prefer collection pipelines over foreach for transforms
Transform/filter/pluck data with fluent collection chains (`->map()`, `->filter()`, `->pluck()`). Reserve `foreach` for side-effecting iteration (e.g. persisting each validated entry), and avoid raw `array_map`/`array_filter`.

## Use static Str:: helpers, not Str::of()
Call `Str::` static methods (`Str::uuid()`, `Str::slug()`, `Str::random()`) directly; don't use the fluent `Str::of()` builder.

## now()/today() helpers with CarbonImmutable
Get current dates via `now()`/`today()`, never `Carbon::now()`. The app is configured (`Date::use(CarbonImmutable::class)`) so these return `CarbonImmutable` — type-hint accordingly instead of mutable `Carbon`.
