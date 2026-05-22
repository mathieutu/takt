<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property int $client_id
 * @property int|null $daily_rate
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property numeric|null $max_budget
 * @property-read Collection<int, ActivityTime> $activityTimes
 * @property-read int|null $activity_times_count
 * @property-read Collection<int, BillingEntry> $billingEntries
 * @property-read int|null $billing_entries_count
 * @property-read Client $client
 * @property-read Collection<int, SharedProject> $shared
 * @property-read int|null $shared_count
 * @property-read Collection<int, Account> $sharedTo
 * @property-read int|null $shared_to_count
 * @property-read Share|null $sharer
 * @property-read Collection<int, View> $vues
 * @property-read int|null $vues_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDailyRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereMaxBudget($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Project extends Model
{
    protected $fillable = ['name', 'client_id', 'daily_rate', 'description', 'max_budget'];

    protected function casts(): array
    {
        return [
            'daily_rate' => 'integer',
            'max_budget' => 'decimal:2',
        ];
    }

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

    public function shared(): HasMany
    {
        return $this->hasMany(SharedProject::class);
    }

    public function sharedTo(): HasManyThrough
    {
        return $this->hasManyThrough(Account::class, SharedProject::class, 'project_id', 'id', 'id', 'account_id');
    }

    public function billingEntries(): HasMany
    {
        return $this->hasMany(BillingEntry::class);
    }

    public function sharer(): MorphOne
    {
        return $this->morphOne(Share::class, 'sharing', type: 'share_type', id: 'share_id');
    }

    public function share(): Share
    {
        return $this->sharer()->firstOrCreate([]);
    }
}
