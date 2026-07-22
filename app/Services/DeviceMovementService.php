<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Position;
use Illuminate\Support\Collection;

class DeviceMovementService
{
    private const TRAIL_MAX_POINTS = 10;
    private const TRAIL_WINDOW_MINUTES = 10;
    private const TRAIL_MAX_SEGMENT_METERS = 600;
    private const TRAIL_MAX_TOTAL_METERS = 850;

    public function isMoving(Device $device): bool
    {
        if ($device->status !== 'online' || $device->last_ignition === false) {
            return false;
        }

        return $this->hasMovement($device);
    }

    public function isParking(Device $device): bool
    {
        return $this->isStopped($device) && $device->last_ignition === false;
    }

    public function isStationaryRunning(Device $device): bool
    {
        return $this->isStopped($device) && $device->last_ignition === true;
    }

    /**
     * @param  Collection<int, Device>  $devices
     * @return array<int, list<array{0: float, 1: float}>>
     */
    public function movementTrails(Collection $devices): array
    {
        $movingDevices = $devices
            ->filter(fn (Device $device): bool => $this->isMoving($device))
            ->keyBy('id');

        if ($movingDevices->isEmpty()) {
            return [];
        }

        return Position::query()
            ->whereIn('device_id', $movingDevices->keys())
            ->where('server_time', '>=', now()->subMinutes(self::TRAIL_WINDOW_MINUTES))
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('is_valid', true)
            ->orderBy('device_id')
            ->latest('server_time')
            ->get(['device_id', 'latitude', 'longitude', 'server_time'])
            ->groupBy('device_id')
            ->map(function (Collection $positions, int $deviceId) use ($movingDevices): array {
                $device = $movingDevices->get($deviceId);
                $coordinates = $positions
                    ->take(self::TRAIL_MAX_POINTS)
                    ->reverse()
                    ->map(fn (Position $position): array => [
                        (float) $position->longitude,
                        (float) $position->latitude,
                    ])
                    ->values()
                    ->all();

                $coordinates = array_reduce($coordinates, function (array $carry, array $coordinate): array {
                    if ($carry === [] || end($carry) !== $coordinate) {
                        $carry[] = $coordinate;
                    }

                    return $carry;
                }, []);

                $current = [
                    (float) $device->last_longitude,
                    (float) $device->last_latitude,
                ];

                if ($coordinates === [] || end($coordinates) !== $current) {
                    $coordinates[] = $current;
                }

                return $this->trimMovementTrail(
                    $this->recentContinuousTrail($coordinates)
                );
            })
            ->filter(fn (array $coordinates): bool => count($coordinates) > 1)
            ->all();
    }

    private function isStopped(Device $device): bool
    {
        if ($device->status !== 'online') {
            return false;
        }

        if ($device->last_ignition === false) {
            return true;
        }

        return ! $this->hasMovement($device);
    }

    private function hasMovement(Device $device): bool
    {
        return $device->last_movement !== null
            ? (bool) $device->last_movement
            : (int) $device->last_speed > 0;
    }

    private function recentContinuousTrail(array $coordinates): array
    {
        $trail = [];

        foreach ($coordinates as $coordinate) {
            if ($trail !== [] && $this->distanceInMeters(end($trail), $coordinate) > self::TRAIL_MAX_SEGMENT_METERS) {
                $trail = [];
            }

            if ($trail === [] || end($trail) !== $coordinate) {
                $trail[] = $coordinate;
            }
        }

        return $trail;
    }

    private function trimMovementTrail(array $coordinates): array
    {
        if (count($coordinates) < 2) {
            return $coordinates;
        }

        $trimmed = [array_pop($coordinates)];
        $distance = 0.0;

        for ($index = count($coordinates) - 1; $index >= 0; $index--) {
            $segmentDistance = $this->distanceInMeters($coordinates[$index], $trimmed[0]);

            if ($distance + $segmentDistance > self::TRAIL_MAX_TOTAL_METERS) {
                break;
            }

            array_unshift($trimmed, $coordinates[$index]);
            $distance += $segmentDistance;
        }

        return $trimmed;
    }

    private function distanceInMeters(array $first, array $second): float
    {
        $earthRadius = 6371000;
        $firstLatitude = deg2rad((float) $first[1]);
        $secondLatitude = deg2rad((float) $second[1]);
        $latitudeDelta = deg2rad((float) $second[1] - (float) $first[1]);
        $longitudeDelta = deg2rad((float) $second[0] - (float) $first[0]);

        $haversine = sin($latitudeDelta / 2) ** 2
            + cos($firstLatitude) * cos($secondLatitude) * sin($longitudeDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($haversine), sqrt(1 - $haversine));
    }
}
