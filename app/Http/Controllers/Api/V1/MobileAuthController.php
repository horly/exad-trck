<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MobileLoginRequest;
use App\Http\Requests\Api\V1\MobileTwoFactorRequest;
use App\Http\Resources\Api\V1\MobileUserResource;
use App\Models\MobileSession;
use App\Models\User;
use App\Services\MobileTokenService;
use App\Services\MobileTwoFactorChallengeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MobileAuthController extends Controller
{
    public function __construct(
        private readonly MobileTokenService $tokens,
        private readonly MobileTwoFactorChallengeService $twoFactor
    ) {}

    public function login(MobileLoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email')->lower()->toString())->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        if ($response = $this->accountUnavailableResponse($user)) {
            return $response;
        }

        $device = $this->devicePayload($request);

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return response()->json([
                'data' => [
                    'two_factor_required' => true,
                    ...$this->twoFactor->create($user, $device),
                ],
            ], 202);
        }

        return $this->authenticatedResponse($user, $this->tokens->issue($user, $device));
    }

    public function twoFactor(MobileTwoFactorRequest $request): JsonResponse
    {
        $verified = $this->twoFactor->verify(
            $request->string('challenge_token')->toString(),
            $request->filled('code') ? $request->string('code')->toString() : null,
            $request->filled('recovery_code') ? $request->string('recovery_code')->toString() : null,
        );

        if (! $verified) {
            throw ValidationException::withMessages([
                'code' => ['Le code de sécurité est invalide ou a expiré.'],
            ]);
        }

        if ($response = $this->accountUnavailableResponse($verified['user'])) {
            return $response;
        }

        return $this->authenticatedResponse(
            $verified['user'],
            $this->tokens->issue($verified['user'], $verified['device'])
        );
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (! $user || ! $token || ! $token->can(MobileTokenService::REFRESH_ABILITY)) {
            return $this->tokenError('INVALID_REFRESH_TOKEN', 'Jeton de rafraîchissement invalide.');
        }

        $session = MobileSession::query()
            ->active()
            ->where('user_id', $user->id)
            ->where('refresh_token_id', $token->id)
            ->first();

        if ($response = $this->accountUnavailableResponse($user)) {
            if ($session) {
                $this->tokens->revoke($session);
            }

            return $response;
        }

        $pair = $session ? $this->tokens->rotate($session, $token->id) : null;

        if (! $pair) {
            return $this->tokenError('REFRESH_SESSION_EXPIRED', 'La session de rafraîchissement a expiré.');
        }

        return $this->authenticatedResponse($user, $pair);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var MobileSession $session */
        $session = $request->attributes->get('mobile_session');
        $this->tokens->revoke($session);

        return response()->json(['message' => 'Session mobile fermée.']);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $revoked = $this->tokens->revokeAll($request->user());

        return response()->json([
            'message' => 'Toutes les sessions mobiles ont été fermées.',
            'revoked_sessions' => $revoked,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function devicePayload(MobileLoginRequest $request): array
    {
        return [
            ...$request->safe()->only([
                'device_identifier',
                'device_name',
                'platform',
                'app_version',
            ]),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }

    /**
     * @param  array<string, mixed>  $pair
     */
    private function authenticatedResponse(User $user, array $pair): JsonResponse
    {
        $user->loadMissing('fleet:id,name,code');

        return response()->json([
            'data' => [
                'two_factor_required' => false,
                'tokens' => $pair,
                'user' => (new MobileUserResource($user))->resolve(),
            ],
        ]);
    }

    private function accountUnavailableResponse(User $user): ?JsonResponse
    {
        if ($user->isActive() && ($user->isSuperadmin() || $user->fleet_id !== null)) {
            return null;
        }

        return response()->json([
            'error' => [
                'code' => 'ACCOUNT_UNAVAILABLE',
                'message' => 'Ce compte ne peut pas accéder à l’application mobile.',
            ],
        ], 403);
    }

    private function tokenError(string $code, string $message): JsonResponse
    {
        return response()->json(['error' => compact('code', 'message')], 401);
    }
}
