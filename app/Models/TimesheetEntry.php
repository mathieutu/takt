<?php

namespace App\Models;

use App\Contracts\HasUser;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Znck\Eloquent\Relations\BelongsToThrough;

/**
 * @property string $id
 * @property string $project_id
 * @property CarbonImmutable $date
 * @property int $coverage
 * @property bool $billable
 * @property string|null $title
 * @property string|null $description
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Project|null $project
 * @property-read User|null $user
 *
 * @method static Builder<static>|TimesheetEntry inMonth(\Carbon\CarbonInterface $date)
 * @method static Builder<static>|TimesheetEntry newModelQuery()
 * @method static Builder<static>|TimesheetEntry newQuery()
 * @method static Builder<static>|TimesheetEntry query()
 * @method static Builder<static>|TimesheetEntry whereBillable($value)
 * @method static Builder<static>|TimesheetEntry whereCoverage($value)
 * @method static Builder<static>|TimesheetEntry whereCreatedAt($value)
 * @method static Builder<static>|TimesheetEntry whereDate($value)
 * @method static Builder<static>|TimesheetEntry whereDescription($value)
 * @method static Builder<static>|TimesheetEntry whereId($value)
 * @method static Builder<static>|TimesheetEntry whereProjectId($value)
 * @method static Builder<static>|TimesheetEntry whereTitle($value)
 * @method static Builder<static>|TimesheetEntry whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class TimesheetEntry extends Model implements HasUser
{
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'coverage' => 'integer',
            'billable' => 'boolean',
        ];
    }

    /**
     * @param  Collection<int, TimesheetEntry>  $entries
     */
    public static function billableCoverageSum(Collection $entries): int
    {
        return $entries->where('billable', true)->sum('coverage');
    }

    public function user(): BelongsToThrough
    {
        return $this->belongsToThrough(User::class, [Project::class, Client::class])
            ->withTrashed(['clients.deleted_at']);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    protected function scopeInMonth(Builder $query, CarbonInterface $date): Builder
    {
        return $query
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month);
    }
}
