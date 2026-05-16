<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Concerns\HasUuid;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuid, Notifiable;

    protected $fillable = [
        'email',
        'password_hash',
        'first_name',
        'last_name',
        'role',
        'qr_code',
        'is_active',
        'suspension_until',
        'email_verified_at',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'suspension_until' => 'datetime',
            'is_active' => 'boolean',
            'role' => UserRole::class,
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function refreshTokens(): HasMany
    {
        return $this->hasMany(RefreshToken::class);
    }

    public function isStaff(): bool
    {
        return in_array($this->role?->value ?? $this->role, [UserRole::Librarian->value, UserRole::Admin->value], true);
    }
}
