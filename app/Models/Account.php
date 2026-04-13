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

/**
 * @property string $id
 * @property AccountType $type
 * @property string $email
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Organization|null $organization
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SharedClient> $sharedClients
 * @property-read int|null $shared_clients_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SharedProject> $sharedProjects
 * @property-read int|null $shared_projects_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
