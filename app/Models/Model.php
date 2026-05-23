<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

abstract class Model extends \Illuminate\Database\Eloquent\Model
{
    use HasUuids;

    protected $guarded = [];
}
