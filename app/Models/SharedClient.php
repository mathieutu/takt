<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $client_id
 * @property string $user_id
 * @property Carbon $created_at
 * @property-read User $user
 * @property-read Client $client
 *
 * @mixin \Eloquent
 */
class SharedClient extends Model
{
    const UPDATED_AT = null;

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
