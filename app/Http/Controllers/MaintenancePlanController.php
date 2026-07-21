<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteMaintenancePlanRequest;
use App\Http\Requests\StoreMaintenancePlanRequest;
use App\Http\Requests\UpdateMaintenancePlanRequest;
use App\Models\Garage;
use App\Models\MaintenancePlan;
use App\Models\MaintenanceRecord;
use App\Models\Vehicle;
use App\Services\MaintenanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MaintenancePlanController extends Controller
{
    public function index(Request $request, MaintenanceService $maintenance): View
    {
        $plans = MaintenancePlan::query()
            ->whereHas('vehicle', fn ($query) => $query->visibleTo($request->user()))
            ->with(['vehicle.fleet:id,name,code', 'vehicle.device:id,vehicle_id,last_odometer_km,last_engine_seconds', 'garage:id,name', 'documents'])
            ->latest()
            ->get();

        $plans->where('status', 'active')->each(fn (MaintenancePlan $plan) => $maintenance->evaluate($plan));

        $recordsQuery = MaintenanceRecord::query()
            ->whereHas('vehicle', fn ($query) => $query->visibleTo($request->user()));

        $records = (clone $recordsQuery)
            ->with(['vehicle:id,name,registration_number', 'garage:id,name', 'documents'])
            ->latest('performed_at')
            ->paginate(10, ['*'], 'history_page');

        return view('maintenance.index', [
            'plans' => $plans,
            'records' => $records,
            'vehicles' => Vehicle::query()
                ->visibleTo($request->user())
                ->with(['fleet:id,name,code', 'device:id,vehicle_id,last_odometer_km,last_engine_seconds'])
                ->orderBy('name')
                ->get(),
            'garages' => Garage::query()
                ->visibleTo($request->user())
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
            'stats' => [
                'active' => $plans->where('status', 'active')->count(),
                'due' => $plans->where('status', 'active')->whereIn('due_status', ['due', 'overdue'])->count(),
                'scheduled_cost' => $plans->where('status', 'active')->sum('estimated_cost'),
                'actual_cost' => (clone $recordsQuery)->sum('actual_cost'),
            ],
            'expenseRecords' => (clone $recordsQuery)->orderByDesc('performed_at')->get(['performed_at', 'actual_cost']),
        ]);
    }

    public function store(StoreMaintenancePlanRequest $request, MaintenanceService $maintenance): RedirectResponse
    {
        $data = $request->validated();
        $this->authorizeMaintenanceData($request, $data);

        $plan = DB::transaction(fn (): MaintenancePlan => MaintenancePlan::query()->create([
            ...Arr::except($data, 'documents'),
            'created_by' => $request->user()->id,
            'status' => 'active',
            'due_status' => 'scheduled',
        ]));

        $maintenance->storeDocuments($plan, $request->file('documents', []));
        $maintenance->evaluate($plan);

        return to_route('maintenance.index')->with('status', __('maintenance.created'));
    }

    public function update(UpdateMaintenancePlanRequest $request, MaintenancePlan $maintenancePlan, MaintenanceService $maintenance): RedirectResponse
    {
        $data = $request->validated();
        $this->authorizePlan($request, $maintenancePlan);
        $this->authorizeMaintenanceData($request, $data);

        $maintenancePlan->update(Arr::except($data, 'documents'));
        $maintenance->storeDocuments($maintenancePlan, $request->file('documents', []));
        $maintenance->evaluate($maintenancePlan->refresh());

        return to_route('maintenance.index')->with('status', __('maintenance.updated'));
    }

    public function complete(CompleteMaintenancePlanRequest $request, MaintenancePlan $maintenancePlan, MaintenanceService $maintenance): RedirectResponse
    {
        $data = $request->validated();
        $this->authorizePlan($request, $maintenancePlan);
        $this->authorizeGarageId($request, $data['garage_id'] ?? null);

        $maintenance->complete(
            $maintenancePlan,
            Arr::except($data, 'documents'),
            $request->user(),
            $request->file('documents', []),
        );

        return to_route('maintenance.index', ['tab' => 'history'])->with('status', __('maintenance.completed'));
    }

    public function toggle(Request $request, MaintenancePlan $maintenancePlan): RedirectResponse
    {
        $this->authorizePlan($request, $maintenancePlan);
        $status = $maintenancePlan->status === 'paused' ? 'active' : 'paused';
        $maintenancePlan->update(['status' => $status]);

        return to_route('maintenance.index')->with('status', __('maintenance.status_updated'));
    }

    public function destroy(Request $request, MaintenancePlan $maintenancePlan, MaintenanceService $maintenance): RedirectResponse
    {
        $this->authorizePlan($request, $maintenancePlan);
        $maintenance->deletePlan($maintenancePlan);

        return to_route('maintenance.index')->with('status', __('maintenance.deleted'))->with('status_type', 'danger');
    }

    /** @param array<string, mixed> $data */
    private function authorizeMaintenanceData(Request $request, array $data): void
    {
        abort_unless(
            Vehicle::query()->visibleTo($request->user())->whereKey($data['vehicle_id'])->exists(),
            403,
        );

        $this->authorizeGarageId($request, $data['garage_id'] ?? null);
    }

    private function authorizeGarageId(Request $request, mixed $garageId): void
    {
        if ($garageId === null || $garageId === '') {
            return;
        }

        abort_unless(
            Garage::query()->visibleTo($request->user())->whereKey($garageId)->exists(),
            403,
        );
    }

    private function authorizePlan(Request $request, MaintenancePlan $plan): void
    {
        abort_unless(
            MaintenancePlan::query()
                ->whereKey($plan->id)
                ->whereHas('vehicle', fn ($query) => $query->visibleTo($request->user()))
                ->exists(),
            403,
        );
    }
}
