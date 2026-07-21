<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\Fleet;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ApplyClientPreview
{
    public const SESSION_KEY = 'client_preview_fleet_id';

    private const EXCLUDED_ROUTES = [
        'fleets.dashboard',
        'client-preview.exit',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        View::share('clientPreviewFleet', null);

        $user = $request->user();
        $fleetId = $request->session()->get(self::SESSION_KEY);

        if ($user === null || ! $user->isSuperadmin() || ! is_numeric($fleetId)) {
            return $next($request);
        }

        $fleet = Fleet::query()->find((int) $fleetId);

        if ($fleet === null) {
            $request->session()->forget(self::SESSION_KEY);

            return $next($request);
        }

        if ($request->routeIs(...self::EXCLUDED_ROUTES)) {
            return $next($request);
        }

        abort_unless($request->isMethodSafe(), 403);

        $originalRole = $user->role;
        $originalFleetId = $user->fleet_id;
        $hadFleetRelation = $user->relationLoaded('fleet');
        $originalFleet = $hadFleetRelation ? $user->getRelation('fleet') : null;

        $request->attributes->set('client_preview', true);
        View::share('clientPreviewFleet', $fleet);
        $user->forceFill([
            'role' => UserRole::Admin,
            'fleet_id' => $fleet->id,
        ]);
        $user->setRelation('fleet', $fleet);

        try {
            return $next($request);
        } finally {
            $user->forceFill([
                'role' => $originalRole,
                'fleet_id' => $originalFleetId,
            ]);

            if ($hadFleetRelation) {
                $user->setRelation('fleet', $originalFleet);
            } else {
                $user->unsetRelation('fleet');
            }
        }
    }
}
