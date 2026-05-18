<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $account_id
 * @property int $project_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\Account $account
 * @property-read \App\Models\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SharedProject newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SharedProject newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SharedProject query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SharedProject whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SharedProject whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SharedProject whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SharedProject whereProjectId($value)
 * @mixin \Eloquent
 */
class SharedProject extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['account_id', 'project_id'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
