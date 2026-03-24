<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Client extends Model
{
    protected $fillable = ['name', 'daily_rate', 'user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(SharedClient::class);
    }

    public function shared(): MorphOne
    {
        return $this->morphOne(Share::class, 'sharing');
    }
}
