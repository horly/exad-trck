<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Alert;
use App\Models\Device;
use App\Models\Fleet;
use App\Models\Position;
use App\Models\ScheduledReport;
use App\Models\TrackerEvent;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * @var list<string>
     */
    private const TYPES = ['positions', 'events', 'alerts', 'fleet_summary'];

    /**
     * @var list<string>
     */
    private const PERIODS = ['week', 'month', 'year', 'custom'];

    public function index(Request $request): View|JsonResponse
    {
        $filters = $this->filters($request);
        $rows = $this->rows($request, $filters, true);
        $viewData = $this->viewData($request, $filters, $rows);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('reports.partials.table', $viewData)->render(),
            ]);
        }

        return view('reports.index', $viewData);
    }

    public function export(Request $request): Response|StreamedResponse
    {
        $filters = $this->filters($request);
        $showTechnicalDetails = $request->user()->isSuperadmin();
        $format = $request->query('format') === 'print' ? 'print' : 'csv';
        $rows = $this->rows($request, $filters, false);
        $columns = $this->columns($filters['type'], $showTechnicalDetails);

        if ($format === 'print') {
            $filename = 'exad-report-'.$filters['type'].'-'.now()->format('Ymd-His').'.pdf';

            return Pdf::loadView('reports.print', [
                'columns' => $columns,
                'filters' => $filters,
                'rows' => $rows,
                'title' => $this->typeLabel($filters['type']),
                'showTechnicalDetails' => $showTechnicalDetails,
            ])
                ->setPaper('a4', 'landscape')
                ->download($filename);
        }

        $filename = 'exad-report-'.$filters['type'].'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($columns, $rows, $showTechnicalDetails, $request): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, array_values($columns), ';');

            foreach ($rows as $row) {
                fputcsv($handle, $this->csvRow($row, $showTechnicalDetails, $request->user()), ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function storeSchedule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(self::TYPES)],
            'frequency' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
            'format' => ['required', Rule::in(['csv', 'print'])],
            'recipients' => ['nullable', 'string', 'max:500'],
            'period' => ['required', Rule::in(self::PERIODS)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'fleet_id' => ['nullable', 'integer', Rule::exists('fleets', 'id')],
            'vehicle_id' => ['nullable', 'integer', Rule::exists('vehicles', 'id')],
            'device_id' => ['nullable', 'integer', Rule::exists('devices', 'id')],
        ]);

        if (! $request->user()->isSuperadmin()) {
            $validated['fleet_id'] = null;
            $validated['device_id'] = null;

            if (! empty($validated['vehicle_id'])) {
                abort_unless(
                    Vehicle::query()->visibleTo($request->user())->whereKey($validated['vehicle_id'])->exists(),
                    403
                );
            }
        }

        ScheduledReport::query()->create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'frequency' => $validated['frequency'],
            'format' => $validated['format'],
            'filters' => collect($validated)->only(['period', 'date_from', 'date_to', 'fleet_id', 'vehicle_id', 'device_id'])->all(),
            'recipients' => collect(explode(',', (string) ($validated['recipients'] ?? '')))
                ->map(fn (string $email): string => trim($email))
                ->filter()
                ->values()
                ->all(),
            'is_active' => true,
            'next_run_at' => $this->nextRunAt($validated['frequency']),
        ]);

        return redirect()
            ->route('reports.index', ['type' => $validated['type'], 'period' => $validated['period']])
            ->with('status', __('reports.schedule_created'));
    }

    public function destroySchedule(ScheduledReport $scheduledReport): RedirectResponse
    {
        abort_unless($scheduledReport->user_id === request()->user()?->id, 403);

        $scheduledReport->delete();

        return redirect()
            ->route('reports.index')
            ->with('status', __('reports.schedule_deleted'))
            ->with('status_type', 'danger');
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        $type = in_array($request->query('type'), self::TYPES, true) ? (string) $request->query('type') : 'positions';
        $period = in_array($request->query('period'), self::PERIODS, true) ? (string) $request->query('period') : 'week';
        [$from, $to] = $this->dateWindow($request, $period);

        return [
            'type' => $type,
            'period' => $period,
            'date_from' => $from,
            'date_to' => $to,
            'fleet_id' => $request->user()->isSuperadmin() ? ($request->integer('fleet_id') ?: null) : null,
            'vehicle_id' => $request->integer('vehicle_id') ?: null,
            'device_id' => $request->user()->isSuperadmin() ? ($request->integer('device_id') ?: null) : null,
            'search' => trim((string) $request->query('search', '')),
            'sort' => (string) $request->query('sort', ''),
            'direction' => $request->query('direction') === 'asc' ? 'asc' : 'desc',
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function dateWindow(Request $request, string $period): array
    {
        if ($period === 'custom') {
            $from = $request->date('date_from')?->startOfDay() ?? now()->subDays(6)->startOfDay();
            $to = $request->date('date_to')?->endOfDay() ?? now()->endOfDay();

            return [$from, $to];
        }

        return match ($period) {
            'month' => [now()->startOfMonth(), now()->endOfDay()],
            'year' => [now()->startOfYear(), now()->endOfDay()],
            default => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
        };
    }

    private function rows(Request $request, array $filters, bool $paginate): mixed
    {
        return match ($filters['type']) {
            'events' => $this->eventRows($request, $filters, $paginate),
            'alerts' => $this->alertRows($request, $filters, $paginate),
            'fleet_summary' => $this->fleetRows($request, $filters, $paginate),
            default => $this->positionRows($request, $filters, $paginate),
        };
    }

    private function positionRows(Request $request, array $filters, bool $paginate): mixed
    {
        $showTechnicalDetails = $request->user()->isSuperadmin();
        $sortable = [
            'id' => 'positions.id',
            'vehicle' => 'vehicle_name',
            'fleet' => 'fleet_name',
            'speed' => 'positions.speed',
            'server_time' => 'positions.server_time',
        ];
        if ($showTechnicalDetails) {
            $sortable['tracker'] = 'device_name';
        }
        $sort = array_key_exists($filters['sort'], $sortable) ? $filters['sort'] : null;

        $query = Position::query()
            ->with(['device:id,fleet_id,vehicle_id,name,imei,model', 'device.fleet:id,name,code', 'device.vehicle:id,name,registration_number'])
            ->select('positions.*')
            ->leftJoin('devices', 'devices.id', '=', 'positions.device_id')
            ->leftJoin('fleets', 'fleets.id', '=', 'devices.fleet_id')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'devices.vehicle_id')
            ->addSelect('devices.name as device_name', 'fleets.name as fleet_name', 'vehicles.name as vehicle_name')
            ->whereBetween('positions.server_time', [$filters['date_from'], $filters['date_to']])
            ->whereHas('device', fn (Builder $query): Builder => $query->visibleTo($request->user()))
            ->when($filters['fleet_id'], fn ($query, $id) => $query->where('devices.fleet_id', $id))
            ->when($filters['vehicle_id'], fn ($query, $id) => $query->where('devices.vehicle_id', $id))
            ->when($filters['device_id'], fn ($query, $id) => $query->where('positions.device_id', $id))
            ->when($filters['search'] !== '', function ($query) use ($filters, $showTechnicalDetails): void {
                $search = $filters['search'];
                $query->where(function ($query) use ($search, $showTechnicalDetails): void {
                    $query
                        ->where('positions.address', 'like', "%{$search}%")
                        ->orWhere('fleets.name', 'like', "%{$search}%")
                        ->orWhere('vehicles.name', 'like', "%{$search}%")
                        ->orWhere('vehicles.registration_number', 'like', "%{$search}%");

                    if ($showTechnicalDetails) {
                        $query->orWhere('positions.imei', 'like', "%{$search}%")
                            ->orWhere('devices.name', 'like', "%{$search}%")
                            ->orWhere('devices.imei', 'like', "%{$search}%");
                    }
                });
            })
            ->when($sort, function ($query) use ($sortable, $sort, $filters): void {
                $query->orderBy($sortable[$sort], $filters['direction'])->orderByDesc('positions.id');
            }, fn ($query) => $query->orderByDesc('positions.server_time')->orderByDesc('positions.id'));

        return $paginate ? $query->paginate(5)->withQueryString() : $query->limit(5000)->get();
    }

    private function eventRows(Request $request, array $filters, bool $paginate): mixed
    {
        $showTechnicalDetails = $request->user()->isSuperadmin();
        $sortable = [
            'id' => 'tracker_events.id',
            'type' => 'tracker_events.type',
            'vehicle' => 'vehicle_name',
            'fleet' => 'fleet_name',
            'duration' => 'tracker_events.duration_seconds',
            'started_at' => 'tracker_events.started_at',
        ];
        if ($showTechnicalDetails) {
            $sortable['tracker'] = 'device_name';
        }
        $sort = array_key_exists($filters['sort'], $sortable) ? $filters['sort'] : null;

        $query = TrackerEvent::query()
            ->vehicleEvents()
            ->visibleTo($request->user())
            ->with(['fleet:id,name,code', 'vehicle:id,name,registration_number', 'device:id,name,imei,model'])
            ->select('tracker_events.*')
            ->leftJoin('fleets', 'fleets.id', '=', 'tracker_events.fleet_id')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'tracker_events.vehicle_id')
            ->leftJoin('devices', 'devices.id', '=', 'tracker_events.device_id')
            ->addSelect('fleets.name as fleet_name', 'vehicles.name as vehicle_name', 'devices.name as device_name')
            ->whereBetween('tracker_events.started_at', [$filters['date_from'], $filters['date_to']])
            ->when($filters['fleet_id'], fn ($query, $id) => $query->where('tracker_events.fleet_id', $id))
            ->when($filters['vehicle_id'], fn ($query, $id) => $query->where('tracker_events.vehicle_id', $id))
            ->when($filters['device_id'], fn ($query, $id) => $query->where('tracker_events.device_id', $id))
            ->when($filters['search'] !== '', function ($query) use ($filters, $showTechnicalDetails): void {
                $search = $filters['search'];
                $query->where(function ($query) use ($search, $showTechnicalDetails): void {
                    $query
                        ->where('tracker_events.title', 'like', "%{$search}%")
                        ->orWhere('tracker_events.message', 'like', "%{$search}%")
                        ->orWhere('tracker_events.type', 'like', "%{$search}%")
                        ->orWhere('fleets.name', 'like', "%{$search}%")
                        ->orWhere('vehicles.name', 'like', "%{$search}%")
                        ->orWhere('vehicles.registration_number', 'like', "%{$search}%");

                    if ($showTechnicalDetails) {
                        $query->orWhere('devices.name', 'like', "%{$search}%")
                            ->orWhere('devices.imei', 'like', "%{$search}%");
                    }
                });
            })
            ->when($sort, function ($query) use ($sortable, $sort, $filters): void {
                $query->orderBy($sortable[$sort], $filters['direction'])->orderByDesc('tracker_events.id');
            }, fn ($query) => $query->orderByDesc('tracker_events.started_at')->orderByDesc('tracker_events.id'));

        return $paginate ? $query->paginate(5)->withQueryString() : $query->limit(5000)->get();
    }

    private function alertRows(Request $request, array $filters, bool $paginate): mixed
    {
        $showTechnicalDetails = $request->user()->isSuperadmin();
        $sortable = [
            'id' => 'alerts.id',
            'type' => 'alerts.type',
            'severity' => 'alerts.severity',
            'vehicle' => 'vehicle_name',
            'fleet' => 'fleet_name',
            'status' => 'alerts.status',
            'occurred_at' => 'alerts.occurred_at',
        ];
        $sort = array_key_exists($filters['sort'], $sortable) ? $filters['sort'] : null;

        $query = Alert::query()
            ->visibleTo($request->user())
            ->with(['fleet:id,name,code', 'vehicle:id,name,registration_number', 'device:id,name,imei,model'])
            ->select('alerts.*')
            ->leftJoin('fleets', 'fleets.id', '=', 'alerts.fleet_id')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'alerts.vehicle_id')
            ->leftJoin('devices', 'devices.id', '=', 'alerts.device_id')
            ->addSelect('fleets.name as fleet_name', 'vehicles.name as vehicle_name', 'devices.name as device_name')
            ->whereBetween('alerts.occurred_at', [$filters['date_from'], $filters['date_to']])
            ->when($filters['fleet_id'], fn ($query, $id) => $query->where('alerts.fleet_id', $id))
            ->when($filters['vehicle_id'], fn ($query, $id) => $query->where('alerts.vehicle_id', $id))
            ->when($filters['device_id'], fn ($query, $id) => $query->where('alerts.device_id', $id))
            ->when($filters['search'] !== '', function ($query) use ($filters, $showTechnicalDetails): void {
                $search = $filters['search'];
                $query->where(function ($query) use ($search, $showTechnicalDetails): void {
                    $query
                        ->where('alerts.title', 'like', "%{$search}%")
                        ->orWhere('alerts.message', 'like', "%{$search}%")
                        ->orWhere('alerts.type', 'like', "%{$search}%")
                        ->orWhere('alerts.severity', 'like', "%{$search}%")
                        ->orWhere('alerts.status', 'like', "%{$search}%")
                        ->orWhere('fleets.name', 'like', "%{$search}%")
                        ->orWhere('vehicles.name', 'like', "%{$search}%")
                        ->orWhere('vehicles.registration_number', 'like', "%{$search}%");

                    if ($showTechnicalDetails) {
                        $query->orWhere('devices.name', 'like', "%{$search}%")
                            ->orWhere('devices.imei', 'like', "%{$search}%");
                    }
                });
            })
            ->when($sort, function ($query) use ($sortable, $sort, $filters): void {
                $query->orderBy($sortable[$sort], $filters['direction'])->orderByDesc('alerts.id');
            }, fn ($query) => $query->orderByRaw("CASE WHEN alerts.status = 'new' THEN 0 ELSE 1 END")->orderByDesc('alerts.occurred_at'));

        return $paginate ? $query->paginate(5)->withQueryString() : $query->limit(5000)->get();
    }

    private function fleetRows(Request $request, array $filters, bool $paginate): mixed
    {
        $showTechnicalDetails = $request->user()->isSuperadmin();
        $sortable = [
            'id' => 'fleets.id',
            'fleet' => 'fleets.name',
            'vehicles' => 'vehicles_count',
            'status' => 'fleets.status',
        ];
        if ($showTechnicalDetails) {
            $sortable['trackers'] = 'devices_count';
        }
        $sort = array_key_exists($filters['sort'], $sortable) ? $filters['sort'] : null;

        $query = Fleet::query()
            ->visibleTo($request->user())
            ->withCount([
                'vehicles',
                'devices',
                'devices as online_devices_count' => fn ($query) => $query->where('status', 'online'),
                'devices as offline_devices_count' => fn ($query) => $query->whereIn('status', ['offline', 'inactive']),
            ])
            ->when($filters['fleet_id'], fn ($query, $id) => $query->whereKey($id))
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('fleets.name', 'like', "%{$search}%")
                        ->orWhere('fleets.code', 'like', "%{$search}%")
                        ->orWhere('fleets.description', 'like', "%{$search}%")
                        ->orWhere('fleets.status', 'like', "%{$search}%");
                });
            })
            ->when($sort, function ($query) use ($sortable, $sort, $filters): void {
                $query->orderBy($sortable[$sort], $filters['direction'])->orderBy('fleets.name');
            }, fn ($query) => $query->orderBy('fleets.name'));

        return $paginate ? $query->paginate(5)->withQueryString() : $query->limit(5000)->get();
    }

    private function viewData(Request $request, array $filters, mixed $rows): array
    {
        $showTechnicalDetails = $request->user()->isSuperadmin();

        return [
            'rows' => $rows,
            'filters' => $filters,
            'columns' => $this->columns($filters['type'], $showTechnicalDetails),
            'typeOptions' => $this->typeOptions(),
            'periodOptions' => $this->periodOptions(),
            'summary' => $this->summary($request),
            'showTechnicalDetails' => $showTechnicalDetails,
            'fleets' => $showTechnicalDetails
                ? Fleet::query()->visibleTo($request->user())->orderBy('name')->get(['id', 'name', 'code'])
                : collect(),
            'vehicles' => Vehicle::query()->visibleTo($request->user())->orderBy('name')->get(['id', 'fleet_id', 'name', 'registration_number']),
            'devices' => $showTechnicalDetails
                ? Device::query()->visibleTo($request->user())->orderBy('name')->orderBy('imei')->get(['id', 'fleet_id', 'vehicle_id', 'name', 'imei'])
                : collect(),
            'scheduledReports' => ScheduledReport::query()
                ->where('user_id', $request->user()->id)
                ->latest()
                ->limit(6)
                ->get(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function summary(Request $request): array
    {
        return [
            'positions' => Position::query()->whereHas('device', fn (Builder $query): Builder => $query->visibleTo($request->user()))->count(),
            'events' => TrackerEvent::query()->vehicleEvents()->visibleTo($request->user())->count(),
            'alerts' => Alert::query()->visibleTo($request->user())->count(),
            'scheduled' => ScheduledReport::query()->where('user_id', $request->user()->id)->where('is_active', true)->count(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function columns(string $type, bool $showTechnicalDetails): array
    {
        return match ($type) {
            'events' => array_filter([
                'number' => __('reports.number'),
                'event' => __('reports.event'),
                'vehicle' => __('reports.vehicle'),
                'tracker' => $showTechnicalDetails ? __('reports.tracker') : null,
                'fleet' => __('reports.fleet'),
                'duration' => __('reports.duration'),
                'date' => __('reports.date'),
            ]),
            'alerts' => [
                'number' => __('reports.number'),
                'alert' => __('reports.alert'),
                'severity' => __('reports.severity'),
                'vehicle' => __('reports.vehicle'),
                'fleet' => __('reports.fleet'),
                'status' => __('reports.status'),
                'date' => __('reports.date'),
            ],
            'fleet_summary' => array_filter([
                'number' => __('reports.number'),
                'fleet' => __('reports.fleet'),
                'vehicles' => __('reports.vehicles'),
                'trackers' => $showTechnicalDetails ? __('reports.trackers') : null,
                'online' => $showTechnicalDetails ? __('reports.online') : null,
                'offline' => $showTechnicalDetails ? __('reports.offline') : null,
                'status' => __('reports.status'),
            ]),
            default => array_filter([
                'number' => __('reports.number'),
                'tracker' => $showTechnicalDetails ? __('reports.tracker') : null,
                'vehicle' => __('reports.vehicle'),
                'fleet' => __('reports.fleet'),
                'speed' => __('reports.speed'),
                'address' => __('reports.address'),
                'date' => __('reports.date'),
            ]),
        };
    }

    /**
     * @return array<string, string>
     */
    private function typeOptions(): array
    {
        return collect(self::TYPES)->mapWithKeys(fn (string $type): array => [$type => $this->typeLabel($type)])->all();
    }

    /**
     * @return array<string, string>
     */
    private function periodOptions(): array
    {
        return [
            'week' => __('reports.week'),
            'month' => __('reports.month'),
            'year' => __('reports.year'),
            'custom' => __('reports.custom_period'),
        ];
    }

    private function typeLabel(string $type): string
    {
        return __('reports.type_'.$type);
    }

    /**
     * @return list<string>
     */
    private function csvRow(mixed $row, bool $showTechnicalDetails, User $user): array
    {
        if ($row instanceof Position) {
            return array_values(array_filter([
                $row->id,
                $showTechnicalDetails ? ($row->device?->name ?: $row->imei) : null,
                $row->device?->vehicle?->name ?: '-',
                $row->device?->fleet?->name ?: '-',
                $row->speed ?? 0,
                $row->address ?: '-',
                $row->server_time?->format('Y-m-d H:i:s') ?: '-',
            ], fn (mixed $value): bool => $value !== null));
        }

        if ($row instanceof TrackerEvent) {
            return array_values(array_filter([
                $row->id,
                $row->localizedTitle().' - '.$row->localizedMessage(),
                $row->vehicle?->name ?: '-',
                $showTechnicalDetails ? ($row->device?->name ?: $row->device?->imei ?: '-') : null,
                $row->fleet?->name ?: '-',
                $row->durationLabel() ?: '-',
                $row->started_at?->format('Y-m-d H:i:s') ?: '-',
            ], fn (mixed $value): bool => $value !== null));
        }

        if ($row instanceof Alert) {
            return [
                $row->id,
                $row->localizedTitle().' - '.$row->localizedMessageFor($user),
                $row->severity,
                $row->vehicle?->name ?: '-',
                $row->fleet?->name ?: '-',
                $row->status,
                $row->occurred_at?->format('Y-m-d H:i:s') ?: '-',
            ];
        }

        return array_values(array_filter([
            $row->id,
            $row->name.' '.($row->code ? '('.$row->code.')' : ''),
            (string) $row->vehicles_count,
            $showTechnicalDetails ? (string) $row->devices_count : null,
            $showTechnicalDetails ? (string) $row->online_devices_count : null,
            $showTechnicalDetails ? (string) $row->offline_devices_count : null,
            $row->status,
        ], fn (mixed $value): bool => $value !== null));
    }

    private function nextRunAt(string $frequency): Carbon
    {
        return match ($frequency) {
            'daily' => now()->addDay()->startOfDay()->addHours(7),
            'monthly' => now()->addMonthNoOverflow()->startOfMonth()->addHours(7),
            default => now()->addWeek()->startOfWeek()->addHours(7),
        };
    }
}
