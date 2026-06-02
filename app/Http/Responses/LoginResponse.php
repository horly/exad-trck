<?php

namespace App\Http\Responses;

use App\Models\UserLoginHistory;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();

        UserLoginHistory::query()->create([
            'user_id' => $user->id,
            'device' => $this->deviceName($request->userAgent()),
            'ip_address' => $request->ip(),
            'logged_in_at' => now(),
        ]);

        if ($user->isSuperadmin()) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('fleets.index');
    }

    private function deviceName(?string $userAgent): string
    {
        $agent = (string) $userAgent;
        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Navigateur inconnu',
        };
        $platform = match (true) {
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS') || str_contains($agent, 'Macintosh') => 'macOS',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone') || str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'appareil inconnu',
        };

        return "{$browser} on {$platform}";
    }
}
