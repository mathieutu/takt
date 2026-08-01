<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * @property string $id
 * @property string $project_id
 * @property CarbonImmutable $date
 * @property int $coverage
 * @property string|null $title
 * @property string|null $description
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property bool $billable
 * @property-read Project $project
 *
 * @method static \Database\Factories\TimesheetEntryFactory factory($count = null, $state = [])
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
class TimesheetEntry extends Model
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

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
