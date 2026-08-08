<?php

namespace App\Services;

use App\Models\Position;
use Illuminate\Support\Str;

class PositionAddressService
{
    public function __construct(
        private readonly ReverseGeocodingService $reverseGeocoding,
    ) {}

    public function resolve(Position $position): ?string
    {
        if ($position->latitude === null || $position->longitude === null) {
            return null;
        }

        $address = $this->reverseGeocoding->resolve(
            (float) $position->latitude,
            (float) $position->longitude,
        );

        $address ??= $this->payloadAddress($position);

        if ($address === null && ! $this->comesFromGpsListener($position)) {
            $address = $this->clean($position->address);
        }

        if ($address !== null && $position->address !== $address) {
            $position->forceFill(['address' => $address])->save();
            $position->address = $address;
        }

        return $address;
    }

    private function payloadAddress(Position $position): ?string
    {
        foreach (['payload.address', 'payload.payload.address'] as $path) {
            $address = $this->clean(data_get($position->raw_data, $path));

            if ($address !== null) {
                return $address;
            }
        }

        return null;
    }

    private function comesFromGpsListener(Position $position): bool
    {
        $source = data_get($position->raw_data, 'source');

        return is_string($source) && Str::startsWith($source, 'gps-listener');
    }

    private function clean(mixed $address): ?string
    {
        if (! is_string($address)) {
            return null;
        }

        $address = trim(preg_replace('/\s+/', ' ', $address) ?? '');

        return $address !== '' ? $address : null;
    }
}
