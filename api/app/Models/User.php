<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Staff and partners sign in to the Filament panels with a password; travelers sign in on the site with
 * Google and use Sanctum tokens kept in an httpOnly cookie by the Next.js BFF.
 */
#[Fillable(['name', 'email', 'password', 'role', 'operator_id', 'telegram_chat_id', 'google_id', 'avatar_url', 'last_login_at'])]
#[Hidden(['password', 'remember_token', 'google_id'])]
class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->role?->isStaff() ?? false,
            // A partner without an operator would see nothing; the super admin links them first.
            'partner' => $this->role === UserRole::Partner && $this->operator_id !== null,
            default => false,
        };
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url;
    }

    public function isStaff(): bool
    {
        return $this->role?->isStaff() ?? false;
    }

    /** The partner's company. */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** Tours the traveler saved ("favorites" on the site). */
    public function savedTours(): BelongsToMany
    {
        return $this->belongsToMany(Tour::class, 'saved_tours')->withTimestamps();
    }
}
