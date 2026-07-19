<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JsonException;

class ServerConsoleTicketController extends Controller
{
    /**
     * @throws JsonException
     */
    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('server_console.ticket_secret', '');

        abort_unless(config('server_console.enabled') && strlen($secret) >= 32, 503, __('server_logs.console_unavailable'));

        $ttl = max(10, min((int) config('server_console.ticket_ttl_seconds', 30), 60));
        $issuedAt = now()->timestamp;
        $claims = [
            'aud' => 'exad-server-console',
            'sub' => (string) $request->user()->getAuthIdentifier(),
            'name' => $request->user()->name,
            'nonce' => (string) Str::uuid(),
            'iat' => $issuedAt,
            'exp' => $issuedAt + $ttl,
        ];
        $payload = $this->base64UrlEncode(json_encode($claims, JSON_THROW_ON_ERROR));
        $signature = hash_hmac('sha256', $payload, $secret);

        Log::notice('Server console ticket issued.', [
            'user_id' => $request->user()->getAuthIdentifier(),
            'ip' => $request->ip(),
            'expires_at' => $claims['exp'],
        ]);

        return response()->json([
            'ticket' => $payload.'.'.$signature,
            'gateway_url' => (string) config('server_console.gateway_url'),
            'username' => (string) config('server_console.allowed_username'),
            'expires_in' => $ttl,
        ]);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
