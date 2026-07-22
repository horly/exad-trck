<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

class MobileTwoFactorChallengeService
{
    public function __construct(
        private readonly TwoFactorAuthenticationProvider $provider
    ) {}

    /**
     * @param  array<string, mixed>  $device
     * @return array{challenge_token: string, expires_in: int}
     */
    public function create(User $user, array $device): array
    {
        $plainToken = Str::random(80);
        $ttlMinutes = max(1, (int) config('mobile-api.two_factor_challenge_ttl_minutes'));

        Cache::put($this->key($plainToken), [
            'user_id' => $user->id,
            'device' => $device,
        ], now()->addMinutes($ttlMinutes));

        return [
            'challenge_token' => $plainToken,
            'expires_in' => $ttlMinutes * 60,
        ];
    }

    /**
     * @return array{user: User, device: array<string, mixed>}|null
     */
    public function verify(string $challengeToken, ?string $code, ?string $recoveryCode): ?array
    {
        $payload = Cache::get($this->key($challengeToken));

        if (! is_array($payload)) {
            return null;
        }

        $user = User::query()->find($payload['user_id'] ?? null);

        if (! $user || ! $user->hasEnabledTwoFactorAuthentication()) {
            return null;
        }

        if ($code !== null && $code !== '') {
            $valid = $this->provider->verify(
                Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
                $code
            );
        } else {
            $valid = $recoveryCode !== null
                && $user->two_factor_recovery_codes !== null
                && in_array($recoveryCode, $user->recoveryCodes(), true);

            if ($valid) {
                $user->replaceRecoveryCode($recoveryCode);
            }
        }

        if (! $valid) {
            return null;
        }

        Cache::forget($this->key($challengeToken));

        return [
            'user' => $user,
            'device' => is_array($payload['device'] ?? null) ? $payload['device'] : [],
        ];
    }

    private function key(string $plainToken): string
    {
        return 'mobile-api:2fa:'.hash('sha256', $plainToken);
    }
}
