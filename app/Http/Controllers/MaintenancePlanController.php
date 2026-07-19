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
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MaintenancePlanController extends Controller
{
    public function index(MaintenanceService $maintenance): View
    {
        $maintenance->evaluateAll();

        $plans = MaintenancePlan::query()
            ->with(['vehicle.fleet:id,name,code', 'vehicle.device:id,vehicle_id,last_odometer_km,last_engine_seconds', 'garage:id,name', 'documents'])
            ->latest()
            ->get();
        $records = MaintenanceRecord::query()
            ->with(['vehicle:id,name,registration_number', 'garage:id,name', 'documents'])
            ->latest('performed_at')
            ->paginate(10, ['*'], 'history_page');

        return view('maintenance.index', [
            'plans' => $plans,
            'records' => $records,
            'vehicles' => Vehicle::query()->with(['fleet:id,name,code', 'device:id,vehicle_id,last_odometer_km,last_engine_seconds'])->orderBy('name')->get(),
            'garages' => Garage::query()->where('status', 'active')->orderBy('name')->get(),
            'stats' => [
                'active' => $plans->where('status', 'active')->count(),
                'due' => $plans->where('status', 'active')->whereIn('due_status', ['due', 'overdue'])->count(),
                'scheduled_cost' => $plans->where('status', 'active')->sum('estimated_cost'),
                'actual_cost' => MaintenanceRecord::query()->sum('actual_cost'),
            ],
            'expenseRecords' => MaintenanceRecord::query()->orderByDesc('performed_at')->get(['performed_at', 'actual_cost']),
        ]);
    }

    public function store(StoreMaintenancePlanRequest $request, MaintenanceService $maintenance): RedirectResponse
    {
        $data = $request->validated();
        $plan = DB::transaction(function () use ($data, $request): MaintenancePlan {
            return MaintenancePlan::query()->create([
                ...Arr::except($data, 'documents'),
                'created_by' => $request->user()->id,
                'status' => 'active',
                'due_status' => 'scheduled',
            ]);
        });
        $maintenance->storeDocuments($plan, $request->file('documents', []));
        $maintenance->evaluate($plan);

        return to_route('maintenance.index')->with('status', __('maintenance.created'));
    }

    public function update(UpdateMaintenancePlanRequest $request, MaintenancePlan $maintenancePlan, MaintenanceService $maintenance): RedirectResponse
    {
        $maintenancePlan->update(Arr::except($request->validated(), 'documents'));
        $maintenance->storeDocuments($maintenancePlan, $request->file('documents', []));
        $maintenance->evaluate($maintenancePlan->refresh());

        return to_route('maintenance.index')->with('status', __('maintenance.updated'));
    }

    public function complete(CompleteMaintenancePlanRequest $request, MaintenancePlan $maintenancePlan, MaintenanceService $maintenance): RedirectResponse
    {
        $maintenance->complete(
            $maintenancePlan,
            Arr::except($request->validated(), 'documents'),
            $request->user(),
            $request->file('documents', []),
        );

        return to_route('maintenance.index', ['tab' => 'history'])->with('status', __('maintenance.completed'));
    }

    public function toggle(MaintenancePlan $maintenancePlan): RedirectResponse
    {
        $status = $maintenancePlan->status === 'paused' ? 'active' : 'paused';
        $maintenancePlan->update(['status' => $status]);

        return to_route('maintenance.index')->with('status', __('maintenance.status_updated'));
    }

    public function destroy(MaintenancePlan $maintenancePlan, MaintenanceService $maintenance): RedirectResponse
    {
        $maintenance->deletePlan($maintenancePlan);

        return to_route('maintenance.index')->with('status', __('maintenance.deleted'))->with('status_type', 'danger');
    }
}
