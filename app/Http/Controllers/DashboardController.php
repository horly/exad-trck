<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Position;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $devices = Device::query()
            ->withCount('positions')
            ->latest('last_seen_at')
            ->limit(8)
            ->get();

        $recentPositions = Position::query()
            ->with('device:id,imei,name,status')
            ->latest('server_time')
            ->limit(8)
            ->get();

        return view('dashboard', [
            'summary' => [
                'devices_total' => Device::query()->count(),
                'devices_online' => Device::query()->where('status', 'online')->count(),
                'devices_moving' => Device::query()->where('last_speed', '>', 0)->count(),
                'positions_today' => Position::query()->whereDate('server_time', today())->count(),
            ],
            'devices' => $devices,
            'recentPositions' => $recentPositions,
        ]);
    }
}
