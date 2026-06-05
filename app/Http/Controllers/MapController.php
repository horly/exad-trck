<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Fleet;
use App\Models\Position;
use App\Services\GoogleRoadsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MapController extends Controller
{
    /**
     * @var list<string>
     */
    private const STATUSES = ['online', 'inactive', 'offline', 'maintenance'];

    public function index(Request $request): View
    {
        $fleets = Fleet::query()
            ->visibleTo($request->user())
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $summary = $this->summary($request);

        return view('map.index', [
            'mapProvider' => $this->mapProvider(),
            'mapboxToken' => (string) config('services.mapbox.public_token'),
            'googleMapsApiKey' => (string) config('services.google_maps.api_key'),
            'fleets' => $fleets,
            'summary' => $summary,
            'defaultCenter' => [15.312, -4.325],
            'defaultZoom' => 11,
        ]);
    }

    public function devices(Request $request, GoogleRoadsService $googleRoads): JsonResponse
    {
        $devices = $this->filteredDevices($request)
            ->whereNotNull('devices.last_latitude')
            ->whereNotNull('devices.last_longitude')
            ->get();
        $trails = $this->movementTrails($devices, $googleRoads);

        return response()->json([
            'summary' => $this->summary($request),
            'geojson' => [
                'type' => 'FeatureCollection',
                'features' => $devices->map(function (Device $device) use ($trails, $googleRoads): array {
                    $trail = $trails[$device->id] ?? [];

                    return [
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => $this->deviceCoordinates($device, $trail, $googleRoads),
                        ],
                        'properties' => [
                            'id' => $device->id,
                            'imei' => $device->imei,
                            'name' => $device->name ?: __('dashboard.device_fallback', ['imei' => $device->imei]),
                            'brand' => $device->brand ? __('trackers.brand_'.$device->brand) : '-',
                            'model' => $device->model ?: '-',
                            'vehicle' => $device->vehicle?->name ?: __('trackers.no_vehicle'),
                            'registration' => $device->vehicle?->registration_number ?: '-',
                            'fleet' => $device->fleet?->name ?: __('trackers.no_fleet'),
                            'fleet_code' => $device->fleet?->code ?: '-',
                            'status' => $device->status,
                            'status_label' => __('trackers.status_'.$device->status),
                            'is_parking' => $this->isParking($device),
                            'is_stationary_running' => $this->isStationaryRunning($device),
                            'is_moving' => $this->isMoving($device),
                            'trail' => $trail,
                            'speed' => (int) $device->last_speed,
                            'angle' => (int) $device->last_angle,
                            'last_signal' => $device->last_seen_at?->diffForHumans() ?? __('trackers.no_signal'),
                            'details_url' => route('trackers.details', $device),
                            'trips_url' => route('trackers.trips', $device),
                        ],
                    ];
                })->values(),
            ],
        ]);
    }

    private function filteredDevices(Request $request)
    {
        $user = $request->user();
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $fleetId = (string) $request->query('fleet_id', '');
        $visibleFleetIds = Fleet::query()->visibleTo($user)->pluck('id')->map(fn (int $id): string => (string) $id);

        return Device::query()
            ->visibleTo($user)
            ->with(['vehicle:id,fleet_id,name,registration_number', 'fleet:id,name,code'])
            ->when(in_array($status, self::STATUSES, true), fn ($query) => $query->where('devices.status', $status))
            ->when($fleetId !== '' && $visibleFleetIds->contains($fleetId), fn ($query) => $query->where('devices.fleet_id', (int) $fleetId))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('devices.name', 'like', "%{$search}%")
                        ->orWhere('devices.imei', 'like', "%{$search}%")
                        ->orWhere('devices.model', 'like', "%{$search}%")
                        ->orWhereHas('vehicle', function ($query) use ($search): void {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('registration_number', 'like', "%{$search}%");
                        })
                        ->orWhereHas('fleet', function ($query) use ($search): void {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('devices.last_seen_at')
            ->latest('devices.id');
    }

    /**
     * @return array<string, int>
     */
    private function summary(Request $request): array
    {
        $baseQuery = $this->filteredDevices($request);

        return [
            'total' => (clone $baseQuery)->count(),
            'positioned' => (clone $baseQuery)->whereNotNull('devices.last_latitude')->whereNotNull('devices.last_longitude')->count(),
            'online' => (clone $baseQuery)->where('devices.status', 'online')->count(),
            'inactive' => (clone $baseQuery)->where('devices.status', 'inactive')->count(),
            'offline' => (clone $baseQuery)->where('devices.status', 'offline')->count(),
            'maintenance' => (clone $baseQuery)->where('devices.status', 'maintenance')->count(),
        ];
    }

    private function isMoving(Device $device): bool
    {
        if ($device->status !== 'online') {
            return false;
        }

        return $device->last_movement !== null
            ? $device->last_movement
            : (int) $device->last_speed > 0;
    }

    private function isParking(Device $device): bool
    {
        return $this->isStopped($device) && $device->last_ignition === false;
    }

    private function isStationaryRunning(Device $device): bool
    {
        return $this->isStopped($device) && $device->last_ignition === true;
    }

    private function isStopped(Device $device): bool
    {
        if ($device->status !== 'online') {
            return false;
        }

        return $device->last_movement !== null
            ? ! $device->last_movement
            : (int) $device->last_speed === 0;
    }

    private function movementTrails($devices, GoogleRoadsService $googleRoads): array
    {
        $movingDevices = $devices
            ->filter(fn (Device $device): bool => $this->isMoving($device))
            ->keyBy('id');

        if ($movingDevices->isEmpty()) {
            return [];
        }

        return Position::query()
            ->whereIn('device_id', $movingDevices->keys())
            ->where('server_time', '>=', now()->subMinutes(30))
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('is_valid', true)
            ->orderBy('device_id')
            ->latest('server_time')
            ->get(['device_id', 'latitude', 'longitude', 'server_time'])
            ->groupBy('device_id')
            ->map(function ($positions, int $deviceId) use ($movingDevices, $googleRoads): array {
                $device = $movingDevices->get($deviceId);
                $coordinates = $positions
                    ->take(8)
                    ->reverse()
                    ->map(fn (Position $position): array => [
                        (float) $position->longitude,
                        (float) $position->latitude,
                    ])
                    ->values()
                    ->all();

                $current = [
                    (float) $device->last_longitude,
                    (float) $device->last_latitude,
                ];

                if ($coordinates === [] || end($coordinates) !== $current) {
                    $coordinates[] = $current;
                }

                $snapped = $googleRoads->snap(
                    $positions
                        ->take(8)
                        ->reverse()
                        ->push(new Position([
                            'latitude' => $device->last_latitude,
                            'longitude' => $device->last_longitude,
                        ]))
                        ->values()
                );

                return count($snapped) > 1 ? $snapped : $coordinates;
            })
            ->filter(fn (array $coordinates): bool => count($coordinates) > 1)
            ->all();
    }

    /**
     * @param  list<array{0: float, 1: float}>  $trail
     * @return array{0: float, 1: float}
     */
    private function deviceCoordinates(Device $device, array $trail, GoogleRoadsService $googleRoads): array
    {
        if ($trail !== []) {
            $last = end($trail);

            if (is_array($last) && count($last) >= 2) {
                return [(float) $last[0], (float) $last[1]];
            }
        }

        $raw = [
            (float) $device->last_longitude,
            (float) $device->last_latitude,
        ];

        return $googleRoads->nearest((float) $device->last_latitude, (float) $device->last_longitude) ?? $raw;
    }

    private function mapProvider(): string
    {
        $provider = (string) config('services.maps.provider', 'google');

        return in_array($provider, ['google', 'mapbox'], true) ? $provider : 'google';
    }
}
