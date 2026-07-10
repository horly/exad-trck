<?php

namespace App\Http\Controllers;

use App\Models\AlertRule;
use App\Models\Device;
use App\Models\Fleet;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AlertRuleController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $isDatatableRequest = $request->ajax();
        $sortableColumns = [
            'id' => 'alert_rules.id',
            'name' => 'alert_rules.name',
            'category' => 'alert_rules.category',
            'severity' => 'alert_rules.severity',
            'scope' => 'alert_rules.scope_type',
            'threshold' => 'alert_rules.threshold_value',
            'status' => 'alert_rules.is_active',
        ];
        $sort = $isDatatableRequest && array_key_exists((string) $request->query('sort'), $sortableColumns)
            ? (string) $request->query('sort')
            : null;
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $rules = AlertRule::query()
            ->visibleTo($request->user())
            ->with(['fleet:id,name,code', 'vehicle:id,name,registration_number', 'device:id,name,imei'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('alert_rules.name', 'like', "%{$search}%")
                        ->orWhere('alert_rules.type', 'like', "%{$search}%")
                        ->orWhere('alert_rules.category', 'like', "%{$search}%")
                        ->orWhere('alert_rules.severity', 'like', "%{$search}%")
                        ->orWhere('alert_rules.scope_type', 'like', "%{$search}%")
                        ->orWhereHas('fleet', fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                        ->orWhereHas('vehicle', fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('registration_number', 'like', "%{$search}%"))
                        ->orWhereHas('device', fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('imei', 'like', "%{$search}%"));
                });
            })
            ->when($sort !== null, function ($query) use ($sortableColumns, $sort, $direction): void {
                $query
                    ->orderBy($sortableColumns[$sort], $direction)
                    ->orderByDesc('alert_rules.is_active')
                    ->orderBy('alert_rules.name');
            }, function ($query): void {
                $query->ordered();
            })
            ->paginate(5)
            ->withQueryString();

        $baseQuery = AlertRule::query()->visibleTo($request->user());
        $viewData = [
            'rules' => $rules,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'active' => (clone $baseQuery)->active()->count(),
                'equipment' => (clone $baseQuery)->where('category', AlertRule::CATEGORY_EQUIPMENT)->count(),
                'vehicle' => (clone $baseQuery)->where('category', AlertRule::CATEGORY_VEHICLE)->count(),
            ],
            'fleets' => Fleet::query()->visibleTo($request->user())->orderBy('name')->get(['id', 'name', 'code']),
            'vehicles' => Vehicle::query()->visibleTo($request->user())->orderBy('name')->get(['id', 'fleet_id', 'name', 'registration_number']),
            'devices' => Device::query()->visibleTo($request->user())->orderBy('name')->orderBy('imei')->get(['id', 'fleet_id', 'vehicle_id', 'name', 'imei']),
            'typeGroups' => $this->typeGroups(),
            'severityOptions' => AlertRule::SEVERITIES,
            'channelOptions' => AlertRule::CHANNELS,
            'scheduleDays' => AlertRule::SCHEDULE_DAYS,
        ];

        if ($isDatatableRequest) {
            return response()->json([
                'html' => view('alert-rules.partials.table', $viewData)->render(),
                'stats' => $viewData['stats'],
            ]);
        }

        return view('alert-rules.index', $viewData);
    }

    public function store(Request $request): RedirectResponse
    {
        AlertRule::query()->create($this->validatedRule($request));

        return redirect()
            ->route('alert-rules.index')
            ->with('status', __('alert_rules.created'));
    }

    public function update(Request $request, AlertRule $alertRule): RedirectResponse
    {
        $alertRule->update($this->validatedRule($request));

        return redirect()
            ->route('alert-rules.index')
            ->with('status', __('alert_rules.updated'));
    }

    public function destroy(AlertRule $alertRule): RedirectResponse
    {
        $alertRule->delete();

        return redirect()
            ->route('alert-rules.index')
            ->with('status', __('alert_rules.deleted'))
            ->with('status_type', 'danger');
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function typeGroups(): array
    {
        return [
            AlertRule::CATEGORY_EQUIPMENT => [
                'no_signal' => __('alert_rules.type_no_signal'),
                'weak_gsm' => __('alert_rules.type_weak_gsm'),
                'low_battery' => __('alert_rules.type_low_battery'),
                'external_power_cut' => __('alert_rules.type_external_power_cut'),
                'obd_disconnected' => __('alert_rules.type_obd_disconnected'),
                'jamming' => __('alert_rules.type_jamming'),
            ],
            AlertRule::CATEGORY_VEHICLE => [
                'overspeed' => __('alert_rules.type_overspeed'),
                'idle_engine_on' => __('alert_rules.type_idle_engine_on'),
                'door_open' => __('alert_rules.type_door_open'),
                'harsh_braking' => __('alert_rules.type_harsh_braking'),
                'harsh_acceleration' => __('alert_rules.type_harsh_acceleration'),
                'crash_detected' => __('alert_rules.type_crash_detected'),
                'sos' => __('alert_rules.type_sos'),
                'geofence_exit' => __('alert_rules.type_geofence_exit'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedRule(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'string', Rule::in($this->allowedTypes())],
            'category' => ['required', Rule::in([AlertRule::CATEGORY_EQUIPMENT, AlertRule::CATEGORY_VEHICLE])],
            'severity' => ['required', Rule::in(AlertRule::SEVERITIES)],
            'scope_type' => ['required', Rule::in([AlertRule::SCOPE_ALL, AlertRule::SCOPE_FLEET, AlertRule::SCOPE_VEHICLE, AlertRule::SCOPE_DEVICE])],
            'fleet_id' => ['nullable', 'required_if:scope_type,'.AlertRule::SCOPE_FLEET, Rule::exists('fleets', 'id')],
            'vehicle_id' => ['nullable', 'required_if:scope_type,'.AlertRule::SCOPE_VEHICLE, Rule::exists('vehicles', 'id')],
            'device_id' => ['nullable', 'required_if:scope_type,'.AlertRule::SCOPE_DEVICE, Rule::exists('devices', 'id')],
            'threshold_value' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'threshold_unit' => ['nullable', 'string', 'max:30'],
            'channels' => ['nullable', 'array'],
            'channels.*' => ['string', Rule::in(AlertRule::CHANNELS)],
            'schedule_days' => ['nullable', 'array'],
            'schedule_days.*' => ['string', Rule::in(AlertRule::SCHEDULE_DAYS)],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['category'] = $this->categoryForType($validated['type']) ?? $validated['category'];
        $validated['channels'] = array_values($validated['channels'] ?? ['platform']);
        $validated['schedule_days'] = array_values($validated['schedule_days'] ?? []);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['fleet_id'] = $validated['scope_type'] === AlertRule::SCOPE_FLEET ? $validated['fleet_id'] : null;
        $validated['vehicle_id'] = $validated['scope_type'] === AlertRule::SCOPE_VEHICLE ? $validated['vehicle_id'] : null;
        $validated['device_id'] = $validated['scope_type'] === AlertRule::SCOPE_DEVICE ? $validated['device_id'] : null;

        return $validated;
    }

    /**
     * @return list<string>
     */
    private function allowedTypes(): array
    {
        return collect($this->typeGroups())
            ->flatMap(fn (array $types): array => array_keys($types))
            ->values()
            ->all();
    }

    private function categoryForType(string $type): ?string
    {
        foreach ($this->typeGroups() as $category => $types) {
            if (array_key_exists($type, $types)) {
                return $category;
            }
        }

        return null;
    }
}
