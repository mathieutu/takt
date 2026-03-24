<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Project extends Model
{
    protected $fillable = ['name', 'client_id', 'daily_rate', 'description'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function activityTimes(): HasMany
    {
        return $this->hasMany(ActivityTime::class);
    }

    public function vues(): HasMany
    {
        return $this->hasMany(View::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(SharedProject::class);
    }

    public function shared(): MorphOne
    {
        return $this->morphOne(Share::class, 'sharing');
    }
}
