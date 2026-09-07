<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DriverSession;

class CurrentDriverSessionService
{
    public function forDevice(Device $device): ?DriverSession
    {
        $vehicle = $device->vehicle;

        if ($device->vehicle_id === null || $vehicle === null) {
            return null;
        }

        return DriverSession::query()
            ->with([
                'driver.department:id,name,code',
                'identifier:id,driver_id,type,uid,active',
            ])
            ->where('device_id', $device->id)
            ->where('vehicle_id', $device->vehicle_id)
            ->where('status', 'active')
            ->whereNull('ended_at')
            ->whereHas('driver', fn ($query) => $query
                ->where('fleet_id', $vehicle->fleet_id)
                ->where('status', 'active'))
            ->whereHas('driver.vehicles', fn ($query) => $query->whereKey($device->vehicle_id))
            ->latest('started_at')
            ->latest('id')
            ->first();
    }
}
