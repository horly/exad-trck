<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Fleet;
use App\Services\DeviceMovementService;
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

    public function __construct(private readonly DeviceMovementService $movementService) {}

    public function index(Request $request): View
    {
        $showTechnicalDetails = $request->user()->isSuperadmin();
        $fleets = $showTechnicalDetails
            ? Fleet::query()->orderBy('name')->get(['id', 'name', 'code'])
            : collect();

        $summary = $this->summary($request);

        return view('map.index', [
            'mapProvider' => $this->mapProvider(),
            'mapboxToken' => (string) config('services.mapbox.public_token'),
            'googleMapsApiKey' => (string) config('services.google_maps.api_key'),
            'fleets' => $fleets,
            'showTechnicalDetails' => $showTechnicalDetails,
            'summary' => $summary,
            'defaultCenter' => [15.312, -4.325],
            'defaultZoom' => 11,
        ]);
    }

    public function devices(Request $request): JsonResponse
    {
        $showTechnicalDetails = $request->user()->isSuperadmin();
        $devices = $this->filteredDevices($request)
            ->whereNotNull('devices.last_latitude')
            ->whereNotNull('devices.last_longitude')
            ->get();
        $trails = $this->movementService->movementTrails($devices);

        return response()->json([
            'summary' => $this->summary($request),
            'geojson' => [
                'type' => 'FeatureCollection',
                'features' => $devices->map(function (Device $device) use ($trails, $showTechnicalDetails): array {
                    $trail = $trails[$device->id] ?? [];

                    $properties = [
                        'id' => $showTechnicalDetails ? $device->id : 'vehicle-'.$device->vehicle_id,
                        'vehicle' => $device->vehicle?->name ?: __('trackers.no_vehicle'),
                        'registration' => $device->vehicle?->registration_number ?: '-',
                        'fleet' => $device->fleet?->name ?: __('trackers.no_fleet'),
                        'fleet_code' => $device->fleet?->code ?: '-',
                        'status' => $device->status,
                        'status_label' => __('trackers.status_'.$device->status),
                        'is_parking' => $this->movementService->isParking($device),
                        'is_stationary_running' => $this->movementService->isStationaryRunning($device),
                        'is_moving' => $this->movementService->isMoving($device),
                        'trail' => $trail,
                        'speed' => (int) $device->last_speed,
                        'angle' => (int) $device->last_angle,
                        'last_signal' => $device->last_seen_at?->diffForHumans() ?? __('trackers.no_signal'),
                        'trips_url' => $showTechnicalDetails
                            ? route('trackers.trips', $device)
                            : route('vehicles.trips', $device->vehicle_id),
                        'details_url' => $showTechnicalDetails
                            ? route('trackers.details', $device)
                            : route('vehicles.tracker-details', $device->vehicle_id),
                        'technical_details' => $showTechnicalDetails,
                    ];

                    if ($showTechnicalDetails) {
                        $properties += [
                            'imei' => $device->imei,
                            'name' => $device->name ?: __('dashboard.device_fallback', ['imei' => $device->imei]),
                            'brand' => $device->brand ? __('trackers.brand_'.$device->brand) : '-',
                            'model' => $device->model ?: '-',
                        ];
                    }

                    return [
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => $this->deviceCoordinates($device),
                        ],
                        'properties' => $properties,
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
            ->when(! $user->isSuperadmin(), fn ($query) => $query->whereNotNull('devices.vehicle_id'))
            ->when(in_array($status, self::STATUSES, true), fn ($query) => $query->where('devices.status', $status))
            ->when($user->isSuperadmin() && $fleetId !== '' && $visibleFleetIds->contains($fleetId), fn ($query) => $query->where('devices.fleet_id', (int) $fleetId))
            ->when($search !== '' && ! $user->isSuperadmin(), function ($query) use ($search): void {
                $query->whereHas('vehicle', function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('registration_number', 'like', "%{$search}%");
                });
            })
            ->when($search !== '' && $user->isSuperadmin(), function ($query) use ($search): void {
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
