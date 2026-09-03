# Contributing

Thanks for considering contributing to Takt.

## Issues

Bug reports and questions are welcome as [issues](https://github.com/mathieutu/takt/issues). Please include enough context to reproduce the problem (steps, expected vs. actual behavior, relevant logs).

## Pull requests

- **Bug fixes, typos, small improvements:** feel free to open a PR directly.
- **New features or larger changes:** open an issue first to discuss the idea before writing code. This avoids wasted effort on a PR that doesn't fit the project's direction.

Before opening a PR:

- Follow the existing code conventions (check sibling files if unsure).
- Add or update tests for the behavior you're changing, and run the affected ones: `php artisan test` (there is no frontend test suite — Vitest/Jest is not set up on this project, so frontend changes rely on lint, typecheck, and manual verification).
- Run `vendor/bin/pint --dirty` for PHP formatting.
- Run `yarn lint:fix` and `yarn typecheck` for frontend changes.
- Keep the PR focused on a single concern.
- Write commit messages following the project's convention (see below).
- Keep the PR description clear about what changed and why, and link the discussion issue for any feature work.

### Commit message convention

Commits follow a [Gitmoji](https://gitmoji.dev) style: `<emoji> [Scope — ]Message`.

- **Emoji** first, summarizing the type of change (✨ feature, 🐛 fix, ♻️ refactor, ✅ tests, 🔥 removal, 🗃️ database, 💄 UI, 🚸 UX, ⚡️ perf, 📝 docs, ⬆️ deps, 🔨 tooling, 💚 CI/build, 🚑️ hotfix, ♿️ accessibility, 🌐 i18n, 🧑‍💻 DX, ...).
- **Scope** (optional): the feature/module name, capitalized, followed by ` — ` (e.g. `Timesheet —`).
- **Message**: imperative mood, capitalized, in English.

Example: `🐛 Users — fix account deletion silently undoing itself`

## AI-assisted contributions

Using AI coding agents to help write a contribution is fine. However, **you are responsible for what you submit**: you must have reviewed, understood, and tested the code yourself before opening the PR. It has to work, and you have to be able to explain and defend every part of it.

PRs that read as unreviewed agent output — untested, not understood by the submitter, or that the submitter can't explain — will be closed without further effort. Please show that your contribution is worth more than the $20/month subscription — and the liters of water — it took to generate it.

## License

By contributing, you agree that your contributions will be licensed under the project's [AGPL-3.0 license](LICENSE).
