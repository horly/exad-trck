<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Fleet;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MapController extends Controller
{
    /**
     * @var list<string>
     */
    private const STATUSES = ['online', 'inactive', 'offline', 'maintenance'];
    private const MOVEMENT_TRAIL_MAX_POINTS = 80;
    private const MOVEMENT_TRAIL_WINDOW_MINUTES = 120;

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

    public function devices(Request $request): JsonResponse
    {
        $devices = $this->filteredDevices($request)
            ->whereNotNull('devices.last_latitude')
            ->whereNotNull('devices.last_longitude')
            ->get();
        $trails = $this->movementTrails($devices);

        return response()->json([
            'summary' => $this->summary($request),
            'geojson' => [
                'type' => 'FeatureCollection',
                'features' => $devices->map(function (Device $device) use ($trails): array {
                    $trail = $trails[$device->id] ?? [];

                    return [
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => $this->deviceCoordinates($device),
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
        $search = $this->normalizedSearchTerm((string) $request->query('search', ''));
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
                        ->orWhere('devices.last_address', 'like', "%{$search}%")
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

    private function normalizedSearchTerm(string $value): string
    {
        $search = Str::of($value)->squish()->toString();

        if ($search === '') {
            return '';
        }

        $parts = explode(' ', $search);

        if (count($parts) > 1 && collect($parts)->every(fn (string $part): bool => Str::length($part) === 1)) {
            return implode('', $parts);
        }

        return $search;
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

        if ($device->last_ignition === false) {
            return false;
        }

        return $this->hasMovement($device);
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

        if ($device->last_ignition === false) {
            return true;
        }

        return ! $this->hasMovement($device);
    }

    private function hasMovement(Device $device): bool
    {
        return $device->last_movement !== null
            ? (bool) $device->last_movement
            : (int) $device->last_speed > 0;
    }

    private function movementTrails($devices): array
    {
        $movingDevices = $devices
            ->filter(fn (Device $device): bool => $this->isMoving($device))
            ->keyBy('id');

        if ($movingDevices->isEmpty()) {
            return [];
        }

        return Position::query()
            ->whereIn('device_id', $movingDevices->keys())
            ->where('server_time', '>=', now()->subMinutes(self::MOVEMENT_TRAIL_WINDOW_MINUTES))
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('is_valid', true)
            ->orderBy('device_id')
            ->latest('server_time')
            ->get(['device_id', 'latitude', 'longitude', 'server_time'])
            ->groupBy('device_id')
            ->map(function ($positions, int $deviceId) use ($movingDevices): array {
                $device = $movingDevices->get($deviceId);
                $coordinates = $positions
                    ->take(self::MOVEMENT_TRAIL_MAX_POINTS)
                    ->reverse()
                    ->map(fn (Position $position): array => [
                        (float) $position->longitude,
                        (float) $position->latitude,
                    ])
                    ->values()
                    ->all();

                $coordinates = array_reduce($coordinates, function (array $carry, array $coordinate): array {
                    if ($carry === [] || end($carry) !== $coordinate) {
                        $carry[] = $coordinate;
                    }

                    return $carry;
                }, []);

                $current = [
                    (float) $device->last_longitude,
                    (float) $device->last_latitude,
                ];

                if ($coordinates === [] || end($coordinates) !== $current) {
                    $coordinates[] = $current;
                }

                return $coordinates;
            })
            ->filter(fn (array $coordinates): bool => count($coordinates) > 1)
            ->all();
    }

    private function deviceCoordinates(Device $device): array
    {
        return [
            (float) $device->last_longitude,
            (float) $device->last_latitude,
        ];
    }

    private function mapProvider(): string
    {
        $provider = (string) config('services.maps.provider', 'google');

        return in_array($provider, ['google', 'mapbox'], true) ? $provider : 'google';
    }
}
