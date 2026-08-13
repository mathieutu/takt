# Commit Message Convention

Every commit message in this project follows a [Gitmoji](https://gitmoji.dev) style:

```
<emoji> [Scope — ]Message
```

- **Emoji** — one Gitmoji summarizing the type of change, always first. Common ones used in this repo:
  - ✨ new feature
  - 🐛 bug fix
  - ♻️ refactor
  - ✅ tests
  - 🔥 remove code/files
  - 🗃️ database (migrations/schema)
  - 💄 UI/style
  - 🚸 UX improvement
  - ⚡️ performance
  - 📝 docs
  - ⬆️ upgrade dependencies
  - 🔨 dev tooling/scripts
  - 💚 fix CI/build
  - 🚑️ critical hotfix
  - ♿️ accessibility
  - 🌐 i18n
  - 🧑‍💻 developer experience
- **Scope** (optional) — the feature/module name (e.g. `Timesheet`, `Billing`, `Projects`), capitalized, followed by ` — `.
- **Message** — imperative mood, capitalized, always written in English.

Examples from history:
- `✨ Timesheet — allow coverage only between a project's start and end date`
- `🐛 Users — fix account deletion silently undoing itself`
- `✅ Add architecture and browser smoke tests, drop default Pest examples`
- `♻️ Billing — factor balance calculation into computeTotals()`

Always write commit messages following this convention.
