<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DriverSession;
use App\Models\Position;
use Illuminate\Support\Facades\DB;

class DriverGeofenceService
{
    private const EARTH_RADIUS_METERS = 6371000;

    public function __construct(private readonly AlertService $alerts) {}

    public function evaluate(?DriverSession $session, Device $device, Position $position): void
    {
        if (! $session) {
            return;
        }

        $session->loadMissing(['driver', 'vehicle']);
        $driver = $session->driver;

        if (! $driver
            || $driver->location_latitude === null
            || $driver->location_longitude === null
            || $position->latitude === null
            || $position->longitude === null) {
            return;
        }

        $radius = max(10, (int) ($driver->location_radius_meters ?? 150));
        $distance = (int) round($this->distanceMeters(
            (float) $driver->location_latitude,
            (float) $driver->location_longitude,
            (float) $position->latitude,
            (float) $position->longitude,
        ));
        $status = $distance > $radius ? 'outside' : 'inside';

        $leftZone = DB::transaction(function () use ($session, $distance, $status): bool {
            $lockedSession = DriverSession::query()->lockForUpdate()->find($session->id);

            if (! $lockedSession || $lockedSession->status !== 'active') {
                return false;
            }

            $previousStatus = $lockedSession->geofence_status;
            $lockedSession->forceFill([
                'geofence_status' => $status,
                'geofence_distance_meters' => $distance,
                'geofence_updated_at' => now(),
            ])->save();

            return $status === 'outside' && $previousStatus !== 'outside';
        });

        if ($leftZone) {
            $this->alerts->createDriverGeofenceExitAlert($session, $device, $position, $distance, $radius);
        }
    }

    private function distanceMeters(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        $latitudeDelta = deg2rad($toLatitude - $fromLatitude);
        $longitudeDelta = deg2rad($toLongitude - $fromLongitude);
        $fromLatitude = deg2rad($fromLatitude);
        $toLatitude = deg2rad($toLatitude);

        $haversine = sin($latitudeDelta / 2) ** 2
            + cos($fromLatitude) * cos($toLatitude) * sin($longitudeDelta / 2) ** 2;

        return self::EARTH_RADIUS_METERS * 2 * atan2(sqrt($haversine), sqrt(1 - $haversine));
    }
}
