<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $id
 * @property string $share_type
 * @property int $share_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read Model|\Eloquent $sharing
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Share newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Share newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Share query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Share whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Share whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Share whereShareId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Share whereShareType($value)
 * @mixin \Eloquent
 */
class Share extends Model
{
    public $incrementing = false;
    public $keyType = 'string';

    const UPDATED_AT = null;

    protected $fillable = ['id', 'share_type', 'share_id'];

    protected $casts = [
        'id' => 'uuid'
    ];

    public function sharing(): MorphTo
    {
        return $this->morphTo();
    }

    public function url()
    {
        return route('share.apply', [
            'share' => $this
        ]);
    }
}
