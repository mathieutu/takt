<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class User extends Model
{
    public $incrementing = false;
    public $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['id', 'first_name', 'last_name'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'id', 'id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'user_id', 'id');
    }

    public function projects(): HasManyThrough
    {
        return $this->hasManyThrough(Project::class, Client::class, 'user_id', 'client_id');
    }
}
