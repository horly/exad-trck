<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlertController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $isDatatableRequest = $request->ajax();
        $sortableColumns = [
            'id' => 'alerts.id',
            'type' => 'alerts.type',
            'severity' => 'alerts.severity',
            'status' => 'alerts.status',
            'vehicle' => 'vehicle_name',
            'fleet' => 'fleet_name',
            'occurred_at' => 'alerts.occurred_at',
        ];
        $sort = $isDatatableRequest && array_key_exists((string) $request->query('sort'), $sortableColumns)
            ? (string) $request->query('sort')
            : null;
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $alerts = Alert::query()
            ->visibleTo($request->user())
            ->with(['fleet:id,name,code', 'vehicle:id,name,registration_number', 'device:id,imei,name,status'])
            ->select('alerts.*')
            ->leftJoin('fleets', 'fleets.id', '=', 'alerts.fleet_id')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'alerts.vehicle_id')
            ->addSelect('fleets.name as fleet_name', 'vehicles.name as vehicle_name')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('alerts.title', 'like', "%{$search}%")
                        ->orWhere('alerts.message', 'like', "%{$search}%")
                        ->orWhere('alerts.type', 'like', "%{$search}%")
                        ->orWhere('alerts.severity', 'like', "%{$search}%")
                        ->orWhere('alerts.status', 'like', "%{$search}%")
                        ->orWhere('fleets.name', 'like', "%{$search}%")
                        ->orWhere('vehicles.name', 'like', "%{$search}%")
                        ->orWhere('vehicles.registration_number', 'like', "%{$search}%")
                        ->orWhereHas('device', function ($query) use ($search): void {
                            $query->where('imei', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($sort !== null, function ($query) use ($sortableColumns, $sort, $direction): void {
                $this->orderUnprocessedFirst($query)
                    ->orderBy($sortableColumns[$sort], $direction)
                    ->orderByDesc('alerts.occurred_at')
                    ->orderByDesc('alerts.id');
            }, function ($query): void {
                $this->orderUnprocessedFirst($query)
                    ->orderByDesc('alerts.occurred_at')
                    ->orderByDesc('alerts.id');
            })
            ->paginate(5)
            ->withQueryString();

        $stats = [
            'total' => Alert::query()->visibleTo($request->user())->count(),
            'new' => Alert::query()->visibleTo($request->user())->where('status', 'new')->count(),
            'critical' => Alert::query()->visibleTo($request->user())->where('severity', 'critical')->count(),
            'high' => Alert::query()->visibleTo($request->user())->where('severity', 'high')->count(),
        ];

        $viewData = [
            'alerts' => $alerts,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'stats' => $stats,
            'reverbConfig' => [
                'key' => config('broadcasting.connections.reverb.key'),
                'host' => config('broadcasting.connections.reverb.options.host'),
                'port' => config('broadcasting.connections.reverb.options.port'),
                'scheme' => config('broadcasting.connections.reverb.options.scheme'),
                'authEndpoint' => url('/broadcasting/auth'),
                'channel' => 'private-superadmin.alerts',
                'event' => 'alert.created',
            ],
        ];

        if ($isDatatableRequest) {
            return response()->json([
                'html' => view('alerts.partials.table', $viewData)->render(),
                'stats' => $stats,
            ]);
        }

        return view('alerts.index', $viewData);
    }

    private function orderUnprocessedFirst(Builder $query): Builder
    {
        return $query->orderByRaw(
            "CASE WHEN alerts.status IN ('acknowledged', 'resolved') THEN 1 ELSE 0 END"
        );
    }

    public function recent(Request $request): JsonResponse
    {
        $after = max(0, (int) $request->query('after', 0));

        $alerts = Alert::query()
            ->visibleTo($request->user())
            ->with(['fleet:id,name,code', 'vehicle:id,name,registration_number', 'device:id,imei,name,status'])
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit(10)
            ->get();

        return response()->json([
            'alerts' => $alerts->map(fn (Alert $alert): array => [
                'id' => $alert->id,
                'type' => $alert->type,
                'severity' => $alert->severity,
                'status' => $alert->status,
                'title' => $alert->localizedTitle(),
                'message' => $alert->localizedMessage(),
                'vehicle' => $alert->vehicle?->name,
                'fleet' => $alert->fleet?->name,
                'occurred_at' => $alert->occurred_at?->toDateTimeString(),
            ])->values(),
            'latest_id' => (int) ($alerts->last()?->id ?? $after),
        ]);
    }

    public function acknowledge(Request $request, Alert $alert): RedirectResponse
    {
        abort_unless(
            Alert::query()->whereKey($alert->id)->visibleTo($request->user())->exists(),
            403
        );

        $alert->forceFill([
            'status' => 'acknowledged',
            'acknowledged_by' => $request->user()->id,
            'acknowledged_at' => now(),
        ])->save();

        return redirect()
            ->route('alerts.index')
            ->with('status', __('alerts.acknowledged'));
    }
}
