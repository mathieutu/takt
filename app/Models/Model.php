<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use MathieuTu\Exporter\Exporter;
use Znck\Eloquent\Traits\BelongsToThrough;

abstract class Model extends \Illuminate\Database\Eloquent\Model
{
    use BelongsToThrough;
    use Exporter;
    use HasUuids;

    protected $guarded = [];
}
