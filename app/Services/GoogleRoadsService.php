<?php

namespace App\Services;

use App\Models\Position;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleRoadsService
{
    private const CACHE_TTL_HOURS = 6;
    private const MAX_POINTS_PER_REQUEST = 100;

    /**
     * @param  Collection<int, Position>  $positions
     * @return list<array{0: float, 1: float}>
     */
    public function snap(Collection $positions): array
    {
        $coordinates = $this->rawCoordinates($positions);

        if (count($coordinates) < 2 || ! $this->isEnabled()) {
            return $coordinates;
        }

        $snapped = Cache::remember(
            $this->cacheKey($coordinates),
            now()->addHours(self::CACHE_TTL_HOURS),
            fn (): array => $this->snapRemote($coordinates) ?? $coordinates
        );

        return count($snapped) >= 2 ? $snapped : $coordinates;
    }

    /**
     * @return array{0: float, 1: float}|null
     */
    public function nearest(float $latitude, float $longitude): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        return Cache::remember(
            $this->nearestCacheKey($latitude, $longitude),
            now()->addHours(self::CACHE_TTL_HOURS),
            fn (): ?array => $this->nearestRemote($latitude, $longitude),
        );
    }

    /**
     * @param  Collection<int, Position>  $positions
     * @return list<array{0: float, 1: float}>
     */
    private function rawCoordinates(Collection $positions): array
    {
        return $positions
            ->filter(fn (Position $position): bool => $position->latitude !== null && $position->longitude !== null)
            ->map(fn (Position $position): array => [
                (float) $position->longitude,
                (float) $position->latitude,
            ])
            ->values()
            ->all();
    }

    private function isEnabled(): bool
    {
        return (bool) config('services.google_maps.roads_enabled', true)
            && (string) config('services.google_maps.api_key') !== '';
    }

    /**
     * @param  list<array{0: float, 1: float}>  $coordinates
     * @return list<array{0: float, 1: float}>|null
     */
    private function snapRemote(array $coordinates): ?array
    {
        $apiKey = (string) config('services.google_maps.api_key');
        $snapped = [];

        try {
            foreach ($this->chunksWithOverlap($coordinates) as $chunk) {
                $response = Http::timeout(8)
                    ->retry(2, 250)
                    ->get('https://roads.googleapis.com/v1/snapToRoads', [
                        'path' => collect($chunk)
                            ->map(fn (array $coordinate): string => $coordinate[1].','.$coordinate[0])
                            ->implode('|'),
                        'interpolate' => 'true',
                        'key' => $apiKey,
                    ]);

                if (! $response->successful()) {
                    Log::warning('Google Roads snap failed.', [
                        'status' => $response->status(),
                    ]);

                    return null;
                }

                foreach ($response->json('snappedPoints', []) as $point) {
                    $location = $point['location'] ?? null;

                    if (! is_array($location) || ! isset($location['latitude'], $location['longitude'])) {
                        continue;
                    }

                    $snapped[] = [
                        (float) $location['longitude'],
                        (float) $location['latitude'],
                    ];
                }
            }
        } catch (Throwable $exception) {
            Log::warning('Google Roads snap exception.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        return $this->deduplicateCoordinates($snapped);
    }

    /**
     * @return array{0: float, 1: float}|null
     */
    private function nearestRemote(float $latitude, float $longitude): ?array
    {
        $apiKey = (string) config('services.google_maps.api_key');

        try {
            $response = Http::timeout(5)
                ->retry(2, 250)
                ->get('https://roads.googleapis.com/v1/nearestRoads', [
                    'points' => $latitude.','.$longitude,
                    'key' => $apiKey,
                ]);

            if (! $response->successful()) {
                Log::warning('Google Roads nearest failed.', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            $location = $response->json('snappedPoints.0.location');

            if (! is_array($location) || ! isset($location['latitude'], $location['longitude'])) {
                return null;
            }

            return [
                (float) $location['longitude'],
                (float) $location['latitude'],
            ];
        } catch (Throwable $exception) {
            Log::warning('Google Roads nearest exception.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  list<array{0: float, 1: float}>  $coordinates
     * @return list<list<array{0: float, 1: float}>>
     */
    private function chunksWithOverlap(array $coordinates): array
    {
        if (count($coordinates) <= self::MAX_POINTS_PER_REQUEST) {
            return [$coordinates];
        }

        $chunks = [];
        $offset = 0;

        while ($offset < count($coordinates)) {
            $chunk = array_slice($coordinates, $offset, self::MAX_POINTS_PER_REQUEST);

            if ($offset > 0) {
                array_unshift($chunk, $coordinates[$offset - 1]);
            }

            $chunks[] = $chunk;
            $offset += self::MAX_POINTS_PER_REQUEST;
        }

        return $chunks;
    }

    /**
     * @param  list<array{0: float, 1: float}>  $coordinates
     * @return list<array{0: float, 1: float}>
     */
    private function deduplicateCoordinates(array $coordinates): array
    {
        $deduplicated = [];
        $previousKey = null;

        foreach ($coordinates as $coordinate) {
            $key = sprintf('%0.7f,%0.7f', $coordinate[0], $coordinate[1]);

            if ($key === $previousKey) {
                continue;
            }

            $deduplicated[] = $coordinate;
            $previousKey = $key;
        }

        return $deduplicated;
    }

    /**
     * @param  list<array{0: float, 1: float}>  $coordinates
     */
    private function cacheKey(array $coordinates): string
    {
        return 'google-roads:snap:v1:'.md5(json_encode($coordinates));
    }

    private function nearestCacheKey(float $latitude, float $longitude): string
    {
        return sprintf('google-roads:nearest:v1:%0.6f,%0.6f', $latitude, $longitude);
    }
}
