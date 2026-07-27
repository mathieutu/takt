<?php

namespace App\Models;

use App\Contracts\HasUser;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Znck\Eloquent\Relations\BelongsToThrough;

/**
 * @property string $id
 * @property string $name
 * @property string $client_id
 * @property int $daily_rate
 * @property int|null $max_month_budget
 * @property int|null $max_total_budget
 * @property string|null $description
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable|null $end_date
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Client|null $client
 * @property-read Collection<int, Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read Collection<int, TimesheetEntry> $timesheetEntries
 * @property-read int|null $timesheet_entries_count
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDailyRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereMaxMonthBudget($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Project extends Model implements HasUser
{
    protected $with = ['client'];

    protected function casts(): array
    {
        return [
            'daily_rate' => 'integer',
            'max_month_budget' => 'integer',
            'max_total_budget' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
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
