<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use MathieuTu\Exporter\Exporter;

abstract class Model extends \Illuminate\Database\Eloquent\Model
{
    use Exporter;
    use HasUuids;

    protected $guarded = [];
}
