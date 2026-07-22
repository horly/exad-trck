<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\DeviceMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileMapController extends Controller
{
    public function __construct(private readonly DeviceMovementService $movementService) {}

    public function __invoke(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['online', 'offline', 'inactive', 'maintenance'])],
            'fleet_id' => ['nullable', 'integer', 'exists:fleets,id'],
        ]);
        $search = trim((string) ($filters['search'] ?? ''));
        $user = $request->user();
        $query = Device::query()
            ->visibleTo($user)
            ->with(['vehicle:id,fleet_id,name,registration_number', 'fleet:id,name,code'])
            ->whereNotNull('vehicle_id')
            ->whereNotNull('last_latitude')
            ->whereNotNull('last_longitude')
            ->when(isset($filters['status']), fn ($query) => $query->where('devices.status', $filters['status']))
            ->when($user->isSuperadmin() && isset($filters['fleet_id']), fn ($query) => $query->where('devices.fleet_id', $filters['fleet_id']))
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('vehicle', function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('registration_number', 'like', "%{$search}%");
                });
            });
        $devices = $query->latest('last_seen_at')->get();
        $trails = $this->movementService->movementTrails($devices);

        return response()->json([
            'data' => [
                'summary' => [
                    'total' => $devices->count(),
                    'positioned' => $devices->count(),
                    'online' => $devices->where('status', 'online')->count(),
                    'inactive' => $devices->where('status', 'inactive')->count(),
                    'offline' => $devices->where('status', 'offline')->count(),
                    'maintenance' => $devices->where('status', 'maintenance')->count(),
                    'moving' => $devices->filter(fn (Device $device): bool => $this->movementService->isMoving($device))->count(),
                ],
                'geojson' => [
                    'type' => 'FeatureCollection',
                    'features' => $devices->map(fn (Device $device): array => [
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [(float) $device->last_longitude, (float) $device->last_latitude],
                        ],
                        'properties' => [
                            'vehicle_id' => $device->vehicle_id,
                            'vehicle' => $device->vehicle?->name,
                            'registration_number' => $device->vehicle?->registration_number,
                            'fleet' => $device->fleet ? [
                                'id' => $device->fleet->id,
                                'name' => $device->fleet->name,
                                'code' => $device->fleet->code,
                            ] : null,
                            'status' => $device->status,
                            'speed_kmh' => (int) $device->last_speed,
                            'heading' => (int) $device->last_angle,
                            'ignition' => $device->last_ignition,
                            'movement' => $device->last_movement,
                            'is_moving' => $this->movementService->isMoving($device),
                            'is_parking' => $this->movementService->isParking($device),
                            'is_stationary_running' => $this->movementService->isStationaryRunning($device),
                            'trail' => $trails[$device->id] ?? [],
                            'last_signal_at' => $device->last_seen_at?->toISOString(),
                            'address' => $device->last_address,
                        ],
                    ])->values(),
                ],
            ],
        ]);
    }

}
