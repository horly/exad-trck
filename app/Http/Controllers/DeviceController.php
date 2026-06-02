<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'FMB910',
            'FMB920',
            'FMC920',
            'FMM920',
            'FMB930',
            'FMB965',
            'FTC920',
            'FTC921',
            'FTC924',
            'FTC927',
            'FTC961',
            'FTC965',
            'FMB110',
            'FMB120',
            'FMB122',
            'FMB130',
            'FMC130',
            'FMM130',
            'FMM13A',
            'FMB202',
            'FMB204',
            'FMB209',
            'FMB230',
            'FMC230',
            'FMC234',
            'FMM230',
            'FTC134',
            'FTM134',
            'FMP100',
            'FMB010',
            'FMB020',
            'FMC800',
            'FMM800',
            'FMM80A',
            'FMT100',
            'FMC880',
            'FMM880',
            'FTC881',
            'FTC880',
            'FTM880',
            'FTC887',
            'FTM887',
            'FMB140',
            'FMB150',
            'FMC150',
            'FMM150',
            'FMB240',
            'FMC250',
            'FMM250',
            'FMB001',
            'FMB003',
            'FMC003',
            'FMM003',
            'FMM00A',
            'TST100',
            'TFT100',
            'FTC305',
            'FTM305',
            'FTC308',
            'FTM308',
            'FMC650',
            'FMM650',
            'FMB125',
            'FMC125',
            'FMM125',
            'FMB225',
            'FMC225',
            'TAT100',
            'TAT140',
            'TAT141',
            'TAT240',
            'GH5200',
            'ATC700',
            'ATM700',
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
        $canManageDevices = $request->user()->isSuperadmin() || $request->user()->isAdmin();

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

        $device->delete();

        return redirect()
            ->route('trackers.index')
            ->with('status', __('trackers.deleted'))
            ->with('status_type', 'danger');
    }

    private function authorizeDeviceManagement(Request $request, ?Device $device = null): void
    {
        $user = $request->user();

        abort_unless($user->isSuperadmin() || $user->isAdmin(), 403);

        if ($device === null || $user->isSuperadmin()) {
            return;
        }

        abort_unless($this->manageableVehicles($request)->contains('id', $device->vehicle_id), 403);
    }

    /**
     * @return array{
     *     vehicle_id: int,
     *     fleet_id: int|null,
     *     subscription_id: int|null,
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
        $data['subscription_id'] = $request->user()->isSuperadmin()
            ? $vehicle?->fleet?->managers()->whereNotNull('subscription_id')->value('subscription_id')
            : $request->user()->subscription_id;

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
}
