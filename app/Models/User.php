<?php

namespace App\Models;

use App\Enums\Role;
use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'phone', 'password', 'city', 'country', 'locale', 'status', 'invited_at', 'internal_notes', 'courier_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'invited_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
        $this->trustedDevices()->delete();
    }

    public function reactivate(): void
    {
        $this->update(['status' => 'active']);
    }

    /** Réinitialise la 2FA : oublie la confirmation et révoque les appareils de confiance. */
    public function resetTwoFactor(): void
    {
        $this->two_factor_confirmed_at = null;
        $this->save();
        $this->trustedDevices()->delete();
    }

    /** Garde-fou : empêche de retirer/suspendre le dernier super administrateur actif. */
    public function isLastSuperAdmin(): bool
    {
        if (! $this->hasRole(Role::SuperAdmin->value)) {
            return false;
        }

        return self::role(Role::SuperAdmin->value)->where('status', 'active')->count() <= 1;
    }

    /** A-t-il au moins un rôle de gestion (back-office) ? */
    public function isStaff(): bool
    {
        return $this->hasAnyRole(array_map(fn (Role $r) => $r->value, Role::management()));
    }

    public function isLivreur(): bool
    {
        return $this->hasRole(Role::Livreur->value);
    }

    /** Route d'accueil après connexion selon le rôle. */
    public function homeRoute(): string
    {
        if ($this->isLivreur()) {
            return '/livreur';
        }

        return $this->isStaff() ? '/admin' : '/app';
    }

    /** @return BelongsTo<Courier, $this> */
    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    /** @return HasMany<Delivery, $this> */
    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'livreur_id');
    }

    /** @return HasMany<TrustedDevice, $this> */
    public function trustedDevices(): HasMany
    {
        return $this->hasMany(TrustedDevice::class);
    }

    /** @return HasMany<LoginLog, $this> */
    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    /** @return HasMany<Parcel, $this> */
    public function parcels(): HasMany
    {
        return $this->hasMany(Parcel::class, 'sender_id');
    }

    /** @return HasMany<ParcelRequest, $this> */
    public function parcelRequests(): HasMany
    {
        return $this->hasMany(ParcelRequest::class);
    }

    /** @return HasMany<Review, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
