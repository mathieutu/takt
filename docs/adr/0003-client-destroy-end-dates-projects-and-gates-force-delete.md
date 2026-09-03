# Deleting a client end-dates its projects, and permanent deletion requires none left

## Context and Problem Statement

`ClientController::destroy()` needs to decide what happens to a client's projects when the client is
deleted, and under what condition a client can ever be purged for good.

## Decision Drivers

* An archived client's projects must not keep showing up as "active" (dashboard, project lists).
* Permanently purging a client's data (invoices, timesheet history) is destructive and should not
  happen as an unintended side effect of a single click.

## Considered Options

* A plain `$client->delete()`, leaving its projects untouched
* End-date every still-open project before soft-deleting the client, and only allow permanent
  deletion (`forceDelete()`) once the client has no projects left

## Decision Outcome

Chosen option: "End-date projects, gate permanent deletion", implemented in
`ClientController::destroy()`: it sets `end_date = today()` on every project of the client that
doesn't already have one, then soft-deletes the client. `forceDelete()` is only allowed once
`$client->projects()->exists()` is false — checked explicitly by the controller (not surfaced as a
database FK error), with a distinct error message from the success case.

### Consequences

* Good, because `Project::scopeActive()` no longer shows a project as active once its client is
  gone.
* Good, because a user must delete every project one by one before a client's data can be purged for
  good, rather than a single destructive action cascading through invoices and timesheet history.
* Bad, because fully purging a client now takes several explicit steps instead of one.
