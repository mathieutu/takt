<?php

namespace App\Models;

use App\Contracts\HasUser;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as BaseCollection;
use Znck\Eloquent\Relations\BelongsToThrough;

/**
 * @property string $id
 * @property string $name
 * @property string $client_id
 * @property string|null $description
 * @property CarbonImmutable|null $end_date
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property int|null $max_total_budget
 * @property CarbonImmutable $start_date
 * @property BaseCollection<array-key, mixed> $daily_rates
 * @property BaseCollection<array-key, mixed>|null $monthly_budgets
 * @property-read Client|null $client
 * @property int $daily_rate
 * @property-read Collection<int, Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property int|null $max_month_budget
 * @property-read Collection<int, TimesheetEntry> $timesheetEntries
 * @property-read int|null $timesheet_entries_count
 * @property-read User|null $user
 *
 * @method static Builder<static>|Project active(\Carbon\CarbonInterface|string|null $date = null)
 * @method static \Database\Factories\ProjectFactory factory($count = null, $state = [])
 * @method static Builder<static>|Project inactive(\Carbon\CarbonInterface|string|null $date = null)
 * @method static Builder<static>|Project newModelQuery()
 * @method static Builder<static>|Project newQuery()
 * @method static Builder<static>|Project orderedByEndDateThenName()
 * @method static Builder<static>|Project query()
 * @method static Builder<static>|Project whereClientId($value)
 * @method static Builder<static>|Project whereCreatedAt($value)
 * @method static Builder<static>|Project whereDailyRates($value)
 * @method static Builder<static>|Project whereDescription($value)
 * @method static Builder<static>|Project whereEndDate($value)
 * @method static Builder<static>|Project whereId($value)
 * @method static Builder<static>|Project whereMaxTotalBudget($value)
 * @method static Builder<static>|Project whereMonthlyBudgets($value)
 * @method static Builder<static>|Project whereName($value)
 * @method static Builder<static>|Project whereStartDate($value)
 * @method static Builder<static>|Project whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Project extends Model implements HasUser
{
    protected $with = ['client'];

    protected function casts(): array
    {
        return [
            'daily_rates' => 'collection',
            'monthly_budgets' => 'collection',
            'max_total_budget' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * Virtual, current-value accessor for `daily_rates`. The setter always dates the new entry to
     * today — for a change effective on a chosen date (past or future), use `setDailyRateFrom()`
     * instead (see ProjectController::update).
     */
    protected function dailyRate(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getDailyRateForDate(today()),
            // Compared against historicalValueAt(), not the daily_rate getter: the latter falls back to
            // the earliest known rate (or 0) when history is empty, which would wrongly look like a
            // match — and silently skip setting daily_rates — for a brand new project.
            set: fn (int $value) => $this->historicalValueAt($this->daily_rates ?? collect(), today()) === $value ? [] : [
                'daily_rates' => ($this->daily_rates ?? collect())->merge([today()->toDateString() => $value]),
            ],
        );
    }

    /**
     * Virtual, current-value accessor for `monthly_budgets`. Same today-only scope as `dailyRate()`.
     */
    protected function maxMonthBudget(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getMonthlyBudgetForDate(today()),
            // See dailyRate() for why this is checked against historicalValueAt(), not the getter.
            set: fn (?int $value) => $this->historicalValueAt($this->monthly_budgets ?? collect(), today()) === $value ? [] : [
                'monthly_budgets' => ($this->monthly_budgets ?? collect())->merge([today()->toDateString() => $value]),
            ],
        );
    }

    /**
     * Records a new daily rate effective from a chosen date (past or future), a no-op if an actual
     * entry already governs that date with the same value.
     *
     * Deliberately checked against `historicalValueAt()`, not `getDailyRateForDate()`: the latter
     * falls back to the earliest known rate for a date older than the whole history, which would
     * wrongly look like a match (and silently drop the entry) when backdating a rate change earlier
     * than everything currently on record — that's new information even if it happens to share the
     * previously-earliest entry's value.
     */
    public function setDailyRateFrom(int $value, CarbonInterface|string $effectiveFrom): void
    {
        $effectiveFrom = CarbonImmutable::parse($effectiveFrom)->toDateString();
        $history = $this->daily_rates ?? collect();

        if ($this->historicalValueAt($history, $effectiveFrom) === $value) {
            return;
        }

        $this->daily_rates = $this->pruneRedundantEntries($history->merge([$effectiveFrom => $value]));
    }

    /**
     * Records a new monthly budget cap effective from a chosen date, a no-op if an actual entry
     * already governs that date with the same value (see `setDailyRateFrom()` for why this is checked
     * against `historicalValueAt()` rather than `getMonthlyBudgetForDate()`). `0` is normalized to
     * `null` — unlike a daily rate, a budget of exactly 0 is never meaningful on its own, so it's
     * treated as "unlimited" instead.
     */
    public function setMonthlyBudgetFrom(?int $value, CarbonInterface|string $effectiveFrom): void
    {
        $value = $value === 0 ? null : $value;
        $effectiveFrom = CarbonImmutable::parse($effectiveFrom)->toDateString();
        $history = $this->monthly_budgets ?? collect();

        if ($this->historicalValueAt($history, $effectiveFrom) === $value) {
            return;
        }

        $pruned = $this->pruneRedundantEntries($history->merge([$effectiveFrom => $value]));

        // A single entry that's unlimited carries no information worth keeping as a dated history —
        // it's the same as never having had a budget, so collapse it to a plain absence of one.
        $this->monthly_budgets = $pruned->count() === 1 && $pruned->first() === null ? null : $pruned;
    }

    /**
     * Drops entries that don't change anything compared to the one right before them chronologically
     * — inserting a value can make a later, already-existing entry redundant (same value, so it no
     * longer marks an actual change), and a redundant entry left in the history is just noise.
     *
     * @param  BaseCollection<string, int|null>  $history
     * @return BaseCollection<string, int|null>
     */
    private function pruneRedundantEntries(BaseCollection $history): BaseCollection
    {
        return $history->sortKeys()->reduce(
            fn (BaseCollection $pruned, $value, $date) => $pruned->isNotEmpty() && $pruned->last() === $value
                ? $pruned
                : $pruned->put($date, $value),
            collect(),
        );
    }

    public function getDailyRateForDate(CarbonInterface|string $date): int
    {
        $history = $this->daily_rates ?? collect();

        // A project always needs a rate, so a date older than the whole history (e.g. a timesheet
        // entry logged before any rate was ever recorded) still gets one — the earliest known rate,
        // rather than silently billing those days at 0.
        return $this->historicalValueAt($history, $date) ?? $history->sortKeys()->first() ?? 0;
    }

    public function getMonthlyBudgetForDate(CarbonInterface|string $date): ?int
    {
        // Unlike the daily rate, "no budget yet" is a meaningful state (see theoreticalBudgetThrough):
        // a date before the budget was ever introduced stays uncapped, it doesn't inherit a later one.
        return $this->historicalValueAt($this->monthly_budgets ?? collect(), $date);
    }

    /**
     * Finds the value effective on a given date in a `{effective date => value}` history: the
     * latest entry whose date is on or before it, or `null` if every entry postdates it.
     *
     * @param  BaseCollection<string, int|null>  $history
     */
    private function historicalValueAt(BaseCollection $history, CarbonInterface|string $date): ?int
    {
        $dateStr = CarbonImmutable::parse($date)->toDateString();

        return $history->sortKeys()
            ->filter(fn ($value, $effectiveFrom) => $effectiveFrom <= $dateStr)
            ->last();
    }

    /**
     * Cumulative budget allowance from the project's start through a given month: `max_total_budget`
     * when set (a flat cap, not monthly), otherwise the sum of the monthly budget effective on each
     * calendar month in range — so a budget increase only grows the allowance from the month it was
     * introduced, and months before any budget existed contribute nothing.
     */
    public function theoreticalBudgetThrough(CarbonInterface $projectStart, CarbonInterface $through): ?int
    {
        if ($this->max_total_budget !== null) {
            return $this->max_total_budget;
        }

        if (($this->monthly_budgets ?? collect())->isEmpty()) {
            return null;
        }

        $months = CarbonPeriod::create($projectStart->startOfMonth(), '1 month', $through->startOfMonth());

        return collect($months)->sum(fn ($month) => $this->getMonthlyBudgetForDate(CarbonImmutable::parse($month)) ?? 0);
    }

    public function scopeActive(Builder $query, CarbonInterface|string|null $date = null): void
    {
        $date = CarbonImmutable::parse($date ?? today())->startOfDay();

        $query->where('start_date', '<=', $date)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $date));
    }

    public function scopeInactive(Builder $query, CarbonInterface|string|null $date = null): void
    {
        $date = CarbonImmutable::parse($date ?? today())->startOfDay();

        $query->where(fn ($q) => $q
            ->where('start_date', '>', $date)
            ->orWhere(fn ($q) => $q->whereNotNull('end_date')->where('end_date', '<', $date))
        );
    }

    public function isInactive(): bool
    {
        return ! $this->isActiveOn(today());
    }

    /**
     * Date since which the project has had an uninterrupted, currently-still-open debt (worked minus
     * invoiced kept strictly positive) — the last time the running balance crossed from ≤0 up to >0.
     * Null when there's currently nothing owed, even if there was in the past.
     */
    public function unbilledSince(): ?CarbonImmutable
    {
        $workEvents = $this->timesheetEntries
            ->where('billable', true)
            ->map(fn (TimesheetEntry $e) => [
                'date' => CarbonImmutable::parse($e->date),
                'order' => 0, // same-day: work lands before that day's invoices
                'delta' => (int) round($e->coverage / 100 * $this->getDailyRateForDate($e->date)),
            ])
            ->toBase();

        $invoiceEvents = $this->invoices->map(fn (Invoice $i) => [
            'date' => CarbonImmutable::parse($i->created_at)->startOfDay(),
            'order' => 1,
            'delta' => -$i->amount,
        ])->toBase();

        $run = $workEvents->merge($invoiceEvents)
            ->sortBy([['date', 'asc'], ['order', 'asc']])
            ->reduce(function (array $run, array $event) {
                $wasPositive = $run['balance'] > 0;
                $balance = $run['balance'] + $event['delta'];

                return [
                    'balance' => $balance,
                    'since' => match (true) {
                        $balance > 0 && ! $wasPositive => $event['date'],
                        $balance <= 0 => null,
                        default => $run['since'],
                    },
                ];
            }, ['balance' => 0, 'since' => null]);

        return $run['since'];
    }

    /**
     * Ended projects first (earliest end_date first), then ongoing ones (no end_date), each group
     * tie-broken alphabetically — used everywhere a client's projects are listed for billing.
     * `end_date IS NULL` evaluates to 0/1 in Postgres, MySQL, and SQLite alike, giving a portable
     * "NULLS LAST" without a driver-specific clause.
     */
    public function scopeOrderedByEndDateThenName(Builder $query): void
    {
        $query->orderByRaw('end_date is null')->orderBy('end_date')->orderBy('name');
    }

    public function isActiveOn(CarbonInterface|string $date): bool
    {
        $date = CarbonImmutable::parse($date)->startOfDay();

        return $this->start_date->lte($date) && ($this->end_date === null || $this->end_date->gte($date));
    }

    public function user(): BelongsToThrough
    {
        return $this->belongsToThrough(User::class, Client::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function timesheetEntries(): HasMany
    {
        return $this->hasMany(TimesheetEntry::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
