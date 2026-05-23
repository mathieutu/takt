<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $client_id
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
 * @property-read Share|null $sharer
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
