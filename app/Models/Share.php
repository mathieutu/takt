<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Share extends Model
{
    public $incrementing = false;
    public $keyType = 'string';

    const UPDATED_AT = null;

    protected $fillable = ['id', 'share_type', 'share_id'];

    public function sharing(): MorphTo
    {
        return $this->morphTo();
    }
}
