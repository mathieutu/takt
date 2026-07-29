# Daily rate and monthly budget are dated histories, not single values

`Project.daily_rates` and `Project.monthly_budgets` are `{effective_date => value}` maps (JSON
columns cast to `Collection`), not single columns. Every place that bills work — the dashboard,
timesheet, billing page, and PDF export — resolves the value effective on the specific date being
billed (`getDailyRateForDate($date)`/`getMonthlyBudgetForDate($date)`: the latest entry on or
before that date), instead of reading one current value. Changing a project's rate or budget today
therefore only affects work billed from today onward; every past month keeps billing at whatever
rate was in force when it was worked, permanently.

A date older than the entire `daily_rates` history still resolves to a value — the earliest known
rate — since a project always needs a rate to bill against (e.g. a timesheet entry logged before
any rate was ever recorded). `monthly_budgets` doesn't do this: a date before the budget history
began stays uncapped (`null`), since "no budget yet" is a meaningful state, distinct from
retroactively applying the earliest budget (see `theoreticalBudgetThrough()`).
