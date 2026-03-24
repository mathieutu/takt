<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityTime extends Model
{
    protected $fillable = ['project_id', 'label', 'start_date', 'comments', 'day_coverage'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
