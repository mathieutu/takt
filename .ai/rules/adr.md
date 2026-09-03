# Architecture Decision Records

`docs/adr/` records non-obvious design decisions — ones that could reasonably have been made
otherwise, where the code, a commit, or a test reveals a deliberate choice and the reasoning behind
it. It is not a place for general documentation of what the code does (that belongs in
`docs/architecture.md`, `docs/models.md`, `docs/features.md`) — only for the *why*, when the *why*
isn't obvious from reading the code alone.

## Format: MADR

Every ADR follows the [MADR](https://adr.github.io/madr/) template:

```markdown
# {Short title stating the decision taken}

## Context and Problem Statement

{What situation/question forced a choice.}

## Decision Drivers

* {Constraint or goal that shaped the decision}

## Considered Options

* {Option A}
* {Option B}

## Decision Outcome

Chosen option: "{Option}", because {reasoning}.

### Consequences

* Good, because {benefit}.
* Bad, because {trade-off}.
```

- The title states the decision taken (e.g. "Daily rate and monthly budget are dated histories, not
  single values"), not an open question.
- Cite the exact class/method (and, when one exists, the commit or test that proves the decision was
  deliberate — e.g. a bug fix commit hash) rather than describing the code in the abstract.
- Cross-reference related ADRs with a relative Markdown link, e.g.
  `[ADR-0009](0009-rate-and-budget-history-as-dated-maps.md)`.
- Keep `Decision Drivers`/`Considered Options` short — a few bullets, not prose essays. `Consequences`
  can include both `Good` and `Bad`/`Neutral` entries; a decision with no real trade-off is usually
  not worth an ADR.

## Numbering

Files are named `NNNN-kebab-case-title.md`, a zero-padded four-digit sequence starting at `0001`,
never reused or renumbered — a superseded decision gets a new ADR that says so, the old file stays.
