<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\TrackerEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackerEventController extends Controller
{
    public function index(Request $request): View|JsonResponse|RedirectResponse
    {
        $search = trim((string) $request->query('search', ''));
        $deviceId = $request->integer('device') ?: null;
        $isDatatableRequest = $request->ajax();
        $selectedDevice = $deviceId !== null
            ? Device::query()
                ->visibleTo($request->user())
                ->with(['fleet:id,name,code', 'vehicle:id,fleet_id,name,registration_number'])
                ->find($deviceId)
            : null;

        if ($deviceId !== null && ! $selectedDevice) {
            if ($isDatatableRequest) {
                abort(404);
            }

            abort(404);
        }

        if (! $selectedDevice && $request->user()->isSuperadmin()) {
            return redirect()
                ->route('trackers.index')
                ->with('status', __('events.select_tracker'));
        }

        $eventScope = function ($query) use ($selectedDevice): void {
            if (! $selectedDevice) {
                return;
            }

            $selectedDevice->vehicle_id
                ? $query->where('tracker_events.vehicle_id', $selectedDevice->vehicle_id)
                : $query->where('tracker_events.device_id', $selectedDevice->id);
        };
        $baseEventScope = function ($query) use ($selectedDevice): void {
            if (! $selectedDevice) {
                return;
            }

            $selectedDevice->vehicle_id
                ? $query->where('vehicle_id', $selectedDevice->vehicle_id)
                : $query->where('device_id', $selectedDevice->id);
        };
        $sortableColumns = [
            'id' => 'tracker_events.id',
            'type' => 'tracker_events.type',
            'vehicle' => 'vehicle_name',
            'tracker' => 'device_name',
            'fleet' => 'fleet_name',
            'started_at' => 'tracker_events.started_at',
            'duration' => 'tracker_events.duration_seconds',
        ];
        $sort = $isDatatableRequest && array_key_exists((string) $request->query('sort'), $sortableColumns)
            ? (string) $request->query('sort')
            : null;
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $events = TrackerEvent::query()
            ->vehicleEvents()
            ->visibleTo($request->user())
            ->with(['fleet:id,name,code', 'vehicle:id,name,registration_number', 'device:id,imei,name,model,status'])
            ->select('tracker_events.*')
            ->leftJoin('fleets', 'fleets.id', '=', 'tracker_events.fleet_id')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'tracker_events.vehicle_id')
            ->leftJoin('devices', 'devices.id', '=', 'tracker_events.device_id')
            ->addSelect('fleets.name as fleet_name', 'vehicles.name as vehicle_name', 'devices.name as device_name')
            ->where($eventScope)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('tracker_events.title', 'like', "%{$search}%")
                        ->orWhere('tracker_events.message', 'like', "%{$search}%")
                        ->orWhere('tracker_events.type', 'like', "%{$search}%")
                        ->orWhere('fleets.name', 'like', "%{$search}%")
                        ->orWhere('vehicles.name', 'like', "%{$search}%")
                        ->orWhere('vehicles.registration_number', 'like', "%{$search}%")
                        ->orWhere('devices.imei', 'like', "%{$search}%")
                        ->orWhere('devices.name', 'like', "%{$search}%")
                        ->orWhere('devices.model', 'like', "%{$search}%");
                });
            })
            ->when($sort !== null, function ($query) use ($sortableColumns, $sort, $direction): void {
                $query
                    ->orderBy($sortableColumns[$sort], $direction)
                    ->orderByDesc('tracker_events.started_at')
                    ->orderByDesc('tracker_events.id');
            }, function ($query): void {
                $query
                    ->orderByDesc('tracker_events.started_at')
                    ->orderByDesc('tracker_events.id');
            })
            ->paginate(5)
            ->withQueryString();

        $baseQuery = TrackerEvent::query()
            ->vehicleEvents()
            ->visibleTo($request->user())
            ->where($baseEventScope);

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'today' => (clone $baseQuery)->whereDate('started_at', today())->count(),
            'movement' => (clone $baseQuery)->whereIn('type', ['movement_started', 'movement_stopped'])->count(),
            'security' => (clone $baseQuery)->whereIn('type', ['door_open', 'harsh_braking', 'harsh_acceleration', 'crash_detected', 'sos'])->count(),
        ];

        $viewData = [
            'events' => $events,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'deviceId' => $deviceId,
            'selectedDevice' => $selectedDevice,
            'showTechnicalDetails' => $request->user()->isSuperadmin(),
            'stats' => $stats,
        ];

        if ($isDatatableRequest) {
            return response()->json([
                'html' => view('events.partials.table', $viewData)->render(),
            ]);
        }

        return view('events.index', $viewData);
    }
}
