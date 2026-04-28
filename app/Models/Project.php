<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @property int $id
 * @property string $name
 * @property int $client_id
 * @property int|null $daily_rate
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityTime> $activityTimes
 * @property-read int|null $activity_times_count
 * @property-read \App\Models\Client $client
 * @property-read \App\Models\Share|null $shared
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SharedProject> $shares
 * @property-read int|null $shares_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\View> $vues
 * @property-read int|null $vues_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDailyRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Project extends Model
{
    protected $fillable = ['name', 'client_id', 'daily_rate', 'description'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function activityTimes(): HasMany
    {
        return $this->hasMany(ActivityTime::class);
    }

    public function vues(): HasMany
    {
        return $this->hasMany(View::class);
    }

    public function shared()
    {
        return $this->hasMany(SharedProject::class);
    }

    public function sharedTo()
    {
        return $this->hasManyThrough(Account::class, SharedClient::class, localKey: 'project_id', secondKey: 'account_id');
    }

    public function share()
    {
        return $this->morphOne(Share::class, 'sharing')->firstOrCreate([
            'share' => $this
        ]);
    }
}
