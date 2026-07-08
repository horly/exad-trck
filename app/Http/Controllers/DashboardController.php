<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Position;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = request()->user();
        $period = $this->selectedPeriod();
        $periodWindow = $this->periodWindow($period);

        $devices = Device::query()
            ->visibleTo($user)
            ->with(['fleet:id,name', 'vehicle:id,name,registration_number'])
            ->withCount('positions')
            ->latest('last_seen_at')
            ->limit(8)
            ->get();

        $recentPositions = Position::query()
            ->with('device:id,imei,name,status')
            ->whereHas('device', fn ($query) => $query->visibleTo($user))
            ->latest('server_time')
            ->limit(8)
            ->get();

        $visibleDevices = Device::query()->visibleTo($user);
        $visibleVehicles = Vehicle::query()->visibleTo($user);
        $positionsForPeriod = Position::query()
            ->whereHas('device', fn ($query) => $query->visibleTo($user))
            ->whereBetween('server_time', [$periodWindow['start'], $periodWindow['end']])
            ->count();

        return view('dashboard', [
            'period' => $period,
            'periodWindow' => $periodWindow,
            'summary' => [
                'vehicles_total' => (clone $visibleVehicles)->count(),
                'devices_total' => (clone $visibleDevices)->count(),
                'devices_online' => (clone $visibleDevices)->where('status', 'online')->count(),
                'devices_offline' => (clone $visibleDevices)->whereIn('status', ['offline', 'inactive'])->count(),
                'devices_moving' => (clone $visibleDevices)->where('last_speed', '>', 0)->count(),
                'positions_period' => $positionsForPeriod,
            ],
            'devices' => $devices,
            'recentPositions' => $recentPositions,
            'dashboardCharts' => [
                'trend' => $this->positionsTrend($user, $periodWindow),
                'status' => $this->deviceStatusDistribution($user),
                'fleet' => $this->fleetDistribution($user),
                'health' => $this->signalHealth($user),
                'map' => $this->trackerMap($user),
                'emptyText' => __('dashboard.no_chart_data'),
                'period' => [
                    'key' => $period,
                    'label' => $periodWindow['label'],
                ],
                'labels' => [
                    'positions' => __('dashboard.positions_chart_series'),
                    'averageSpeed' => __('dashboard.average_speed_series'),
                    'total' => __('dashboard.total'),
                    'score' => __('dashboard.score'),
                    'trackers' => __('dashboard.devices'),
                    'online' => __('dashboard.online_devices'),
                    'moving' => __('dashboard.moving_devices'),
                    'worldMap' => __('dashboard.tracker_world_map'),
                ],
            ],
        ]);
    }

    /**
     * @return array{labels: list<string>, positions: list<int>, speed: list<float|int>}
     */
    private function positionsTrend($user, array $periodWindow): array
    {
        $points = $periodWindow['points'];

        $start = $periodWindow['start'];
        $end = $periodWindow['end'];
        $dateExpression = $periodWindow['group_expression'];

        $positions = Position::query()
            ->whereHas('device', fn ($query) => $query->visibleTo($user))
            ->whereBetween('server_time', [$start, $end])
            ->selectRaw($dateExpression.' as period_key, COUNT(*) as total, AVG(speed) as average_speed')
            ->groupByRaw($dateExpression)
            ->get()
            ->keyBy('period_key');

        return [
            'labels' => $points->map(fn (array $point): string => $point['label'])->values()->all(),
            'positions' => $points
                ->map(fn (array $point): int => (int) ($positions->get($point['key'])?->total ?? 0))
                ->values()
                ->all(),
            'speed' => $points
                ->map(fn (array $point): float => round((float) ($positions->get($point['key'])?->average_speed ?? 0), 1))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{labels: list<string>, series: list<int>}
     */
    private function deviceStatusDistribution($user): array
    {
        $counts = Device::query()
            ->visibleTo($user)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'labels' => [
                __('dashboard.status_online'),
                __('dashboard.status_inactive'),
                __('dashboard.status_offline'),
                __('dashboard.status_maintenance'),
            ],
            'series' => [
                (int) ($counts->get('online') ?? 0),
                (int) ($counts->get('inactive') ?? 0),
                (int) ($counts->get('offline') ?? 0),
                (int) ($counts->get('maintenance') ?? 0),
            ],
        ];
    }

    /**
     * @return array{labels: list<string>, series: list<int>}
     */
    private function fleetDistribution($user): array
    {
        $fleets = Device::query()
            ->visibleTo($user)
            ->leftJoin('fleets', 'devices.fleet_id', '=', 'fleets.id')
            ->selectRaw('COALESCE(fleets.name, ?) as label, COUNT(devices.id) as total', [__('dashboard.no_fleet')])
            ->groupBy('fleets.name')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        return [
            'labels' => $fleets->pluck('label')->values()->all(),
            'series' => $fleets->pluck('total')->map(fn ($total): int => (int) $total)->values()->all(),
        ];
    }

    /**
     * @return array{labels: list<string>, series: list<int>}
     */
    private function signalHealth($user): array
    {
        $deviceStats = Device::query()
            ->visibleTo($user)
            ->selectRaw('COUNT(*) as total, AVG(last_gsm_signal) as gsm_signal, AVG(last_satellites) as satellites, SUM(CASE WHEN last_seen_at IS NOT NULL THEN 1 ELSE 0 END) as reporting')
            ->first();

        $positionStats = Position::query()
            ->whereHas('device', fn ($query) => $query->visibleTo($user))
            ->where('server_time', '>=', now()->subDay())
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_valid = 1 THEN 1 ELSE 0 END) as valid_total')
            ->first();

        $totalDevices = max((int) ($deviceStats?->total ?? 0), 1);
        $totalPositions = max((int) ($positionStats?->total ?? 0), 1);

        return [
            'labels' => [
                __('dashboard.gsm_signal'),
                __('dashboard.gps_quality'),
                __('dashboard.reporting_devices'),
            ],
            'series' => [
                min(100, max(0, (int) round((float) ($deviceStats?->gsm_signal ?? 0)))),
                min(100, max(0, (int) round(((float) ($positionStats?->valid_total ?? 0) / $totalPositions) * 100))),
                min(100, max(0, (int) round(((float) ($deviceStats?->reporting ?? 0) / $totalDevices) * 100))),
            ],
        ];
    }

    private function selectedPeriod(): string
    {
        $period = request()->query('period', 'month');

        return in_array($period, ['week', 'month', 'year'], true) ? $period : 'month';
    }

    /**
     * @return array{start: Carbon, end: Carbon, label: string, points: Collection<int, array{key: string, label: string}>, group_expression: string}
     */
    private function periodWindow(string $period): array
    {
        $now = now();

        if ($period === 'week') {
            $points = collect(range(6, 0))->map(function (int $daysAgo) use ($now): array {
                $date = $now->copy()->startOfDay()->subDays($daysAgo);

                return [
                    'key' => $date->format('Y-m-d'),
                    'label' => $date->format('d/m'),
                ];
            });

            return [
                'start' => $points->first() ? Carbon::createFromFormat('Y-m-d', $points->first()['key'])->startOfDay() : $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfDay(),
                'label' => __('dashboard.week'),
                'points' => $points,
                'group_expression' => 'DATE(server_time)',
            ];
        }

        if ($period === 'year') {
            $points = collect(range(11, 0))->map(function (int $monthsAgo) use ($now): array {
                $date = $now->copy()->startOfMonth()->subMonths($monthsAgo);

                return [
                    'key' => $date->format('Y-m'),
                    'label' => $date->format('m/Y'),
                ];
            });

            return [
                'start' => $now->copy()->startOfMonth()->subMonths(11),
                'end' => $now->copy()->endOfDay(),
                'label' => __('dashboard.year'),
                'points' => $points,
                'group_expression' => "DATE_FORMAT(server_time, '%Y-%m')",
            ];
        }

        $points = collect(range(29, 0))->map(function (int $daysAgo) use ($now): array {
            $date = $now->copy()->startOfDay()->subDays($daysAgo);

            return [
                'key' => $date->format('Y-m-d'),
                'label' => $date->format('d/m'),
            ];
        });

        return [
            'start' => $points->first() ? Carbon::createFromFormat('Y-m-d', $points->first()['key'])->startOfDay() : $now->copy()->startOfMonth(),
            'end' => $now->copy()->endOfDay(),
            'label' => __('dashboard.month'),
            'points' => $points,
            'group_expression' => 'DATE(server_time)',
        ];
    }

    /**
     * @return array{clusters: list<array{label: string, country: string, latitude: float, longitude: float, total: int, online: int, moving: int, url: string}>, total: int}
     */
    private function trackerMap($user): array
    {
        $devices = Device::query()
            ->visibleTo($user)
            ->with(['vehicle:id,name,registration_number'])
            ->whereNotNull('last_latitude')
            ->whereNotNull('last_longitude')
            ->get(['id', 'vehicle_id', 'name', 'imei', 'status', 'last_speed', 'last_latitude', 'last_longitude', 'last_address']);

        $clusters = $devices
            ->groupBy(fn (Device $device): string => $this->clusterKey($device))
            ->map(function (Collection $devices): array {
                $first = $devices->first();
                $label = $this->cityLabel($first?->last_address);

                return [
                    'label' => $label,
                    'country' => $this->countryLabel($first?->last_address),
                    'latitude' => round((float) $devices->avg('last_latitude'), 5),
                    'longitude' => round((float) $devices->avg('last_longitude'), 5),
                    'total' => $devices->count(),
                    'online' => $devices->where('status', 'online')->count(),
                    'moving' => $devices->filter(fn (Device $device): bool => (int) $device->last_speed > 0)->count(),
                    'url' => route('map.index', [
                        'search' => $label,
                        'show' => 1,
                    ]),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();

        return [
            'clusters' => $clusters,
            'total' => $devices->count(),
        ];
    }

    private function clusterKey(Device $device): string
    {
        return Str::slug($this->cityLabel($device->last_address).'-'.$this->countryLabel($device->last_address))
            ?: sprintf('%s:%s', round((float) $device->last_latitude, 1), round((float) $device->last_longitude, 1));
    }

    private function cityLabel(?string $address): string
    {
        $parts = collect(explode(',', (string) $address))
            ->map(fn (string $part): string => trim($part))
            ->filter();

        foreach (['kinshasa', 'brazzaville', 'lubumbashi', 'matadi'] as $city) {
            $match = $parts->first(fn (string $part): bool => Str::contains(Str::lower($part), $city));

            if ($match !== null) {
                return Str::headline($match);
            }
        }

        foreach ($parts as $part) {
            if (Str::contains(Str::lower($part), ['gombe', 'ngaliema'])) {
                return Str::headline($part);
            }
        }

        return $parts->first() ?: __('dashboard.unknown_area');
    }

    private function countryLabel(?string $address): string
    {
        $parts = collect(explode(',', (string) $address))
            ->map(fn (string $part): string => trim($part))
            ->filter();

        return $parts->last() ?: __('dashboard.unknown_country');
    }
}
