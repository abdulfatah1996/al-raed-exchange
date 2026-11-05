<?php

namespace App\Models;

use App\Support\Permissions;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use SensitiveParameter;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'email',
        'password',
        'locale',
        'is_active',
    ];

    /** Mirrors the column default so new, unsaved models are active too. */
    protected $attributes = ['is_active' => true];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
        'app_authentication_secret',
        'app_authentication_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'app_authentication_secret' => 'encrypted',
            'app_authentication_recovery_codes' => 'encrypted:array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $user) {
            if ($user->isDirty('is_active') && ! $user->is_active && $user->isLastActiveAdmin()) {
                throw new \RuntimeException(__('admin.users.last_admin'));
            }
        });
    }

    /** True when this account is the only active administrator left. */
    public function isLastActiveAdmin(): bool
    {
        return $this->hasRole(Permissions::ROLE_ADMIN)
            && ! static::query()->role(Permissions::ROLE_ADMIN)->where('is_active', true)->whereKeyNot($this->getKey())->exists();
    }

    public function hasAppAuthentication(): bool
    {
        return filled($this->app_authentication_secret);
    }

    /** Only active staff with the panel permission may enter — in every environment. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->can(Permissions::PANEL_ACCESS);
    }

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->app_authentication_secret;
    }

    public function saveAppAuthenticationSecret(#[SensitiveParameter] ?string $secret): void
    {
        $this->forceFill(['app_authentication_secret' => $secret])->save();
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->app_authentication_recovery_codes;
    }

    public function saveAppAuthenticationRecoveryCodes(#[SensitiveParameter] ?array $codes): void
    {
        $this->forceFill(['app_authentication_recovery_codes' => $codes])->save();
    }
}
