<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $project_id
 * @property string|null $label
 * @property Carbon $start_date
 * @property string|null $comments
 * @property int $day_coverage
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Project $project
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityTime newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityTime newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityTime query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityTime whereComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityTime whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityTime whereDayCoverage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityTime whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityTime whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityTime whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityTime whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityTime whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ActivityTime extends Model
{
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'day_coverage' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
