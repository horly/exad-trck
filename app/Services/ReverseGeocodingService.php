<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReverseGeocodingService
{
    public function resolve(float $latitude, float $longitude): ?string
    {
        $token = (string) config('services.mapbox.public_token');

        if ($token === '') {
            return null;
        }

        try {
            $response = Http::timeout(5)
                ->retry(2, 200)
                ->get('https://api.mapbox.com/search/geocode/v6/reverse', [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'access_token' => $token,
                    'language' => app()->getLocale(),
                    'limit' => 1,
                    'types' => 'address,street,neighborhood,locality,place',
                    'permanent' => true,
                ]);

            if (! $response->successful()) {
                Log::warning('Mapbox reverse geocoding failed.', [
                    'status' => $response->status(),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);

                return null;
            }

            $feature = $response->json('features.0');

            if (! is_array($feature)) {
                return null;
            }

            $address = $feature['properties']['full_address']
                ?? $feature['properties']['name']
                ?? $feature['place_name']
                ?? null;

            return is_string($address) && trim($address) !== ''
                ? trim($address)
                : null;
        } catch (Throwable $exception) {
            Log::warning('Mapbox reverse geocoding exception.', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
