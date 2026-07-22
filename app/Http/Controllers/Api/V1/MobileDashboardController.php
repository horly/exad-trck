<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MobileAlertResource;
use App\Http\Resources\Api\V1\MobileVehicleResource;
use App\Models\Alert;
use App\Models\Fleet;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileDashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $vehicles = Vehicle::query()->visibleTo($user);
        $recentVehicles = Vehicle::query()
            ->visibleTo($user)
            ->with(['fleet:id,name,code', 'device'])
            ->latest('updated_at')
            ->limit(8)
            ->get();
        $recentAlerts = Alert::query()
            ->visibleTo($user)
            ->with(['fleet:id,name,code', 'vehicle:id,name,registration_number'])
            ->latest('occurred_at')
            ->limit(5)
            ->get();
        $fleetDistribution = $user->isSuperadmin()
            ? Fleet::query()
                ->select(['id', 'name', 'code'])
                ->withCount([
                    'vehicles',
                    'vehicles as online_vehicles_count' => fn ($query) => $query
                        ->whereHas('device', fn ($query) => $query->where('status', 'online')),
                ])
                ->orderBy('name')
                ->get()
                ->map(fn (Fleet $fleet): array => [
                    'id' => $fleet->id,
                    'name' => $fleet->name,
                    'code' => $fleet->code,
                    'vehicles_total' => $fleet->vehicles_count,
                    'vehicles_online' => $fleet->online_vehicles_count,
                ])
                ->values()
            : collect();

        return response()->json([
            'data' => [
                'fleet' => $user->fleet ? [
                    'id' => $user->fleet->id,
                    'name' => $user->fleet->name,
                    'code' => $user->fleet->code,
                ] : null,
                'summary' => [
                    'fleets_total' => $user->isSuperadmin() ? $fleetDistribution->count() : 1,
                    'vehicles_total' => (clone $vehicles)->count(),
                    'vehicles_online' => (clone $vehicles)
                        ->whereHas('device', fn ($query) => $query->where('status', 'online'))
                        ->count(),
                    'vehicles_moving' => (clone $vehicles)
                        ->whereHas('device', fn ($query) => $query
                            ->where('status', 'online')
                            ->where('last_speed', '>', 0))
                        ->count(),
                    'vehicles_attention' => (clone $vehicles)
                        ->where(function ($query): void {
                            $query
                                ->whereDoesntHave('device')
                                ->orWhereHas('device', fn ($query) => $query->where('status', '!=', 'online'));
                        })
                        ->count(),
                    'new_alerts' => Alert::query()->visibleTo($user)->where('status', 'new')->count(),
                ],
                'fleet_distribution' => $fleetDistribution,
                'features' => [
                    'map' => $user->hasClientPermission(User::PERMISSION_MAP_VIEW),
                    'reports' => $user->hasClientPermission(User::PERMISSION_REPORTS_GENERATE),
                    'garages' => $user->hasClientPermission(User::PERMISSION_GARAGES_MANAGE),
                    'maintenance' => $user->hasClientPermission(User::PERMISSION_MAINTENANCE_MANAGE),
                ],
                'vehicles' => MobileVehicleResource::collection($recentVehicles)->resolve($request),
                'recent_alerts' => MobileAlertResource::collection($recentAlerts)->resolve($request),
            ],
        ]);
    }
}
