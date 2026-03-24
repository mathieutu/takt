<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Organization extends Model
{
    public $incrementing = false;
    public $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['id', 'name'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'id', 'id');
    }
}
