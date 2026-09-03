# `TimesheetEntry.coverage` stores a percentage of a workday, not hours worked

## Context and Problem Statement

A timesheet entry needs to record how much of a given day was worked, in a way that composes
directly with a project's billing rate. How should that quantity be represented?

## Decision Drivers

* This app's billing model is per-day (a daily rate / TJM — "tarif journalier moyen"), not hourly.
* There is no universal length for "a full workday": it varies per freelancer, with no natural
  default to convert a number of hours into a fraction of a day.

## Considered Options

* Store hours worked, and convert to a fraction of a day using a configurable "hours per day" value
* Store `coverage` as a percentage of a workday (`0`-`100`), with no notion of hours at all

## Decision Outcome

Chosen option: "Percentage of a workday", because it composes directly with `daily_rate`
(`coverage / 100 * daily_rate`) without ever needing to define what a full day means in hours — a
value with no sensible universal default, that would need configuring (per user? per project?) for
no benefit to a billing model that was never hourly to begin with.

### Consequences

* Good, because half-days and any other fraction are representable naturally (`0`-`100`), with no
  hours-per-day setting needed anywhere in the app.
* Bad, because the app cannot report "hours logged" — only a fraction of a day; a freelancer who
  wants an hours breakdown must derive it themselves outside the app.
