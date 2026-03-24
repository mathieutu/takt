<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Account extends Authenticatable
{
    public $incrementing = false;
    public $keyType = 'string';

    protected $fillable = ['type', 'email', 'password'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'id');
    }

    public function organization(): HasOne
    {
        return $this->hasOne(Organization::class, 'id', 'id');
    }

    public function sharedProjects(): HasMany
    {
        return $this->hasMany(SharedProject::class);
    }

    public function sharedClients(): HasMany
    {
        return $this->hasMany(SharedClient::class);
    }
}
