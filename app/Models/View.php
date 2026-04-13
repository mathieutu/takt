<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $title
 * @property int $project_id
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property string|null $comments
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder<static>|View newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|View newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|View query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|View whereComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|View whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|View whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|View whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|View whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|View whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|View whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|View whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class View extends Model
{
    protected $table = 'views';

    protected $fillable = ['title', 'project_id', 'start_date', 'end_date', 'comments'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
