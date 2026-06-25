---
name: vuejs
description: "Vue.js conventions for this project. Activates when editing .vue files or TypeScript files in resources/js."
---

# Vue.js Guidelines

## Navigation Links

Never use `@click="router.visit(...)"` on a button when it's a navigation link. Always use `:href` instead — it enables right-click, open in new tab, and correct semantic HTML.

```vue
<!-- ✅ Correct -->
<UButton :href="dashboard({ query: { from, to } })" />

<!-- ✅ With preserveScroll -->
<UButton :href="dashboard({ query: { from, to } })" preserveScroll />

<!-- ❌ Wrong -->
<UButton @click="router.visit(dashboard({ query: { from, to } }), { preserveScroll: true })" />
```

Reserve `router.visit()` for programmatic actions where a URL cannot be passed as a prop (e.g. after a form submission, after a picker selection).

## Variable Naming

Never use single or double-character variable names. Every variable must be named so its meaning is immediately clear without context.

```ts
// ✅ Correct
const currentYear = now.getFullYear()
const currentMonth = now.getMonth() + 1
const [fromYear, fromMonth] = props.from.split('-').map(Number)

// ❌ Wrong
const cy = now.getFullYear()
const cm = now.getMonth() + 1
const [fy, fm] = props.from.split('-').map(Number)
```
