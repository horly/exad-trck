<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AddressSearchService
{
    /**
     * @return list<array{address: string, latitude: float, longitude: float}>
     */
    public function search(string $query): array
    {
        $query = Str::of($query)->squish()->limit(180, '')->value();

        if (mb_strlen($query) < 3) {
            return [];
        }

        $provider = (string) config('services.maps.provider', 'google');
        $resolvers = $provider === 'mapbox'
            ? ['mapbox', 'google']
            : ['google', 'mapbox'];

        foreach ($resolvers as $resolver) {
            $results = $resolver === 'google'
                ? $this->searchGoogle($query)
                : $this->searchMapbox($query);

            if ($results !== []) {
                return $results;
            }
        }

        return [];
    }

    /** @return list<array{address: string, latitude: float, longitude: float}> */
    private function searchGoogle(string $query): array
    {
        $apiKey = (string) config('services.google_maps.api_key');

        if ($apiKey === '') {
            return [];
        }

        try {
            $response = Http::timeout(5)
                ->retry(2, 200)
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $query,
                    'key' => $apiKey,
                    'language' => app()->getLocale(),
                    'region' => 'cd',
                    'bounds' => '-4.65,15.05|-4.15,15.65',
                ]);

            if (! $response->successful() || $response->json('status') !== 'OK') {
                return [];
            }

            return collect($response->json('results', []))
                ->filter(fn (mixed $result): bool => is_array($result)
                    && is_string($result['formatted_address'] ?? null)
                    && is_numeric(data_get($result, 'geometry.location.lat'))
                    && is_numeric(data_get($result, 'geometry.location.lng')))
                ->map(fn (array $result): array => [
                    'address' => trim($result['formatted_address']),
                    'latitude' => (float) data_get($result, 'geometry.location.lat'),
                    'longitude' => (float) data_get($result, 'geometry.location.lng'),
                ])
                ->unique('address')
                ->take(5)
                ->values()
                ->all();
        } catch (Throwable $exception) {
            Log::warning('Google address search failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    /** @return list<array{address: string, latitude: float, longitude: float}> */
    private function searchMapbox(string $query): array
    {
        $token = (string) config('services.mapbox.public_token');

        if ($token === '') {
            return [];
        }

        try {
            $response = Http::timeout(5)
                ->retry(2, 200)
                ->get('https://api.mapbox.com/search/geocode/v6/forward', [
                    'q' => $query,
                    'access_token' => $token,
                    'language' => app()->getLocale(),
                    'country' => 'cd',
                    'proximity' => '15.2663,-4.4419',
                    'autocomplete' => 'true',
                    'limit' => 5,
                    'types' => 'address,poi,street,neighborhood,locality,place',
                ]);

            if (! $response->successful()) {
                return [];
            }

            return collect($response->json('features', []))
                ->filter(fn (mixed $feature): bool => is_array($feature)
                    && is_numeric(data_get($feature, 'geometry.coordinates.0'))
                    && is_numeric(data_get($feature, 'geometry.coordinates.1')))
                ->map(function (array $feature): array {
                    $address = data_get($feature, 'properties.full_address')
                        ?? data_get($feature, 'properties.place_formatted')
                        ?? data_get($feature, 'properties.name')
                        ?? data_get($feature, 'place_name');

                    return [
                        'address' => trim((string) $address),
                        'latitude' => (float) data_get($feature, 'geometry.coordinates.1'),
                        'longitude' => (float) data_get($feature, 'geometry.coordinates.0'),
                    ];
                })
                ->filter(fn (array $result): bool => $result['address'] !== '')
                ->unique('address')
                ->values()
                ->all();
        } catch (Throwable $exception) {
            Log::warning('Mapbox address search failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }
}
