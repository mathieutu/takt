<?php

namespace App\Models\Concerns;

use App\Models\Share;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait Shareable
{
    public function sharer(): MorphOne
    {
        return $this->morphOne(Share::class, 'shareable');
    }

    public function share(): Share
    {
        return $this->sharer()->firstOrCreate([]);
    }
}
