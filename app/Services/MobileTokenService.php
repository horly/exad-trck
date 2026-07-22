<?php

namespace App\Services;

use App\Models\MobileSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class MobileTokenService
{
    public const ACCESS_ABILITY = 'mobile:access';

    public const REFRESH_ABILITY = 'mobile:refresh';

    /**
     * @param  array{device_identifier: string, device_name: string, platform: string, app_version?: string|null, ip_address?: string|null, user_agent?: string|null}  $device
     * @return array<string, mixed>
     */
    public function issue(User $user, array $device): array
    {
        return DB::transaction(function () use ($user, $device): array {
            MobileSession::query()
                ->whereNull('revoked_at')
                ->where('user_id', $user->id)
                ->where('device_identifier', $device['device_identifier'])
                ->lockForUpdate()
                ->get()
                ->each(fn (MobileSession $session) => $this->revokeSession($session));

            return $this->createPair($user, $device);
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function rotate(MobileSession $mobileSession, int $refreshTokenId): ?array
    {
        return DB::transaction(function () use ($mobileSession, $refreshTokenId): ?array {
            $session = MobileSession::query()->lockForUpdate()->find($mobileSession->id);

            if (! $session
                || $session->revoked_at !== null
                || $session->refresh_token_id !== $refreshTokenId
                || $session->refresh_expires_at->isPast()) {
                return null;
            }

            $user = $session->user()->firstOrFail();
            $oldTokenIds = array_filter([$session->access_token_id, $session->refresh_token_id]);
            $pair = $this->newTokens($user, $session->device_name);

            $session->forceFill([
                'access_token_id' => $pair['access_model']->id,
                'refresh_token_id' => $pair['refresh_model']->id,
                'last_used_at' => now(),
                'access_expires_at' => $pair['access_expires_at'],
                'refresh_expires_at' => $pair['refresh_expires_at'],
            ])->save();

            PersonalAccessToken::query()->whereIn('id', $oldTokenIds)->delete();

            return $this->tokenPayload($session, $pair);
        });
    }

    public function revoke(MobileSession $session): void
    {
        DB::transaction(fn () => $this->revokeSession($session));
    }

    public function revokeAll(User $user): int
    {
        return DB::transaction(function () use ($user): int {
            $sessions = MobileSession::query()
                ->whereNull('revoked_at')
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->get();

            $sessions->each(fn (MobileSession $session) => $this->revokeSession($session));

            return $sessions->count();
        });
    }

    /**
     * @param  array<string, mixed>  $device
     * @return array<string, mixed>
     */
    private function createPair(User $user, array $device): array
    {
        $pair = $this->newTokens($user, $device['device_name']);
        $session = MobileSession::query()->create([
            'user_id' => $user->id,
            'access_token_id' => $pair['access_model']->id,
            'refresh_token_id' => $pair['refresh_model']->id,
            'device_identifier' => $device['device_identifier'],
            'device_name' => $device['device_name'],
            'platform' => $device['platform'],
            'app_version' => $device['app_version'] ?? null,
            'ip_address' => $device['ip_address'] ?? null,
            'user_agent' => $device['user_agent'] ?? null,
            'last_used_at' => now(),
            'access_expires_at' => $pair['access_expires_at'],
            'refresh_expires_at' => $pair['refresh_expires_at'],
        ]);

        return $this->tokenPayload($session, $pair);
    }

    /**
     * @return array<string, mixed>
     */
    private function newTokens(User $user, string $deviceName): array
    {
        $accessExpiresAt = now()->addMinutes(max(1, (int) config('mobile-api.access_token_ttl_minutes')));
        $refreshExpiresAt = now()->addDays(max(1, (int) config('mobile-api.refresh_token_ttl_days')));
        $access = $user->createToken(
            'mobile-access:'.$deviceName,
            [self::ACCESS_ABILITY],
            $accessExpiresAt
        );
        $refresh = $user->createToken(
            'mobile-refresh:'.$deviceName,
            [self::REFRESH_ABILITY],
            $refreshExpiresAt
        );

        return [
            'access_model' => $access->accessToken,
            'refresh_model' => $refresh->accessToken,
            'access_token' => $access->plainTextToken,
            'refresh_token' => $refresh->plainTextToken,
            'access_expires_at' => $accessExpiresAt,
            'refresh_expires_at' => $refreshExpiresAt,
        ];
    }

    /**
     * @param  array<string, mixed>  $pair
     * @return array<string, mixed>
     */
    private function tokenPayload(MobileSession $session, array $pair): array
    {
        return [
            'token_type' => 'Bearer',
            'access_token' => $pair['access_token'],
            'expires_in' => (int) now()->diffInSeconds($pair['access_expires_at']),
            'refresh_token' => $pair['refresh_token'],
            'refresh_expires_in' => (int) now()->diffInSeconds($pair['refresh_expires_at']),
            'session_id' => $session->id,
        ];
    }

    private function revokeSession(MobileSession $session): void
    {
        $tokenIds = array_filter([$session->access_token_id, $session->refresh_token_id]);

        $session->forceFill([
            'revoked_at' => $session->revoked_at ?? now(),
        ])->save();

        if ($tokenIds !== []) {
            PersonalAccessToken::query()->whereIn('id', $tokenIds)->delete();
        }
    }
}
