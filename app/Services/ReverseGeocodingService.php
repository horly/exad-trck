<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ReverseGeocodingService
{
    private const CACHE_TTL_DAYS = 30;

    public function resolve(float $latitude, float $longitude): ?string
    {
        return $this->resolveBest($latitude, $longitude);
    }

    public function resolveBest(float $latitude, float $longitude, ?string $currentAddress = null): ?string
    {
        $currentAddress = $this->cleanAddress($currentAddress);

        if ($currentAddress !== null && $this->isDetailedEnough($currentAddress)) {
            return $currentAddress;
        }

        $resolvedAddress = Cache::remember(
            $this->cacheKey($latitude, $longitude),
            now()->addDays(self::CACHE_TTL_DAYS),
            fn (): ?string => $this->resolveRemote($latitude, $longitude)
        );

        if ($resolvedAddress === null) {
            return $currentAddress;
        }

        if ($currentAddress === null || $this->addressScore($resolvedAddress) >= $this->addressScore($currentAddress)) {
            return $resolvedAddress;
        }

        return $currentAddress;
    }

    private function resolveRemote(float $latitude, float $longitude): ?string
    {
        $provider = (string) config('services.maps.provider', 'google');
        $resolvers = $provider === 'mapbox'
            ? ['mapbox', 'google']
            : ['google', 'mapbox'];

        foreach ($resolvers as $resolver) {
            $address = $resolver === 'google'
                ? $this->resolveWithGoogle($latitude, $longitude)
                : $this->resolveWithMapbox($latitude, $longitude);

            if ($address !== null) {
                return $address;
            }
        }

        return null;
    }

    private function resolveWithGoogle(float $latitude, float $longitude): ?string
    {
        $apiKey = (string) config('services.google_maps.api_key');

        if ($apiKey === '') {
            return null;
        }

        try {
            $response = Http::timeout(5)
                ->retry(2, 200)
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'latlng' => $latitude.','.$longitude,
                    'key' => $apiKey,
                    'language' => app()->getLocale(),
                    'region' => 'cd',
                    'result_type' => 'street_address|route|premise|point_of_interest|establishment|neighborhood|sublocality|locality',
                ]);

            if (! $response->successful() || $response->json('status') !== 'OK') {
                Log::warning('Google reverse geocoding failed.', [
                    'status' => $response->status(),
                    'google_status' => $response->json('status'),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);

                return null;
            }

            $results = collect($response->json('results', []))
                ->filter(fn ($result): bool => is_array($result))
                ->sortByDesc(fn (array $result): int => $this->googleResultScore($result))
                ->values();

            foreach ($results as $result) {
                $address = $this->googleAddressFromResult($result);

                if ($address !== null) {
                    return $address;
                }
            }

            return null;
        } catch (Throwable $exception) {
            Log::warning('Google reverse geocoding exception.', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function resolveWithMapbox(float $latitude, float $longitude): ?string
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
                    'types' => 'address,poi,street,neighborhood,locality,place',
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
                ?? $feature['properties']['place_formatted']
                ?? $feature['properties']['name']
                ?? $feature['place_name']
                ?? null;

            return $this->cleanAddress(is_string($address) ? $address : null);
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

    private function googleAddressFromResult(array $result): ?string
    {
        $formattedAddress = $this->cleanAddress($result['formatted_address'] ?? null);
        $components = collect($result['address_components'] ?? [])
            ->filter(fn ($component): bool => is_array($component))
            ->values();

        $component = function (string $type) use ($components): ?string {
            $value = $components->first(
                fn (array $component): bool => in_array($type, Arr::wrap($component['types'] ?? []), true)
            );

            return $this->cleanAddress($value['long_name'] ?? null);
        };

        $route = $component('route');
        $streetNumber = $component('street_number');
        $street = $route !== null && $streetNumber !== null ? $streetNumber.' '.$route : $route;
        $locality = $component('locality');
        $parts = [
            $street,
            $component('premise'),
            $component('point_of_interest'),
            $component('neighborhood'),
            $component('sublocality_level_2'),
            $component('sublocality_level_1') ?? $component('sublocality'),
            $locality,
            $component('administrative_area_level_2'),
            $component('administrative_area_level_1'),
            $component('country'),
        ];

        $address = collect($parts)
            ->filter(fn (?string $part): bool => $part !== null && ! $this->isPlusCode($part))
            ->unique(fn (string $part): string => Str::lower($part))
            ->implode(', ');

        $address = $this->cleanAddress($address !== '' ? $address : null);

        if ($address !== null && $this->addressScore($address) >= $this->addressScore($formattedAddress)) {
            return $address;
        }

        return $formattedAddress;
    }

    private function googleResultScore(array $result): int
    {
        $score = 0;
        $types = Arr::wrap($result['types'] ?? []);
        $formattedAddress = $this->cleanAddress($result['formatted_address'] ?? null);

        $preferredTypes = [
            'street_address' => 70,
            'premise' => 64,
            'point_of_interest' => 58,
            'establishment' => 52,
            'route' => 46,
            'neighborhood' => 34,
            'sublocality' => 28,
            'locality' => 12,
        ];

        foreach ($preferredTypes as $type => $weight) {
            if (in_array($type, $types, true)) {
                $score += $weight;
            }
        }

        if (($result['geometry']['location_type'] ?? null) === 'ROOFTOP') {
            $score += 18;
        }

        return $score + $this->addressScore($formattedAddress);
    }

    private function addressScore(?string $address): int
    {
        $address = $this->cleanAddress($address);

        if ($address === null) {
            return 0;
        }

        $score = 10;
        $parts = $this->addressParts($address);
        $lower = Str::lower($address);

        $score += min(30, count($parts) * 6);

        if (preg_match('/\b(avenue|ave|av\.|rue|boulevard|route|street|road|drive|place|quartier|centre|cité)\b/u', $lower)) {
            $score += 35;
        }

        if (preg_match('/\d/u', $address)) {
            $score += 8;
        }

        if ($this->isGenericAddress($address)) {
            $score -= 45;
        }

        if ($this->isPlusCode($address)) {
            $score -= 35;
        }

        return max(0, $score);
    }

    private function isDetailedEnough(string $address): bool
    {
        if ($this->isGenericAddress($address) || $this->isPlusCode($address)) {
            return false;
        }

        return $this->addressScore($address) >= 45;
    }

    private function isGenericAddress(string $address): bool
    {
        $parts = $this->addressParts($address);
        $lowerParts = array_map(fn (string $part): string => Str::lower($part), $parts);
        $lower = Str::lower($address);

        if (Str::contains($lower, ['latitude', 'longitude'])) {
            return true;
        }

        if (count($parts) <= 3 && count(array_unique($lowerParts)) < count($lowerParts)) {
            return true;
        }

        return count($parts) <= 3
            && Str::contains($lower, ['république démocratique du congo', 'democratic republic of the congo'])
            && ! preg_match('/\b(avenue|ave|av\.|rue|boulevard|route|street|road|drive|place|quartier|centre|cité)\b/u', $lower);
    }

    /**
     * @return list<string>
     */
    private function addressParts(string $address): array
    {
        return collect(explode(',', $address))
            ->map(fn (string $part): string => trim($part))
            ->filter()
            ->values()
            ->all();
    }

    private function isPlusCode(string $value): bool
    {
        return (bool) preg_match('/(^|,\s*)[23456789CFGHJMPQRVWX]{4,}\+[23456789CFGHJMPQRVWX]{2,}/iu', $value);
    }

    private function cleanAddress(?string $address): ?string
    {
        if (! is_string($address)) {
            return null;
        }

        $address = preg_replace('/(^|,\s*)[23456789CFGHJMPQRVWX]{4,}\+[23456789CFGHJMPQRVWX]{2,}\s*,?\s*/iu', '$1', $address);
        $address = trim(preg_replace('/\s+/', ' ', (string) $address));
        $address = trim($address, " \t\n\r\0\x0B,");

        return $address !== '' ? $address : null;
    }

    private function cacheKey(float $latitude, float $longitude): string
    {
        return sprintf(
            'reverse-geocode:v2:%s:%0.5f:%0.5f',
            app()->getLocale(),
            $latitude,
            $longitude,
        );
    }
}
