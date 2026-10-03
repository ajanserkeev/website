<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\MeResource;
use App\Services\Accounts\TravelerAccounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * Traveler sign-in for the site's BFF. The site redirects to Google with its own state cookie and sends us
 * the code from the callback; the client secret stays here.
 */
class AuthController extends Controller
{
    public function __construct(private readonly TravelerAccounts $accounts) {}

    /** Which buttons the login page shows. */
    public function providers(): JsonResponse
    {
        return response()->json(['data' => [
            'google' => filled(config('services.google.client_id')) && filled(config('services.google.client_secret')),
            'dev' => self::devLoginEnabled(),
        ]]);
    }

    public function googleUrl(Request $request): JsonResponse
    {
        $data = $request->validate(['state' => ['required', 'string', 'min:20', 'max:100']]);
        abort_unless(filled(config('services.google.client_id')), 404);

        $url = Socialite::driver('google')->stateless()
            ->with(['state' => $data['state'], 'prompt' => 'select_account'])
            ->redirect()
            ->getTargetUrl();

        return response()->json(['data' => ['url' => $url]]);
    }

    public function google(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:2000']]);
        abort_unless(filled(config('services.google.client_id')), 404);

        try {
            $profile = Socialite::driver('google')->stateless()->user();
        } catch (Throwable $e) {
            report($e);
            throw ValidationException::withMessages(['code' => 'Google sign-in failed. Please try again.']);
        }
        if (! $profile->getEmail()) {
            throw ValidationException::withMessages(['code' => 'Your Google account has no email address.']);
        }

        [$user, $token] = $this->accounts->signIn($profile->getEmail(), $profile->getName() ?: $profile->getEmail(), (string) $profile->getId(), $profile->getAvatar());

        return response()->json(['data' => ['token' => $token, 'user' => new MeResource($user)]]);
    }

    /** Local only: sign in as a test traveler without Google. */
    public function devLogin(Request $request): JsonResponse
    {
        abort_unless(self::devLoginEnabled(), 404);
        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        [$user, $token] = $this->accounts->signIn($data['email'], $data['name'] ?? 'Test Traveler');

        return response()->json(['data' => ['token' => $token, 'user' => new MeResource($user)]]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['data' => ['ok' => true]]);
    }

    private static function devLoginEnabled(): bool
    {
        return app()->isLocal() && config('services.dev_login');
    }
}
