<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $share_type
 * @property int $share_id
 * @property Carbon $created_at
 * @property-read Model|\Eloquent $sharing
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Share newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Share newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Share query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Share whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Share whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Share whereShareId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Share whereShareType($value)
 *
 * @mixin \Eloquent
 */
class Share extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = ['share_type', 'share_id'];

    public function sharing(): MorphTo
    {
        return $this->morphTo(type: 'share_type', id: 'share_id');
    }

    public function url(): string
    {
        return route('share.apply', ['share' => $this]);
    }
}
