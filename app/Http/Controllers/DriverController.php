<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDriverRequest;
use App\Http\Requests\UpdateDriverRequest;
use App\Models\Department;
use App\Models\Driver;
use App\Models\Fleet;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DriverController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $canManageDrivers = $user->isSuperadmin();
        $search = trim((string) $request->query('search', ''));
        $isAjax = $request->ajax();
        $columns = [
            'id' => 'drivers.id',
            'name' => 'drivers.first_name',
            'employee_id' => 'drivers.employee_id',
            'fleet' => 'fleet_name',
            'department' => 'department_name',
            'status' => 'drivers.status',
        ];
        $sort = $isAjax && isset($columns[(string) $request->query('sort')])
            ? (string) $request->query('sort')
            : null;
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $drivers = Driver::query()
            ->visibleTo($user)
            ->with([
                'fleet:id,name,code',
                'department:id,name,code',
                ...($canManageDrivers ? [
                    'primaryIdentifier:driver_identifiers.id,driver_identifiers.driver_id,driver_identifiers.type,driver_identifiers.uid,driver_identifiers.active',
                ] : []),
                'vehicles' => fn ($query) => $query
                    ->visibleTo($user)
                    ->select(['vehicles.id', 'vehicles.name', 'vehicles.registration_number']),
            ])
            ->select('drivers.*')
            ->leftJoin('fleets', 'fleets.id', '=', 'drivers.fleet_id')
            ->leftJoin('departments', 'departments.id', '=', 'drivers.department_id')
            ->addSelect('fleets.name as fleet_name', 'departments.name as department_name')
            ->when($search !== '', function ($query) use ($search, $canManageDrivers): void {
                $query->where(function ($query) use ($search, $canManageDrivers): void {
                    $query->where('drivers.first_name', 'like', "%{$search}%")
                        ->orWhere('drivers.middle_name', 'like', "%{$search}%")
                        ->orWhere('drivers.last_name', 'like', "%{$search}%")
                        ->orWhere('drivers.employee_id', 'like', "%{$search}%")
                        ->orWhere('drivers.phone', 'like', "%{$search}%")
                        ->orWhere('drivers.email', 'like', "%{$search}%")
                        ->orWhere('fleets.name', 'like', "%{$search}%")
                        ->orWhere('departments.name', 'like', "%{$search}%");

                    if ($canManageDrivers) {
                        $query->orWhereHas('identifiers', fn ($query) => $query->where('uid', 'like', "%{$search}%"));
                    }
                });
            })
            ->when($sort, fn ($query) => $query->orderBy($columns[$sort], $direction)->orderByDesc('drivers.id'),
                fn ($query) => $query->latest('drivers.created_at')->latest('drivers.id'))
            ->paginate(5)
            ->withQueryString();

        $data = [
            'drivers' => $drivers,
            'fleets' => $canManageDrivers ? Fleet::query()->orderBy('name')->get(['id', 'name', 'code']) : collect(),
            'departmentsForForm' => $canManageDrivers ? Department::query()->orderBy('name')->get(['id', 'fleet_id', 'name', 'code']) : collect(),
            'vehiclesForForm' => $canManageDrivers ? Vehicle::query()->orderBy('name')->get(['id', 'fleet_id', 'name', 'registration_number']) : collect(),
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'canManageDrivers' => $canManageDrivers,
        ];

        if ($isAjax) {
            return response()->json(['html' => view('drivers.partials.table', $data)->render()]);
        }

        return view('drivers.index', $data);
    }

    public function store(StoreDriverRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $data): void {
            $driverData = Arr::except($data, ['rfid_uid', 'identifier_type', 'authorized_vehicle_ids', 'photo']);

            if ($request->hasFile('photo')) {
                $driverData['photo_path'] = $request->file('photo')->store('drivers', 'public');
            }

            $driver = Driver::query()->create($driverData);
            $driver->vehicles()->sync($data['authorized_vehicle_ids'] ?? []);
            $this->syncIdentifier($driver, $data['rfid_uid'] ?? null, $data['identifier_type']);
        });

        return to_route('drivers.index')->with('status', __('drivers.created'));
    }

    public function update(UpdateDriverRequest $request, Driver $driver): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $driver, $data): void {
            $driverData = Arr::except($data, ['rfid_uid', 'identifier_type', 'authorized_vehicle_ids', 'photo']);

            if ($request->hasFile('photo')) {
                if ($driver->photo_path) {
                    Storage::disk('public')->delete($driver->photo_path);
                }
                $driverData['photo_path'] = $request->file('photo')->store('drivers', 'public');
            }

            $driver->update($driverData);
            $driver->vehicles()->sync($data['authorized_vehicle_ids'] ?? []);
            $this->syncIdentifier($driver, $data['rfid_uid'] ?? null, $data['identifier_type']);
        });

        return to_route('drivers.index')->with('status', __('drivers.updated'));
    }

    public function destroy(Driver $driver): RedirectResponse
    {
        DB::transaction(function () use ($driver): void {
            if ($driver->photo_path) {
                Storage::disk('public')->delete($driver->photo_path);
            }
            $driver->delete();
        });

        return to_route('drivers.index')
            ->with('status', __('drivers.deleted'))
            ->with('status_type', 'danger');
    }

    private function syncIdentifier(Driver $driver, ?string $uid, string $type): void
    {
        if (! $uid) {
            $driver->identifiers()->where('active', true)->update(['active' => false]);

            return;
        }

        $identifier = $driver->identifiers()->where('uid', $uid)->first();

        $driver->identifiers()
            ->where('active', true)
            ->when($identifier, fn ($query) => $query->where('id', '!=', $identifier->id))
            ->update(['active' => false]);

        if ($identifier) {
            $identifier->update(['type' => $type, 'active' => true]);

            return;
        }

        $driver->identifiers()->create(['uid' => $uid, 'type' => $type, 'active' => true]);
    }
}
