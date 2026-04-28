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
 * @property float $daily_rate
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project> $projects
 * @property-read int|null $projects_count
 * @property-read \App\Models\Share|null $shared
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SharedClient> $shares
 * @property-read int|null $shares_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereDailyRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereUserId($value)
 * @mixin \Eloquent
 */
class Client extends Model
{
    protected $fillable = ['name', 'daily_rate', 'user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function shared()
    {
        return $this->hasMany(SharedClient::class);
    }

    public function sharedTo()
    {
        return $this->hasManyThrough(Account::class, SharedClient::class, localKey: 'client_id', secondKey: 'account_id');
    }

    public function sharer()
    {
        return $this->morphOne(Share::class, 'sharing', 'share_type', 'share_id');
    }

    public function share()
    {
        return $this->sharer()->firstOrCreate([], ['id' => (string) \Str::uuid()]);
    }
}
