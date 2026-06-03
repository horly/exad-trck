<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Position;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DeviceTripService
{
    private const MAX_GAP_MINUTES = 15;

    public function __construct(private readonly ReverseGeocodingService $reverseGeocoding)
    {
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
            ->get();

        $segments = $this->movingSegments($positions);
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
                        'index' => $trip['index'],
                        'distance_km' => $trip['distance_km'],
                        'duration_seconds' => $trip['duration_seconds'],
                    ],
                ])->values()->all(),
            ],
        ];
    }

    /**
     * @param  Collection<int, Position>  $positions
     * @return list<Collection<int, Position>>
     */
    private function movingSegments(Collection $positions): array
    {
        $segments = [];
        $current = collect();
        $previous = null;

        foreach ($positions as $position) {
            $isMoving = $position->movement ?? ((int) $position->speed > 0);

            if (! $isMoving) {
                $this->pushSegment($segments, $current);
                $current = collect();
                $previous = $position;
                continue;
            }

            if (
                $previous instanceof Position
                && $current->isNotEmpty()
                && $previous->server_time?->diffInMinutes($position->server_time) > self::MAX_GAP_MINUTES
            ) {
                $this->pushSegment($segments, $current);
                $current = collect();
            }

            $current->push($position);
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
        if ($segment->count() < 2) {
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
        $duration = max(0, (int) $start->server_time->diffInSeconds($end->server_time));

        return [
            'index' => $index,
            'date' => $start->server_time->format('d.m.Y'),
            'start_time' => $start->server_time->format('H:i'),
            'end_time' => $end->server_time->format('H:i'),
            'start_address' => $this->addressFor($start),
            'end_address' => $this->addressFor($end),
            'distance_km' => round($distance, 2),
            'distance_label' => __('trackers.trip_distance_value', ['distance' => number_format($distance, 2, '.', '')]),
            'duration_seconds' => $duration,
            'duration_label' => $this->durationLabel($duration),
            'coordinates' => $positions
                ->map(fn (Position $position): array => [
                    (float) $position->longitude,
                    (float) $position->latitude,
                ])
                ->values()
                ->all(),
        ];
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
        if ($position->address) {
            return $position->address;
        }

        $payloadAddress = data_get($position->raw_data, 'payload.address');

        if (is_string($payloadAddress) && $payloadAddress !== '') {
            return $payloadAddress;
        }

        $resolvedAddress = $this->reverseGeocoding->resolve(
            (float) $position->latitude,
            (float) $position->longitude,
        );

        if ($resolvedAddress !== null) {
            $position->forceFill(['address' => $resolvedAddress])->save();

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
