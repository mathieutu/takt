# `Project.daily_rate`/`max_month_budget` stay as today-snapshot accessors, scoped to display and input

Alongside the dated histories (`daily_rates`, `monthly_budgets`, see ADR-0002), `Project` keeps
virtual `daily_rate`/`max_month_budget` accessors that read/write today's value
(`getDailyRateForDate(today())` under the hood). Their scope is deliberately narrow: display of the
project's current rate (project lists, the billing page header, budget-to-days hints for
forward-looking figures like remaining budget), and input (the create form, factories, demo
seeding, where "today" is the only sensible effective date). No billing calculation reads from
them — every money computation goes through `getDailyRateForDate($date)`/`getMonthlyBudgetForDate($date)`
with the date actually being billed.

`Client.daily_rate` is unrelated: it's a plain, non-historized column (a client-level suggested
rate offered when creating a new project), not the same concept under the same name.
