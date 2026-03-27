<?php

namespace App\Models;

use App\Enums\AccountType;
use Auth;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Str;
use function Pest\Laravel\instance;

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

    public static function authenticated(): ?Account
    {
        $auth = Auth::getUser();
        if (!$auth) {
            return null;
        }

        assert($auth instanceof Account, "Auth is not an Account");
        return $auth;
    }
}
