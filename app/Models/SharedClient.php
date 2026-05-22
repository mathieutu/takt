<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $client_id
 * @property string $account_id
 * @property Carbon $created_at
 * @property-read Account $account
 * @property-read Client $client
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SharedClient newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SharedClient newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SharedClient query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SharedClient whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SharedClient whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SharedClient whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SharedClient whereId($value)
 *
 * @mixin \Eloquent
 */
class SharedClient extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['client_id', 'account_id'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
