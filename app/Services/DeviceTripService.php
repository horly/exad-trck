<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Position;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DeviceTripService
{
    private const ENRICHMENT_BUDGET_SECONDS = 5.0;

    private const MAX_GAP_MINUTES = 15;

    private const STOP_SPLIT_MINUTES = 5;

    private const STOP_LEAD_IN_MAX_SECONDS = 60;

    private const MIN_TRIP_DISTANCE_KM = 0.05;

    private const TRIP_COLORS = [
        '#2563eb',
        '#7c3aed',
        '#0891b2',
        '#16a34a',
        '#ea580c',
        '#dc2626',
    ];

    private ?float $enrichmentDeadline = null;

    public function __construct(
        private readonly ReverseGeocodingService $reverseGeocoding,
        private readonly GoogleRoadsService $googleRoads,
        private readonly LocationTimezoneService $locationTimezone,
    ) {}

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
        $this->enrichmentDeadline = microtime(true) + self::ENRICHMENT_BUDGET_SECONDS;

        $positions = Position::query()
            ->where('device_id', $device->id)
            ->where(function ($query) use ($from, $to): void {
                $query
                    ->whereBetween('gps_time', [$from, $to])
                    ->orWhere(function ($fallbackQuery) use ($from, $to): void {
                        $fallbackQuery
                            ->whereNull('gps_time')
                            ->whereBetween('server_time', [$from, $to]);
                    });
            })
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderByRaw('COALESCE(gps_time, server_time)')
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

        $payload = [
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

        $this->enrichmentDeadline = null;

        return $payload;
    }

    /**
     * @param  Collection<int, Position>  $positions
     * @return list<Collection<int, Position>>
     */
    private function tripSegments(Collection $positions): array
    {
        $segments = [];
        $current = collect();
        $pendingStops = collect();
        $previous = null;
        $lastStop = null;

        foreach ($positions as $position) {
            if (
                $previous instanceof Position
                && $current->isNotEmpty()
                && $this->timestampFor($previous)->diffInMinutes($this->timestampFor($position)) > self::MAX_GAP_MINUTES
            ) {
                if ($pendingStops->isNotEmpty()) {
                    $current->push($pendingStops->first());
                }

                $this->pushSegment($segments, $current);
                $current = collect();
                $pendingStops = collect();
                $lastStop = null;
            }

            if ($this->isMovingPosition($position)) {
                if ($pendingStops->isNotEmpty()) {
                    /** @var Position $lastMovingPosition */
                    $lastMovingPosition = $current->last();

                    if ($this->stopDurationMinutes($lastMovingPosition, $pendingStops, $position) >= self::STOP_SPLIT_MINUTES) {
                        /** @var Position $segmentStart */
                        $segmentStart = $current->first();
                        $current->push($pendingStops->first());
                        $segmentCreated = $this->pushSegment($segments, $current);
                        $current = collect();
                        $lastStop = $segmentCreated ? $pendingStops->last() : $segmentStart;
                        $pendingStops = collect();
                    }
                }

                if ($current->isEmpty() && $lastStop instanceof Position) {
                    $current->push($lastStop);
                }

                if ($pendingStops->isNotEmpty()) {
                    $current = $current->merge($pendingStops);
                    $pendingStops = collect();
                }

                $current->push($position);
                $previous = $position;

                continue;
            }

            if ($current->isNotEmpty()) {
                $pendingStops->push($position);
                /** @var Position $lastMovingPosition */
                $lastMovingPosition = $current->last();

                if ($this->stopDurationMinutes($lastMovingPosition, $pendingStops) >= self::STOP_SPLIT_MINUTES) {
                    /** @var Position $segmentStart */
                    $segmentStart = $current->first();
                    $current->push($pendingStops->first());
                    $segmentCreated = $this->pushSegment($segments, $current);
                    $current = collect();
                    $pendingStops = collect();
                    $lastStop = $segmentCreated ? $position : $segmentStart;
                }
            } else {
                $lastStop = $position;
            }

            $previous = $position;
        }

        $current = $current->merge($pendingStops);
        $this->pushSegment($segments, $current);

        return $segments;
    }

    /**
     * @param  Collection<int, Position>  $stops
     */
    private function stopDurationMinutes(
        Position $lastMovingPosition,
        Collection $stops,
        ?Position $stopEnd = null,
    ): float {
        /** @var Position $firstStop */
        $firstStop = $stops->first();
        /** @var Position $lastStop */
        $lastStop = $stopEnd ?: $stops->last();
        $leadInSeconds = min(
            self::STOP_LEAD_IN_MAX_SECONDS,
            $this->timestampFor($lastMovingPosition)->diffInSeconds($this->timestampFor($firstStop)),
        );
        $stationarySeconds = $this->timestampFor($firstStop)->diffInSeconds($this->timestampFor($lastStop));

        return ($leadInSeconds + $stationarySeconds) / 60;
    }

    /**
     * @param  list<Collection<int, Position>>  $segments
     * @param  Collection<int, Position>  $segment
     */
    private function pushSegment(array &$segments, Collection $segment): bool
    {
        if ($segment->count() < 2 || ! $segment->contains(fn (Position $position): bool => $this->isMovingPosition($position))) {
            return false;
        }

        if ($this->distanceFor($segment) < self::MIN_TRIP_DISTANCE_KM) {
            return false;
        }

        $segments[] = $segment->values();

        return true;
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
        $startAddress = $this->addressFor($start);
        $endAddress = $this->addressFor($end);
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
            'start_address' => $startAddress,
            'end_address' => $endAddress,
            'distance_km' => round($distance, 2),
            'distance_label' => __('trackers.trip_distance_value', ['distance' => number_format($distance, 2, '.', '')]),
            'duration_seconds' => $duration,
            'duration_label' => $this->durationLabel($duration),
            'point_count' => $positions->count(),
            'average_speed_kmh' => $averageSpeed,
            'max_speed_kmh' => $maxSpeed,
            'color' => $color,
            'coordinates' => $this->canEnrich()
                ? $this->googleRoads->snap($positions)
                : $this->rawCoordinates($positions),
        ];
    }

    private function isMovingPosition(Position $position): bool
    {
        if ($position->speed !== null) {
            return (float) $position->speed > 0;
        }

        return (bool) $position->movement;
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

    private function canEnrich(): bool
    {
        return $this->enrichmentDeadline !== null
            && microtime(true) < $this->enrichmentDeadline;
    }

    /**
     * @param  Collection<int, Position>  $positions
     * @return list<array{0: float, 1: float}>
     */
    private function rawCoordinates(Collection $positions): array
    {
        return $positions
            ->map(fn (Position $position): array => [
                (float) $position->longitude,
                (float) $position->latitude,
            ])
            ->values()
            ->all();
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
