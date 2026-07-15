<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MathieuTu\Exporter\Exporter;
use Znck\Eloquent\Traits\BelongsToThrough;

abstract class Model extends \Illuminate\Database\Eloquent\Model
{
    use BelongsToThrough;
    use Exporter;
    use HasFactory;
    use HasUuids;

    protected $guarded = [];
}
