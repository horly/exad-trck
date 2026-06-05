<?php

namespace App\Services;

use App\Models\Position;
use DateTimeZone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class LocationTimezoneService
{
    private const CACHE_TTL_DAYS = 30;

    public function forPosition(Position $position, ?Carbon $timestamp = null): string
    {
        $latitude = $position->latitude;
        $longitude = $position->longitude;

        if ($latitude === null || $longitude === null) {
            return $this->fallbackTimezone();
        }

        $timestamp ??= $position->gps_time ?: $position->server_time ?: now();

        return $this->forCoordinates((float) $latitude, (float) $longitude, $timestamp);
    }

    public function forCoordinates(float $latitude, float $longitude, Carbon $timestamp): string
    {
        $apiKey = (string) config('services.google_maps.api_key');

        if ($apiKey === '') {
            return $this->fallbackTimezone();
        }

        $timezone = Cache::remember(
            $this->cacheKey($latitude, $longitude, $timestamp),
            now()->addDays(self::CACHE_TTL_DAYS),
            fn (): ?string => $this->resolveWithGoogle($latitude, $longitude, $timestamp, $apiKey),
        );

        return $timezone ?? $this->fallbackTimezone();
    }

    private function resolveWithGoogle(float $latitude, float $longitude, Carbon $timestamp, string $apiKey): ?string
    {
        try {
            $response = Http::timeout(5)
                ->retry(2, 200)
                ->get('https://maps.googleapis.com/maps/api/timezone/json', [
                    'location' => $latitude.','.$longitude,
                    'timestamp' => $timestamp->getTimestamp(),
                    'key' => $apiKey,
                    'language' => app()->getLocale(),
                ]);

            $timezone = $response->json('timeZoneId');

            if (! $response->successful() || $response->json('status') !== 'OK' || ! is_string($timezone)) {
                Log::warning('Google timezone lookup failed.', [
                    'status' => $response->status(),
                    'google_status' => $response->json('status'),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);

                return null;
            }

            return $this->validTimezone($timezone);
        } catch (Throwable $exception) {
            Log::warning('Google timezone lookup exception.', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function fallbackTimezone(): string
    {
        return $this->validTimezone((string) config('services.gps.fallback_timezone', 'Africa/Kinshasa'))
            ?? 'UTC';
    }

    private function validTimezone(string $timezone): ?string
    {
        return in_array($timezone, DateTimeZone::listIdentifiers(), true) ? $timezone : null;
    }

    private function cacheKey(float $latitude, float $longitude, Carbon $timestamp): string
    {
        return sprintf(
            'gps-timezone:%s:%s:%s:%s',
            app()->getLocale(),
            round($latitude, 4),
            round($longitude, 4),
            $timestamp->toDateString(),
        );
    }
}
