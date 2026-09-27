<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property bool $active
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'manager_id',
        'role',
        'name',
        'cpf',
        'email',
        'email_verified_at',
        'password',
        'active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'role' => UserRole::class,
        'password' => 'hashed',
        'active' => 'boolean',
    ];

    public function isManager(): bool
    {
        return $this->role === UserRole::MANAGER;
    }

    public function isAnalyst(): bool
    {
        return $this->role === UserRole::ANALYST;
    }

    public function isClient(): bool
    {
        return $this->role === UserRole::CLIENT;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function analysts(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id')->where('role', UserRole::ANALYST->value);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function clients(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id')->where('role', UserRole::CLIENT->value);
    }
}
