<?php

namespace App\Services\Accounts;

use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Traveler sign-in: finds or creates the account, links earlier bookings, issues the site's token. */
final class TravelerAccounts
{
    public const TOKEN_DAYS = 30;

    /**
     * @param  string|null  $googleId  null for the local test sign-in
     * @return array{0: User, 1: string} the user and a plain Sanctum token for the site's cookie
     */
    public function signIn(string $email, string $name, ?string $googleId = null, ?string $avatarUrl = null): array
    {
        $email = Str::lower(trim($email));

        $user = DB::transaction(function () use ($email, $name, $googleId, $avatarUrl) {
            $user = ($googleId ? User::where('google_id', $googleId)->first() : null)
                ?? User::whereRaw('lower(email) = ?', [$email])->first();

            // Team and partner accounts use the password panels; a Google sign-in must not open a session for them.
            if ($user && $user->role !== UserRole::Tourist) {
                throw ValidationException::withMessages([
                    'email' => 'This email belongs to a team or partner account. Please use another Google account.',
                ]);
            }

            $user ??= new User(['role' => UserRole::Tourist]);
            $user->fill([
                'name' => $user->name ?: $name,
                'email' => $email,
                'google_id' => $googleId ?? $user->google_id,
                'avatar_url' => $avatarUrl ?? $user->avatar_url,
                'last_login_at' => now(),
            ]);
            // Google verified the address; earlier bookings with it are this traveler's.
            $user->forceFill(['role' => UserRole::Tourist, 'email_verified_at' => $user->email_verified_at ?? now()])->save();

            Booking::query()->whereNull('user_id')->whereRaw('lower(email) = ?', [$email])->update(['user_id' => $user->id]);

            return $user;
        });

        $token = $user->createToken('site', ['traveler'], now()->addDays(self::TOKEN_DAYS))->plainTextToken;

        return [$user, $token];
    }
}
