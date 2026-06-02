<?php

namespace App\Http\Controllers;

use Akaunting\Apexcharts\Chart;
use App\Models\Device;
use App\Models\Position;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = request()->user();

        $positionsChart = $this->positionsChart($user);
        $deviceStatusChart = $this->deviceStatusChart($user);

        $devices = Device::query()
            ->visibleTo($user)
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

        return view('dashboard', [
            'summary' => [
                'vehicles_total' => (clone $visibleVehicles)->count(),
                'devices_total' => (clone $visibleDevices)->count(),
                'devices_online' => (clone $visibleDevices)->where('status', 'online')->count(),
                'devices_moving' => (clone $visibleDevices)->where('last_speed', '>', 0)->count(),
                'positions_today' => Position::query()
                    ->whereHas('device', fn ($query) => $query->visibleTo($user))
                    ->whereDate('server_time', today())
                    ->count(),
            ],
            'devices' => $devices,
            'recentPositions' => $recentPositions,
            'positionsChart' => $positionsChart,
            'deviceStatusChart' => $deviceStatusChart,
        ]);
    }

    private function positionsChart($user): Chart
    {
        $days = collect(range(13, 0))
            ->map(fn (int $daysAgo): Carbon => now()->startOfDay()->subDays($daysAgo));

        $labels = $days->map(fn (Carbon $date): string => $date->format('d/m'))->all();

        $data = $days
            ->map(fn (Carbon $date): int => Position::query()
                ->whereHas('device', fn ($query) => $query->visibleTo($user))
                ->whereBetween('server_time', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
                ->count())
            ->all();

        return (new Chart)
            ->setType('area')
            ->setHeight(330)
            ->setColors(['#6d3df2'])
            ->setDataset(__('dashboard.positions_chart_series'), 'area', $data)
            ->setOptions([
                'chart' => [
                    'toolbar' => ['show' => false],
                    'zoom' => ['enabled' => false],
                    'fontFamily' => 'inherit',
                ],
                'stroke' => [
                    'curve' => 'smooth',
                    'width' => 4,
                ],
                'fill' => [
                    'type' => 'gradient',
                    'gradient' => [
                        'shadeIntensity' => 1,
                        'opacityFrom' => 0.22,
                        'opacityTo' => 0,
                    ],
                ],
                'grid' => [
                    'borderColor' => '#dce6f4',
                    'strokeDashArray' => 5,
                ],
                'xaxis' => [
                    'categories' => $labels,
                    'axisBorder' => ['show' => false],
                    'axisTicks' => ['show' => false],
                ],
                'yaxis' => [
                    'min' => 0,
                    'forceNiceScale' => true,
                ],
                'markers' => [
                    'size' => 0,
                    'hover' => ['size' => 6],
                ],
                'tooltip' => [
                    'theme' => 'light',
                ],
            ]);
    }

    private function deviceStatusChart($user): Chart
    {
        $series = [
            Device::query()->visibleTo($user)->where('status', 'online')->count(),
            Device::query()->visibleTo($user)->where('status', 'inactive')->count(),
            Device::query()->visibleTo($user)->where('status', 'offline')->count(),
            Device::query()->visibleTo($user)->where('status', 'maintenance')->count(),
        ];

        return (new Chart)
            ->setType('donut')
            ->setHeight(330)
            ->setLabels([
                __('dashboard.status_online'),
                __('dashboard.status_inactive'),
                __('dashboard.status_offline'),
                __('dashboard.status_maintenance'),
            ])
            ->setColors(['#2f67e8', '#94a3b8', '#7a35ed', '#f43f68'])
            ->setDataset(__('dashboard.status_chart_series'), 'donut', $series)
            ->setOptions([
                'chart' => [
                    'toolbar' => ['show' => false],
                    'fontFamily' => 'inherit',
                ],
                'legend' => [
                    'position' => 'bottom',
                    'fontWeight' => 700,
                ],
                'plotOptions' => [
                    'pie' => [
                        'donut' => [
                            'size' => '62%',
                        ],
                    ],
                ],
                'stroke' => [
                    'width' => 5,
                    'colors' => ['#ffffff'],
                ],
                'dataLabels' => [
                    'enabled' => false,
                ],
            ]);
    }
}
