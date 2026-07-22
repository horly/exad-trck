<?php

namespace App\Http\Middleware;

use App\Models\MobileSession;
use App\Services\MobileTokenService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobileAccessToken
{
    public function __construct(
        private readonly MobileTokenService $tokens
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (! $user || ! $token || ! $token->can(MobileTokenService::ACCESS_ABILITY)) {
            return $this->unauthorized('INVALID_ACCESS_TOKEN', 'Jeton d’accès mobile invalide.');
        }

        $session = MobileSession::query()
            ->active()
            ->where('access_token_id', $token->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $session || $session->access_expires_at->isPast()) {
            return $this->unauthorized('MOBILE_SESSION_EXPIRED', 'La session mobile a expiré.');
        }

        if (! $user->isActive() || (! $user->isSuperadmin() && $user->fleet_id === null)) {
            $this->tokens->revoke($session);

            return response()->json([
                'error' => [
                    'code' => 'ACCOUNT_UNAVAILABLE',
                    'message' => 'Ce compte ne peut pas accéder à l’application mobile.',
                ],
            ], 403);
        }

        if ($session->last_used_at === null || $session->last_used_at->lt(now()->subMinutes(5))) {
            $session->forceFill(['last_used_at' => now()])->save();
        }

        $request->attributes->set('mobile_session', $session);

        return $next($request);
    }

    private function unauthorized(string $code, string $message): JsonResponse
    {
        return response()->json([
            'error' => compact('code', 'message'),
        ], 401);
    }
}
