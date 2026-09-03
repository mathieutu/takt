# Project ordering uses a portable `end_date is null` expression instead of driver-specific `NULLS LAST`

## Context and Problem Statement

`Project::scopeOrderedByEndDateThenName()` needs ended projects first (earliest `end_date` first),
then ongoing ones (`end_date IS NULL`) last — a "NULLS LAST" ordering. This project runs on
PostgreSQL in production and SQLite in tests/local dev (and previously supported MySQL). How should
this ordering be expressed so it behaves the same on every driver?

## Decision Drivers

* A driver-specific clause would break silently depending on which database ran the query — this
  class of driver mismatch already caused a real bug elsewhere in this codebase (a filter that never
  matched under Postgres-incompatible drivers, fixed in `1693430`).

## Considered Options

* A driver-specific `NULLS LAST` clause (native on PostgreSQL)
* A portable boolean expression: `orderByRaw('end_date is null')` before `end_date`/`name`

## Decision Outcome

Chosen option: "Portable boolean expression", because `end_date IS NULL` evaluates to a portable
`0`/`1` on Postgres, MySQL, and SQLite alike, giving the same "NULLS LAST" result everywhere without
a driver-specific clause.

### Consequences

* Good, because ordering behaves identically regardless of which database driver runs the query.
