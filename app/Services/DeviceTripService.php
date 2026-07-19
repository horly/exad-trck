<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Position;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DeviceTripService
{
    private const MAX_GAP_MINUTES = 15;
    private const MIN_TRIP_DISTANCE_KM = 0.05;
    private const TRIP_COLORS = [
        '#2563eb',
        '#7c3aed',
        '#0891b2',
        '#16a34a',
        '#ea580c',
        '#dc2626',
    ];

    public function __construct(
        private readonly ReverseGeocodingService $reverseGeocoding,
        private readonly GoogleRoadsService $googleRoads,
        private readonly LocationTimezoneService $locationTimezone,
    ) {
    }

    /**
     * @return array{
     *     trips: list<array<string, mixed>>,
     *     total_distance_km: float,
     *     total_duration_seconds: int,
     *     geojson: array<string, mixed>
     * }
     */
    public function build(Device $device, Carbon $from, Carbon $to): array
    {
        $positions = Position::query()
            ->where('device_id', $device->id)
            ->whereBetween('server_time', [$from, $to])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('server_time')
            ->orderBy('id')
            ->get([
                'id',
                'device_id',
                'gps_time',
                'server_time',
                'latitude',
                'longitude',
                'address',
                'speed',
                'movement',
                'raw_data',
            ]);

        $segments = $this->tripSegments($positions);
        $trips = [];

        foreach ($segments as $index => $segment) {
            $trips[] = $this->formatTrip($segment, $index + 1);
        }

        $totalDistance = round(array_sum(array_column($trips, 'distance_km')), 2);
        $totalDuration = (int) array_sum(array_column($trips, 'duration_seconds'));

        return [
            'trips' => $trips,
            'total_distance_km' => $totalDistance,
            'total_duration_seconds' => $totalDuration,
            'geojson' => [
                'type' => 'FeatureCollection',
                'features' => collect($trips)->map(fn (array $trip): array => [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'LineString',
                        'coordinates' => $trip['coordinates'],
                    ],
                    'properties' => [
                        'id' => $trip['id'],
                        'index' => $trip['index'],
                        'date' => $trip['date'],
                        'start_time' => $trip['start_time'],
                        'end_time' => $trip['end_time'],
                        'distance_km' => $trip['distance_km'],
                        'duration_seconds' => $trip['duration_seconds'],
                        'point_count' => $trip['point_count'],
                        'average_speed_kmh' => $trip['average_speed_kmh'],
                        'max_speed_kmh' => $trip['max_speed_kmh'],
                        'color' => $trip['color'],
                    ],
                ])->values()->all(),
            ],
        ];
    }

    /**
     * @param  Collection<int, Position>  $positions
     * @return list<Collection<int, Position>>
     */
    private function tripSegments(Collection $positions): array
    {
        $segments = [];
        $current = collect();
        $previous = null;
        $lastStop = null;

        foreach ($positions as $position) {
            if (
                $previous instanceof Position
                && $current->isNotEmpty()
                && $previous->server_time?->diffInMinutes($position->server_time) > self::MAX_GAP_MINUTES
            ) {
                $this->pushSegment($segments, $current);
                $current = collect();
            }

            if ($this->isMovingPosition($position)) {
                if ($current->isEmpty() && $lastStop instanceof Position) {
                    $current->push($lastStop);
                }

                $current->push($position);
                $previous = $position;
                continue;
            }

            if ($current->isNotEmpty()) {
                $current->push($position);
                $this->pushSegment($segments, $current);
                $current = collect();
            }

            $lastStop = $position;
            $previous = $position;
        }

        $this->pushSegment($segments, $current);

        return $segments;
    }

    /**
     * @param  list<Collection<int, Position>>  $segments
     * @param  Collection<int, Position>  $segment
     */
    private function pushSegment(array &$segments, Collection $segment): void
    {
        if ($segment->count() < 2 || ! $segment->contains(fn (Position $position): bool => $this->isMovingPosition($position))) {
            return;
        }

        if ($this->distanceFor($segment) < self::MIN_TRIP_DISTANCE_KM) {
            return;
        }

        $segments[] = $segment->values();
    }

    /**
     * @param  Collection<int, Position>  $positions
     * @return array<string, mixed>
     */
    private function formatTrip(Collection $positions, int $index): array
    {
        /** @var Position $start */
        $start = $positions->first();
        /** @var Position $end */
        $end = $positions->last();
        $distance = $this->distanceFor($positions);
        $startTimestamp = $this->timestampFor($start);
        $endTimestamp = $this->timestampFor($end);
        $startTime = $this->localTimeFor($start);
        $endTime = $this->localTimeFor($end);
        $duration = max(0, (int) $startTimestamp->diffInSeconds($endTimestamp));
        $movingSpeeds = $positions
            ->pluck('speed')
            ->filter(fn (mixed $speed): bool => is_numeric($speed) && (float) $speed >= 0)
            ->map(fn (mixed $speed): float => (float) $speed);
        $averageSpeed = $duration > 0
            ? round($distance / ($duration / 3600), 1)
            : round((float) ($movingSpeeds->avg() ?? 0), 1);
        $maxSpeed = round((float) ($movingSpeeds->max() ?? 0), 1);
        $color = self::TRIP_COLORS[($index - 1) % count(self::TRIP_COLORS)];

        return [
            'id' => sprintf('trip-%d', $index),
            'index' => $index,
            'date' => $startTime->format('d.m.Y'),
            'start_time' => $startTime->format('H:i'),
            'end_time' => $endTime->format('H:i'),
            'start_address' => $this->addressFor($start),
            'end_address' => $this->addressFor($end),
            'distance_km' => round($distance, 2),
            'distance_label' => __('trackers.trip_distance_value', ['distance' => number_format($distance, 2, '.', '')]),
            'duration_seconds' => $duration,
            'duration_label' => $this->durationLabel($duration),
            'point_count' => $positions->count(),
            'average_speed_kmh' => $averageSpeed,
            'max_speed_kmh' => $maxSpeed,
            'color' => $color,
            'coordinates' => $this->googleRoads->snap($positions),
        ];
    }

    private function isMovingPosition(Position $position): bool
    {
        return (bool) ($position->movement ?? ((int) $position->speed > 0));
    }

    private function timestampFor(Position $position): Carbon
    {
        return ($position->gps_time ?: $position->server_time ?: now())->copy();
    }

    private function localTimeFor(Position $position): Carbon
    {
        $timestamp = $this->timestampFor($position);

        return $timestamp->copy()->setTimezone($this->locationTimezone->forPosition($position, $timestamp));
    }

    /**
     * @param  Collection<int, Position>  $positions
     */
    private function distanceFor(Collection $positions): float
    {
        $distance = 0.0;
        $previous = null;

        foreach ($positions as $position) {
            if ($previous instanceof Position) {
                $distance += $this->haversine(
                    (float) $previous->latitude,
                    (float) $previous->longitude,
                    (float) $position->latitude,
                    (float) $position->longitude,
                );
            }

            $previous = $position;
        }

        return $distance;
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371.0;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function addressFor(Position $position): string
    {
        $payloadAddress = data_get($position->raw_data, 'payload.address');
        $currentAddress = $position->address ?: (is_string($payloadAddress) ? $payloadAddress : null);

        // Tracker payloads can keep an old address after coordinates change.
        // Resolve each trip boundary from its own coordinates first.
        $resolvedAddress = $this->reverseGeocoding->resolve(
            (float) $position->latitude,
            (float) $position->longitude,
        );

        $resolvedAddress ??= is_string($currentAddress) && trim($currentAddress) !== ''
            ? trim($currentAddress)
            : null;

        if ($resolvedAddress !== null) {
            if ($position->address !== $resolvedAddress) {
                $position->forceFill(['address' => $resolvedAddress])->save();
            }

            return $resolvedAddress;
        }

        return __('trackers.trip_coordinates_address', [
            'latitude' => $position->latitude,
            'longitude' => $position->longitude,
        ]);
    }

    public function durationLabel(int $seconds): string
    {
        if ($seconds < 60) {
            return __('trackers.duration_seconds', ['seconds' => $seconds]);
        }

        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        if ($remainingSeconds === 0) {
            return __('trackers.duration_minutes', ['minutes' => $minutes]);
        }

        return __('trackers.duration_minutes_seconds', [
            'minutes' => $minutes,
            'seconds' => $remainingSeconds,
        ]);
    }
}
