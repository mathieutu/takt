---
name: typescript-best-practices
description: "Apply this skill when writing, reviewing, or refactoring TypeScript or Vue <script setup> code in this project. Covers coding style, function declarations, control flow, iteration patterns, type definitions, imports, and code structure. Activate for any .ts or .vue file modification."
---

# TypeScript Best Practices

Rules derived from `eslint.config.mjs` and project conventions. ESLint enforces most of these — follow them proactively.

## Functions

**Always use arrow functions.** `func-style` and `prefer-arrow-callback` are both errors.

```ts
// ✅
const formatDate = (date: string): string => new Date(date).toLocaleDateString()
const double = (n: number) => n * 2
const items = list.map(item => item.name)

// ❌
function formatDate(date: string): string { ... }
list.map(function(item) { return item.name })
```

**Single-parameter arrows omit parens.** (`style/arrow-parens: as-needed`)

```ts
// ✅
const double = n => n * 2
arr.filter(x => x > 0)

// ❌
const double = (n) => n * 2
arr.filter((x) => x > 0)
```

## Control Flow — No `else`, Use Early Returns

Replace `if/else` with guard clauses. Exit early, keep the happy path at the bottom with minimal nesting.

```ts
// ✅
const getLabel = (value: number): string => {
  if (value < 0) return 'negative'
  if (value === 0) return 'zero'
  return 'positive'
}

// ❌
const getLabel = (value: number): string => {
  if (value < 0) {
    return 'negative'
  } else if (value === 0) {
    return 'zero'
  } else {
    return 'positive'
  }
}
```

In event handlers and watchers, guard at the top:

```ts
// ✅
watch(() => page.flash, flash => {
  if (!flash) return
  // process flash...
})

// ❌
watch(() => page.flash, flash => {
  if (flash) {
    // process flash...
  }
})
```

## Iteration — No `for` Loops

Use native array methods. `for`, `for...of`, and `while` should only appear when there is genuinely no functional alternative (e.g., async iteration with `for await...of`).

| Need | Use |
|------|-----|
| Transform each element | `map` |
| Keep matching elements | `filter` |
| Find one element | `find` / `findIndex` |
| Check all/any | `every` / `some` |
| Flatten + map | `flatMap` |
| Accumulate a value | `reduce` |
| Side effects only | `forEach` (prefer restructuring to avoid it) |
| Fixed-size array | `Array.from({ length: n }, (_, i) => ...)` |
| Object entries | `Object.entries(obj).map(...)` / `Object.fromEntries(...)` |

```ts
// ✅
const names = users.map(u => u.name)
const actives = users.filter(u => u.active)
const total = amounts.reduce((sum, n) => sum + n, 0)
const days = Array.from({ length: count }, (_, i) => i + 1)

// ❌
const names: string[] = []
for (const u of users) {
  names.push(u.name)
}
```

## Nesting — Keep It Flat

Maximum 2 levels of nesting in a function body. If logic goes deeper, extract a named arrow function.

```ts
// ✅
const isAvailable = (slot: Slot): boolean => slot.active && !slot.booked

const availableSlots = schedule.flatMap(day =>
  day.slots.filter(isAvailable)
)

// ❌
const availableSlots = []
for (const day of schedule) {
  for (const slot of day.slots) {
    if (slot.active) {
      if (!slot.booked) {
        availableSlots.push(slot)
      }
    }
  }
}
```

Compose small, named functions instead of deeply nested inline logic.

## Types

**Use `type`, never `interface`.** (`ts/consistent-type-definitions: type`)

```ts
// ✅
type User = {
  id: number,
  name: string,
  role: 'admin' | 'member',
}

// ❌
interface User {
  id: number;
  name: string;
}
```

**Type member delimiter is `,` not `;`.** (`style/member-delimiter-style: comma`)

```ts
// ✅
type Point = { x: number, y: number }
type Config = {
  host: string,
  port: number,
}

// ❌
type Point = { x: number; y: number }
```

## Imports

**Type-only imports use top-level `import type`.** (`import/consistent-type-specifier-style: prefer-top-level`)

```ts
// ✅
import type { User } from '@/types'
import { ref } from 'vue'

// ❌
import { type User } from '@/types'
```

## Immutable Array/Object Methods (ES2023+)

Prefer the non-mutating counterparts — they return a new array, no spread needed.

| Mutating (avoid) | Non-mutating (prefer) |
|---|---|
| `arr.sort(fn)` | `arr.toSorted(fn)` |
| `arr.reverse()` | `arr.toReversed()` |
| `arr.splice(i, n, ...items)` | `arr.toSpliced(i, n, ...items)` |
| `arr[i] = v` | `arr.with(i, v)` |

```ts
// ✅
const sorted = items.toSorted((a, b) => a.name.localeCompare(b.name))
const reversed = items.toReversed()
const updated = items.with(2, newItem)

// ❌
const sorted = [...items].sort((a, b) => a.name.localeCompare(b.name))
const reversed = [...items].reverse()
const updated = [...items]; updated[2] = newItem
```

Also prefer modern lookup methods:

```ts
// ✅
const last = arr.at(-1)
const lastMatch = arr.findLast(x => x.active)
const lastIndex = arr.findLastIndex(x => x.active)

// ❌
const last = arr[arr.length - 1]
```

## Constants

Always `const`, never `let` for values that don't change.

## Naming

- Boolean variables: `isLoading`, `hasError`, `canSubmit` — not `loading`, `error`, `submit`.
- Event handlers: `handleClick`, `handleSubmit` — not `onClick`, `click`.
- Descriptive names: `isRegisteredForDiscounts`, not `discount()`.

## After Every Change

Always run both commands after modifying any `.ts` or `.vue` file, and fix any errors before finishing:

```bash
yarn lint:fix
yarn typecheck
```
