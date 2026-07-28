<?php

namespace App\Models;

use App\Contracts\HasUser;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $user_id
 * @property string $client_id
 * @property string $token
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Client|null $client
 * @property-read User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedShare newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedShare newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedShare query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedShare whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedShare whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedShare whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedShare whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedShare whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedShare whereUserId($value)
 *
 * @mixin \Eloquent
 */
class SavedShare extends Model implements HasUser
{
    protected $table = 'shares';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function isValid(): bool
    {
        return $this->client?->share_token === $this->token;
    }
}
