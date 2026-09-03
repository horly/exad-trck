<?php

namespace App\Http\Controllers;

use App\Models\AlertRule;
use App\Models\Fleet;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VehicleController extends Controller
{
    /**
     * @var list<string>
     */
    private const VEHICLE_TYPES = [
        'passenger_car',
        'suv_4x4',
        'pickup',
        'fourgonnette',
        'camionnette',
        'van',
        'minibus',
        'truck',
        'bus_coach',
        'motorcycle',
        'tricycle',
        'tractor',
        'bulldozer',
        'excavator',
        'grader',
        'loader',
        'ambulance',
        'police_vehicle',
        'fire_truck',
        'tow_truck',
        'trailer',
    ];

    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $isDatatableRequest = $request->ajax();
        $sortableColumns = [
            'id' => 'vehicles.id',
            'name' => 'name',
            'registration_number' => 'registration_number',
            'fleet' => 'fleet_name',
            'vehicle_type' => 'vehicle_type',
            'status' => 'vehicles.status',
        ];
        $sort = $isDatatableRequest && array_key_exists((string) $request->query('sort'), $sortableColumns)
            ? (string) $request->query('sort')
            : null;
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $canManageVehicles = $request->user()->isSuperadmin();

        $vehicles = Vehicle::query()
            ->visibleTo($request->user())
            ->with(['fleet:id,name,code', 'device:id,vehicle_id,imei,status', 'speedPolicy:id,threshold_value,is_active'])
            ->select('vehicles.*')
            ->leftJoin('fleets', 'fleets.id', '=', 'vehicles.fleet_id')
            ->addSelect('fleets.name as fleet_name')
            ->when($search !== '', function ($query) use ($search, $request): void {
                $query->where(function ($query) use ($search, $request): void {
                    $query->where('vehicles.name', 'like', "%{$search}%")
                        ->orWhere('registration_number', 'like', "%{$search}%");

                    if ($request->user()->isSuperadmin()) {
                        $query
                            ->orWhere('brand', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%")
                            ->orWhere('vehicle_type', 'like', "%{$search}%")
                            ->orWhere('vehicles.status', 'like', "%{$search}%")
                            ->orWhere('fleets.name', 'like', "%{$search}%");
                    }
                });
            })
            ->when($sort !== null, function ($query) use ($sortableColumns, $sort, $direction): void {
                $query->orderBy($sortableColumns[$sort], $direction)->orderByDesc('vehicles.created_at')->orderByDesc('vehicles.id');
            }, function ($query): void {
                $query->orderByDesc('vehicles.created_at')->orderByDesc('vehicles.id');
            })
            ->paginate(5)
            ->withQueryString();

        $viewData = [
            'vehicles' => $vehicles,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'canManageVehicles' => $canManageVehicles,
            'manageableFleets' => $this->manageableFleets($request),
            'vehicleTypes' => self::VEHICLE_TYPES,
        ];

        if ($isDatatableRequest) {
            return response()->json([
                'html' => view('vehicles.partials.table', $viewData)->render(),
            ]);
        }

        return view('vehicles.index', $viewData);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeVehicleManagement($request);

        $data = $this->validatedVehicleData($request);
        $speedLimit = ($value = Arr::pull($data, 'speed_limit_kmh')) !== null ? (int) $value : null;
        $data['created_by'] = $request->user()->id;

        DB::transaction(function () use ($data, $speedLimit): void {
            $vehicle = Vehicle::query()->create($data);
            $this->syncSpeedPolicy($vehicle, $speedLimit);
        });

        return redirect()
            ->route('vehicles.index')
            ->with('status', __('vehicles.created'));
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicleManagement($request, $vehicle);

        $data = $this->validatedVehicleData($request, $vehicle);
        $speedLimit = ($value = Arr::pull($data, 'speed_limit_kmh')) !== null ? (int) $value : null;

        DB::transaction(function () use ($vehicle, $data, $speedLimit): void {
            $vehicle->update($data);
            $vehicle->device()->update(['fleet_id' => $vehicle->fleet_id]);
            $this->syncSpeedPolicy($vehicle, $speedLimit);
        });

        return redirect()
            ->route('vehicles.index')
            ->with('status', __('vehicles.updated'));
    }

    public function destroy(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicleManagement($request, $vehicle);

        DB::transaction(function () use ($vehicle): void {
            $policy = $vehicle->speedPolicy()->first();
            $vehicle->forceFill(['speed_policy_rule_id' => null])->save();
            $policy?->delete();
            $vehicle->delete();
        });

        return redirect()
            ->route('vehicles.index')
            ->with('status', __('vehicles.deleted'))
            ->with('status_type', 'danger');
    }

    private function authorizeVehicleManagement(Request $request, ?Vehicle $vehicle = null): void
    {
        $user = $request->user();

        abort_unless($user->isSuperadmin(), 403);

        if ($vehicle === null || $user->isSuperadmin()) {
            return;
        }

        abort_unless($this->manageableFleets($request)->contains('id', $vehicle->fleet_id), 403);
    }

    /**
     * @return array{
     *     fleet_id: int,
     *     name: string,
     *     registration_number: string,
     *     brand?: string|null,
     *     model?: string|null,
     *     color?: string|null,
     *     year?: int|null,
     *     vehicle_type: string,
     *     speed_limit_kmh?: int|null,
     *     status: string
     * }
     */
    private function validatedVehicleData(Request $request, ?Vehicle $vehicle = null): array
    {
        $manageableFleetIds = $this->manageableFleets($request)
            ->pluck('id')
            ->map(fn (int $id): string => (string) $id)
            ->all();

        return $request->validate([
            'fleet_id' => ['required', 'integer', Rule::in($manageableFleetIds)],
            'name' => ['required', 'string', 'max:255'],
            'registration_number' => [
                'required',
                'string',
                'max:40',
                Rule::unique('vehicles')
                    ->where('fleet_id', $request->integer('fleet_id'))
                    ->ignore($vehicle),
            ],
            'brand' => ['nullable', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'max:50'],
            'year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'vehicle_type' => ['required', Rule::in(self::VEHICLE_TYPES)],
            'speed_limit_kmh' => ['nullable', 'integer', 'min:1', 'max:300'],
            'status' => ['required', Rule::in(['active', 'inactive', 'maintenance'])],
        ]);
    }

    private function syncSpeedPolicy(Vehicle $vehicle, ?int $speedLimit): void
    {
        $policy = $vehicle->speedPolicy()->first();

        if ($speedLimit === null) {
            $vehicle->forceFill(['speed_policy_rule_id' => null])->save();
            $policy?->delete();

            return;
        }

        $attributes = [
            'vehicle_id' => $vehicle->id,
            'name' => __('vehicles.speed_policy_rule_name', ['vehicle' => $vehicle->name]),
            'type' => 'overspeed',
            'category' => AlertRule::CATEGORY_VEHICLE,
            'severity' => 'high',
            'scope_type' => AlertRule::SCOPE_VEHICLE,
            'threshold_value' => $speedLimit,
            'threshold_unit' => 'km/h',
            'channels' => ['platform'],
            'schedule_days' => [],
            'starts_at' => null,
            'ends_at' => null,
            'is_active' => true,
        ];

        if ($policy === null) {
            $policy = AlertRule::query()->create($attributes);
            $vehicle->forceFill(['speed_policy_rule_id' => $policy->id])->save();

            return;
        }

        $policy->update($attributes);
    }

    private function manageableFleets(Request $request)
    {
        $user = $request->user();

        return Fleet::query()
            ->when(! $user->isSuperadmin(), function ($query) use ($user): void {
                $query->whereHas('users', function ($query) use ($user): void {
                    $query
                        ->whereKey($user->id)
                        ->where('fleet_user.permission', 'manager');
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }
}
