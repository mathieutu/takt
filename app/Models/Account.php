<?php

namespace App\Models;

use App\Enums\AccountType;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Str;

class Account extends Authenticatable implements FilamentUser, HasName
{
    public $incrementing = false;
    public $keyType = 'string';

    protected $fillable = ['type', 'email', 'password'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'type' => AccountType::class
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

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
    public function getFilamentName(): string
    {
        if ($this->type === AccountType::User && $user = $this->user) {
            return Str::ucfirst($user->first_name) . " " . Str::upper($user->last_name);
        } else if ($this->type === AccountType::Organization && $organization = $this->organization) {
            return Str::ucwords($organization->name);
        } else {
            return "Unknown";
        }
    }
}
