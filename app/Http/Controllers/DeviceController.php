<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Driver;
use App\Models\DriverIdentifier;
use App\Models\Position;
use App\Models\Vehicle;
use App\Services\CanBusStateService;
use App\Services\DeviceTripService;
use App\Services\PositionAddressService;
use App\Support\DriverIdentifierUid;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeviceController extends Controller
{
    /**
     * @var array<string, list<string>>
     */
    private const TRACKER_MODELS = [
        'teltonika' => [
            'FMB900',
            'FMB920',
            'FMC920',
            'FMM920',
            'FMB910',
            'FMB930',
            'FMB965',
            'FMT100',
            'FMP100',
            'FMB020',
            'FMB010',
            'FMC800',
            'FMM800',
            'FMM80A',
            'FMC880',
            'FMM880',
            'FMB110',
            'FMB120',
            'FMB122',
            'FMB125',
            'FMB130',
            'FMC125',
            'FMC130',
            'FMM125',
            'FMM130',
            'FMC13A',
            'FMM13A',
            'FMB202',
            'FMB204',
            'FMB209',
            'FMB230',
            'FMC225',
            'FMC230',
            'FMM230',
            'FMB240',
            'FMB225',
            'FMC234',
            'MSP500',
            'TAT100',
            'TAT140',
            'TAT141',
            'TAT240',
            'GH5200',
            'TMT250',
            'TFT100',
            'TST100',
            'FMB001',
            'FMB002',
            'FMB003',
            'FMC001',
            'FMM001',
            'FMC00A',
            'FMM00A',
            'FMC003',
            'FMM003',
            'FMB140',
            'FMB150',
            'FMC150',
            'FMM150',
            'FMC250',
            'FMM250',
            'FMB640',
            'FMB641',
            'FMC640',
            'FMM640',
            'FMC650',
            'FMM650',
        ],
        'edt' => [
            'Platinum7',
            'Autre EDT',
        ],
    ];

    /**
     * Liste pratique des opérateurs mobiles africains majeurs et locaux, conservée
     * par nom commercial pour simplifier le choix de l'opérateur SIM du traceur.
     *
     * @var list<string>
     */
    private const AFRICAN_OPERATORS = [
        '9mobile',
        'Africell',
        'Airtel',
        'Almadar',
        'Alou',
        'AT',
        'Azur',
        'BTC Mobile',
        'Cable & Wireless Seychelles',
        'Camtel',
        'Cell C',
        'Cellcom',
        'Celtiis',
        'Cellplus',
        'Chili',
        'Chinguitel',
        'Comium',
        'Comores Telecom',
        'CST',
        'CVMovel',
        'Digitel',
        'Djibouti Telecom',
        'Djezzy',
        'Econet',
        'Econet Leo',
        'Econet Telecom Lesotho',
        'Emtel',
        'Equitel',
        'EriTel',
        'Eswatini Mobile',
        'Etisalat',
        'Ethiotelecom',
        'Expresso',
        'Faiba',
        'Free',
        'Gamcel',
        'Gemtel',
        'Getesa',
        'GITGE',
        'Glo',
        'Golis Telecom',
        'Halotel',
        'Hormuud Telecom',
        'Inwi',
        'Intelvision',
        'Jamii Telecom',
        'Libya Phone',
        'Libyana',
        'Lonestar Cell MTN',
        'Lumitel',
        'Lycamobile',
        'Maroc Telecom',
        'Mascom',
        'M-Pesa',
        'Mattel',
        'Malitel',
        'Mauritel',
        'Mobilis',
        'Moov Africa',
        'Movitel',
        'MTN',
        'MTN Eswatini',
        'MTC',
        'Muni',
        'My.T',
        'Namibian Mobile Telecommunications',
        'NationLink',
        'NetOne',
        'Nexttel',
        'Niger Telecoms',
        'Onatel',
        'Ooredoo',
        'Orange',
        'Orange Liberia',
        'Paratus',
        'QCell',
        'Ramtel',
        'Safaricom',
        'Safaricom Ethiopia',
        'Salam',
        'Smile',
        'Somnet',
        'Somtel',
        'Spacetel',
        'Sudani',
        'Telesom',
        'T+',
        'Tchad Mobile',
        'Telecel',
        'Telecel Ghana',
        'Telecom Egypt',
        'Telecom Namibia',
        'Telkom',
        'Telma',
        'Tmcel',
        'Tigo',
        'TN Mobile',
        'TNM',
        'Togocom',
        'TTCL',
        'Tunisie Telecom',
        'Uganda Telecom',
        'Unitel',
        'Unitel STP',
        'Vivacell',
        'Vodacom',
        'Vodacom Lesotho',
        'Vodafone',
        'WE',
        'Yas',
        'Zain',
        'Zantel',
        'Zamtel',
        'Zamani Telecom',
        'Zimbabwe Telecel',
        'Autre opérateur africain',
    ];

    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $isDatatableRequest = $request->ajax();
        $sortableColumns = [
            'id' => 'devices.id',
            'name' => 'devices.name',
            'imei' => 'imei',
            'vehicle' => 'vehicle_name',
            'fleet' => 'fleet_name',
            'status' => 'devices.status',
            'last_seen_at' => 'last_seen_at',
        ];
        $sort = $isDatatableRequest && array_key_exists((string) $request->query('sort'), $sortableColumns)
            ? (string) $request->query('sort')
            : null;
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $canManageDevices = $request->user()->isSuperadmin();

        $devices = Device::query()
            ->visibleTo($request->user())
            ->with(['vehicle:id,fleet_id,name,registration_number', 'fleet:id,name,code'])
            ->select('devices.*')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'devices.vehicle_id')
            ->leftJoin('fleets', 'fleets.id', '=', 'devices.fleet_id')
            ->addSelect('vehicles.name as vehicle_name', 'fleets.name as fleet_name')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('devices.name', 'like', "%{$search}%")
                        ->orWhere('imei', 'like', "%{$search}%")
                        ->orWhere('devices.brand', 'like', "%{$search}%")
                        ->orWhere('devices.model', 'like', "%{$search}%")
                        ->orWhere('sim_number', 'like', "%{$search}%")
                        ->orWhere('operator_name', 'like', "%{$search}%")
                        ->orWhere('devices.status', 'like', "%{$search}%")
                        ->orWhere('vehicles.name', 'like', "%{$search}%")
                        ->orWhere('vehicles.registration_number', 'like', "%{$search}%")
                        ->orWhere('fleets.name', 'like', "%{$search}%");
                });
            })
            ->when($sort !== null, function ($query) use ($sortableColumns, $sort, $direction): void {
                $query->orderBy($sortableColumns[$sort], $direction)->orderByDesc('devices.created_at')->orderByDesc('devices.id');
            }, function ($query): void {
                $query->orderByDesc('devices.created_at')->orderByDesc('devices.id');
            })
            ->paginate(5)
            ->withQueryString();

        $viewData = [
            'devices' => $devices,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'canManageDevices' => $canManageDevices,
            'manageableVehicles' => $this->manageableVehicles($request),
            'availableVehicleIds' => $this->availableVehiclesForAssignment($request)->pluck('id')->all(),
            'trackerBrands' => array_keys(self::TRACKER_MODELS),
            'trackerModels' => self::TRACKER_MODELS,
            'trackerOperators' => self::AFRICAN_OPERATORS,
        ];

        if ($isDatatableRequest) {
            return response()->json([
                'html' => view('trackers.partials.table', $viewData)->render(),
            ]);
        }

        return view('trackers.index', $viewData);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeDeviceManagement($request);

        Device::query()->create($this->validatedDeviceData($request));

        return redirect()
            ->route('trackers.index')
            ->with('status', __('trackers.created'));
    }

    public function update(Request $request, Device $device): RedirectResponse
    {
        $this->authorizeDeviceManagement($request, $device);

        $device->update($this->validatedDeviceData($request, $device));

        return redirect()
            ->route('trackers.index')
            ->with('status', __('trackers.updated'));
    }

    public function destroy(Request $request, Device $device): RedirectResponse
    {
        $this->authorizeDeviceManagement($request, $device);

        DB::transaction(function () use ($device): void {
            $device->positions()->delete();
            $device->delete();
        });

        return redirect()
            ->route('trackers.index')
            ->with('status', __('trackers.deleted'))
            ->with('status_type', 'danger');
    }

    public function details(
        Request $request,
        Device $device,
        PositionAddressService $positionAddress,
        CanBusStateService $canBusState,
    ): JsonResponse {
        abort_unless(
            Device::query()->visibleTo($request->user())->whereKey($device->id)->exists(),
            403
        );

        return $this->detailsResponse(
            $device,
            $positionAddress,
            $canBusState,
            showTechnicalDetails: true,
            showDriverIdentifier: true,
            canControlEngine: $request->user()->can('control-engine', $device),
            engineCommandUrl: route('trackers.engine-commands.store', $device),
            engineDetailsUrl: route('trackers.details', $device),
        );
    }

    public function vehicleDetails(
        Request $request,
        Vehicle $vehicle,
        PositionAddressService $positionAddress,
        CanBusStateService $canBusState,
    ): JsonResponse {
        abort_unless(
            Vehicle::query()->visibleTo($request->user())->whereKey($vehicle->id)->exists(),
            403
        );

        $device = Device::query()
            ->visibleTo($request->user())
            ->where('vehicle_id', $vehicle->id)
            ->firstOrFail();

        return $this->detailsResponse(
            $device,
            $positionAddress,
            $canBusState,
            showTechnicalDetails: true,
            showDriverIdentifier: $request->user()->isSuperadmin(),
            canControlEngine: ! (bool) $request->attributes->get('client_preview', false)
                && $request->user()->can('control-engine', $device),
            engineCommandUrl: route('vehicles.engine-commands.store', $vehicle),
            engineDetailsUrl: route('vehicles.tracker-details', $vehicle),
        );
    }

    private function detailsResponse(
        Device $device,
        PositionAddressService $positionAddress,
        CanBusStateService $canBusState,
        bool $showTechnicalDetails,
        bool $showDriverIdentifier,
        bool $canControlEngine,
        string $engineCommandUrl,
        string $engineDetailsUrl,
    ): JsonResponse {
        $device->load([
            'fleet:id,name,code',
            'vehicle:id,fleet_id,name,registration_number',
            'vehicle.fleet:id,name,code',
            'trackerEvents' => fn ($query) => $query
                ->vehicleEvents()
                ->with('position:id,latitude,longitude')
                ->latest('started_at')
                ->latest('id')
                ->limit(5),
        ]);
        $latestPosition = Position::query()
            ->where('device_id', $device->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($device->last_position_at, fn ($query, $lastPositionAt) => $query->where('gps_time', '<=', $lastPositionAt))
            ->latest('gps_time')
            ->latest('server_time')
            ->latest('id')
            ->first(['id', 'device_id', 'gps_time', 'latitude', 'longitude', 'address', 'altitude', 'server_time', 'speed', 'angle', 'movement', 'ignition', 'raw_data']);
        $latestStoppedPosition = Position::query()
            ->where('device_id', $device->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where(function ($query): void {
                $query
                    ->where('movement', false)
                    ->orWhere('speed', 0)
                    ->orWhere('ignition', false);
            })
            ->when($device->last_position_at, fn ($query, $lastPositionAt) => $query->where('gps_time', '<=', $lastPositionAt))
            ->latest('gps_time')
            ->latest('server_time')
            ->latest('id')
            ->first(['id', 'device_id', 'gps_time', 'latitude', 'longitude', 'address', 'altitude', 'server_time', 'speed', 'angle', 'movement', 'ignition', 'raw_data']);
        $parkingStartPosition = $this->parkingStartPosition($device);
        $locationPosition = $parkingStartPosition ?: ($latestStoppedPosition ?: $latestPosition);
        $locationUpdatedAt = $locationPosition?->gps_time ?: ($locationPosition?->server_time ?: $device->last_position_at);
        $parkingStartedAt = $parkingStartPosition?->gps_time ?: $parkingStartPosition?->server_time;

        $locationAddress = $this->refreshReadableAddress(
            $device,
            $locationPosition,
            $positionAddress,
            $locationPosition instanceof Position && $latestPosition instanceof Position && $locationPosition->is($latestPosition),
        );
        $currentDriver = $this->currentDriverForDevice($device, $showDriverIdentifier);
        $engineCommand = $canControlEngine
            ? DeviceCommand::query()
                ->where('device_id', $device->id)
                ->latest('id')
                ->first()
            : null;
        $lastConfirmedEngineCommand = $canControlEngine
            ? DeviceCommand::query()
                ->where('device_id', $device->id)
                ->where('status', DeviceCommand::STATUS_CONFIRMED)
                ->latest('confirmed_at')
                ->latest('id')
                ->first()
            : null;

        return response()->json([
            'html' => view('trackers.partials.details', [
                'device' => $device,
                'currentDriver' => $currentDriver,
                'latestPosition' => $locationPosition,
                'locationAddress' => $locationAddress ?? __('trackers.address_unavailable'),
                'gpsQuality' => $this->gpsQuality($device),
                'direction' => $this->directionLabel((int) ($locationPosition?->angle ?? $device->last_angle)),
                'locationUpdatedAt' => $locationUpdatedAt,
                'parkingDuration' => $parkingStartedAt?->diffForHumans(null, true),
                'canBusStates' => $canBusState->forDevice($device),
                'showTechnicalDetails' => $showTechnicalDetails,
                'showDriverIdentifier' => $showDriverIdentifier,
                'canControlEngine' => $canControlEngine,
                'engineCommandUrl' => $engineCommandUrl,
                'engineDetailsUrl' => $engineDetailsUrl,
                'engineCommand' => $engineCommand,
                'engineImmobilized' => $lastConfirmedEngineCommand?->action === DeviceCommand::ACTION_IMMOBILIZE,
            ])->render(),
        ]);
    }

    private function currentDriverForDevice(Device $device, bool $includeIdentifier): ?Driver
    {
        $vehicleFleetId = $device->vehicle?->fleet_id;

        if ($vehicleFleetId === null || blank($device->last_driver_identifier_uid)) {
            return null;
        }

        $identifier = DriverIdentifier::query()
            ->with([
                'driver.department:id,name,code',
            ])
            ->whereIn('uid', DriverIdentifierUid::candidates($device->last_driver_identifier_uid))
            ->where('active', true)
            ->whereHas('driver', fn ($query) => $query->where('fleet_id', $vehicleFleetId))
            ->whereHas('driver.vehicles', fn ($query) => $query->whereKey($device->vehicle_id))
            ->first();

        if ($includeIdentifier) {
            $identifier?->driver?->setRelation('primaryIdentifier', $identifier);
        }

        return $identifier?->driver;
    }

    public function trips(Request $request, Device $device, DeviceTripService $tripService): JsonResponse
    {
        abort_unless(
            Device::query()->visibleTo($request->user())->whereKey($device->id)->exists(),
            403
        );

        return $this->tripResponse($request, $device, $tripService);
    }

    public function vehicleTrips(Request $request, Vehicle $vehicle, DeviceTripService $tripService): JsonResponse
    {
        abort_unless(
            Vehicle::query()->visibleTo($request->user())->whereKey($vehicle->id)->exists(),
            403
        );

        $device = Device::query()
            ->visibleTo($request->user())
            ->where('vehicle_id', $vehicle->id)
            ->firstOrFail();

        return $this->tripResponse($request, $device, $tripService);
    }

    private function tripResponse(Request $request, Device $device, DeviceTripService $tripService): JsonResponse
    {
        [$from, $to, $periodLabel] = $this->tripPeriod($request);
        $device->load(['fleet:id,name,code', 'vehicle:id,fleet_id,name,registration_number']);
        $payload = $tripService->build($device, $from, $to);

        return response()->json([
            'html' => view('trackers.partials.trips-results', [
                'device' => $device,
                'periodLabel' => $periodLabel,
                'trips' => $payload['trips'],
                'totalDistanceKm' => $payload['total_distance_km'],
                'totalDurationLabel' => $tripService->durationLabel($payload['total_duration_seconds']),
            ])->render(),
            'geojson' => $payload['geojson'],
            'summary' => [
                'count' => count($payload['trips']),
                'distance_km' => $payload['total_distance_km'],
                'duration_seconds' => $payload['total_duration_seconds'],
            ],
        ]);
    }

    private function authorizeDeviceManagement(Request $request, ?Device $device = null): void
    {
        $user = $request->user();

        abort_unless($user->isSuperadmin(), 403);

        if ($device === null || $user->isSuperadmin()) {
            return;
        }

        abort_unless($this->manageableVehicles($request)->contains('id', $device->vehicle_id), 403);
    }

    private function refreshReadableAddress(
        Device $device,
        ?Position $latestPosition,
        PositionAddressService $positionAddress,
        bool $syncDeviceAddress = true,
    ): ?string {
        if (! $latestPosition instanceof Position) {
            return null;
        }

        $resolvedAddress = $positionAddress->resolve($latestPosition);

        if ($resolvedAddress === null) {
            return null;
        }

        if ($syncDeviceAddress && $device->last_address !== $resolvedAddress) {
            $device->forceFill(['last_address' => $resolvedAddress])->save();
            $device->last_address = $resolvedAddress;
        }

        return $resolvedAddress;
    }

    private function parkingStartPosition(Device $device): ?Position
    {
        if ($device->last_ignition !== false) {
            return null;
        }

        $positions = Position::query()
            ->where('device_id', $device->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($device->last_position_at, fn ($query, $lastPositionAt) => $query->where('gps_time', '<=', $lastPositionAt))
            ->latest('gps_time')
            ->latest('server_time')
            ->latest('id')
            ->limit(250)
            ->get(['id', 'device_id', 'gps_time', 'latitude', 'longitude', 'address', 'altitude', 'server_time', 'speed', 'angle', 'movement', 'ignition', 'raw_data']);

        $parkingStart = null;

        foreach ($positions as $position) {
            if ($position->ignition !== false) {
                break;
            }

            $parkingStart = $position;
        }

        return $parkingStart ?: Position::query()
            ->where('device_id', $device->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('ignition', false)
            ->when($device->last_position_at, fn ($query, $lastPositionAt) => $query->where('gps_time', '<=', $lastPositionAt))
            ->latest('gps_time')
            ->latest('server_time')
            ->latest('id')
            ->first(['id', 'device_id', 'gps_time', 'latitude', 'longitude', 'address', 'altitude', 'server_time', 'speed', 'angle', 'movement', 'ignition', 'raw_data']);
    }

    /**
     * @return array{
     *     vehicle_id: int,
     *     fleet_id: int|null,
     *     imei: string,
     *     name?: string|null,
     *     brand: string,
     *     model: string,
     *     sim_number?: string|null,
     *     operator_name?: string|null,
     *     protocol: string
     * }
     */
    private function validatedDeviceData(Request $request, ?Device $device = null): array
    {
        $manageableVehicles = $this->manageableVehicles($request);
        $assignableVehicles = $this->availableVehiclesForAssignment($request, $device);
        $assignableVehicleIds = $assignableVehicles
            ->pluck('id')
            ->map(fn (int $id): string => (string) $id)
            ->all();

        $data = $request->validate([
            'vehicle_id' => ['required', 'integer', Rule::in($assignableVehicleIds)],
            'imei' => ['required', 'string', 'max:20', Rule::unique('devices')->ignore($device)],
            'name' => ['nullable', 'string', 'max:255'],
            'brand' => ['required', Rule::in(array_keys(self::TRACKER_MODELS))],
            'model' => ['required', 'string', 'max:255', Rule::in($this->trackerModelsForBrand((string) $request->input('brand')))],
            'sim_number' => ['nullable', 'string', 'max:30'],
            'operator_name' => ['nullable', 'string', 'max:50', Rule::in(self::AFRICAN_OPERATORS)],
            'protocol' => ['required', Rule::in(['TCP', 'UDP', 'HTTP'])],
        ]);

        $vehicle = $manageableVehicles->firstWhere('id', (int) $data['vehicle_id']);

        $data['fleet_id'] = $vehicle?->fleet_id;

        return $data;
    }

    /**
     * @return list<string>
     */
    private function trackerModelsForBrand(string $brand): array
    {
        return self::TRACKER_MODELS[$brand] ?? [];
    }

    private function manageableVehicles(Request $request)
    {
        $user = $request->user();

        return Vehicle::query()
            ->with('fleet:id,name,code')
            ->when(! $user->isSuperadmin(), function ($query) use ($user): void {
                $query->whereHas('fleet.users', function ($query) use ($user): void {
                    $query
                        ->whereKey($user->id)
                        ->where('fleet_user.permission', 'manager');
                });
            })
            ->orderBy('name')
            ->get(['id', 'fleet_id', 'name', 'registration_number']);
    }

    private function availableVehiclesForAssignment(Request $request, ?Device $device = null)
    {
        $assignedVehicleIds = Device::query()
            ->whereNotNull('vehicle_id')
            ->when($device !== null, fn ($query) => $query->whereKeyNot($device->id))
            ->pluck('vehicle_id')
            ->all();

        return $this->manageableVehicles($request)
            ->reject(fn (Vehicle $vehicle): bool => in_array($vehicle->id, $assignedVehicleIds, true))
            ->values();
    }

    private function gpsQuality(Device $device): ?int
    {
        if ($device->last_satellites === null) {
            return null;
        }

        return min(100, max(0, (int) $device->last_satellites * 7));
    }

    private function directionLabel(int $angle): string
    {
        $directions = ['N', 'NE', 'E', 'SE', 'S', 'SO', 'O', 'NO'];
        $index = (int) round(($angle % 360) / 45) % 8;

        return $directions[$index];
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function tripPeriod(Request $request): array
    {
        $period = (string) $request->query('period', 'today');
        $now = now();

        return match ($period) {
            'yesterday' => [
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
                __('trackers.trip_period_yesterday'),
            ],
            'week' => [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
                __('trackers.trip_period_week'),
            ],
            'current_month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                __('trackers.trip_period_current_month'),
            ],
            'last_month' => [
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->endOfMonth(),
                __('trackers.trip_period_last_month'),
            ],
            'custom' => $this->customTripPeriod($request),
            default => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                __('trackers.trip_period_today'),
            ],
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function customTripPeriod(Request $request): array
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $from = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : now()->startOfDay();
        $to = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : now()->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [
            $from,
            $to,
            __('trackers.trip_period_custom_label', [
                'from' => $from->format('d.m.Y'),
                'to' => $to->format('d.m.Y'),
            ]),
        ];
    }
}
