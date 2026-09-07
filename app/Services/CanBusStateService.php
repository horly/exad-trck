<?php

namespace App\Services;

use App\Models\Device;

class CanBusStateService
{
    /**
     * @var array<string, int>
     */
    private const INDIVIDUAL_IO_IDS = [
        'key_in_ignition' => 652,
        'handbrake_active' => 653,
        'front_left_door_open' => 654,
        'front_right_door_open' => 655,
        'rear_left_door_open' => 656,
        'rear_right_door_open' => 657,
        'trunk_open' => 658,
        'ignition_on' => 898,
        'webasto_on' => 899,
        'engine_running' => 900,
        'roof_open' => 909,
        'footbrake_active' => 910,
        'clutch_pressed' => 911,
        'hood_open' => 913,
    ];

    /**
     * Teltonika Security State Flags P2 (AVL ID 132).
     *
     * @var array<string, int>
     */
    private const P2_BITS = [
        'key_in_ignition' => 24,
        'ignition_on' => 25,
        'webasto_on' => 27,
        'handbrake_active' => 36,
        'footbrake_active' => 37,
        'engine_running' => 38,
        'front_left_door_open' => 40,
        'front_right_door_open' => 41,
        'rear_left_door_open' => 42,
        'rear_right_door_open' => 43,
        'hood_open' => 44,
        'trunk_open' => 45,
        'roof_open' => 46,
    ];

    /**
     * Teltonika Security State Flags P4 (AVL ID 517).
     *
     * @var array<string, int>
     */
    private const P4_BITS = [
        'ignition_on' => 8,
        'key_in_ignition' => 9,
        'webasto_on' => 10,
        'engine_running' => 11,
        'handbrake_active' => 18,
        'footbrake_active' => 19,
        'clutch_pressed' => 20,
        'front_left_door_open' => 22,
        'front_right_door_open' => 23,
        'rear_left_door_open' => 24,
        'rear_right_door_open' => 25,
        'trunk_open' => 26,
        'hood_open' => 27,
        'roof_open' => 48,
    ];

    /**
     * Teltonika Door Status (AVL ID 90).
     *
     * @var array<string, int>
     */
    private const DOOR_STATUS_MASKS = [
        'front_left_door_open' => 0x0100,
        'front_right_door_open' => 0x0200,
        'rear_left_door_open' => 0x0400,
        'rear_right_door_open' => 0x0800,
        'hood_open' => 0x1000,
        'trunk_open' => 0x2000,
    ];

    /**
     * @return array<string, bool|null>
     */
    public function forDevice(Device $device): array
    {
        return $this->decode(
            is_array($device->last_io) ? $device->last_io : [],
            is_array($device->last_raw_payload) ? $device->last_raw_payload : [],
        );
    }

    /**
     * @param  array<int|string, mixed>  $io
     * @param  array<int|string, mixed>  $rawPayload
     * @return array<string, bool|null>
     */
    public function decode(array $io, array $rawPayload = []): array
    {
        $sources = $this->sources($io, $rawPayload);
        $p2Flags = $this->firstValue($sources, [132, 'security_state_flags', 'security_state_flags_p2']);
        $p4Flags = $this->firstValue($sources, [517, 'security_state_flags_p4']);
        $doorStatus = $this->firstValue($sources, [90, 'door_status', 'can_door_status']);
        $states = [];

        foreach (self::INDIVIDUAL_IO_IDS as $state => $ioId) {
            $individualIoValue = $this->booleanValue($this->firstValue($sources, [$ioId]));

            // Teltonika AVL 911 is inverted: 0 means pushed and 1 means released.
            if ($ioId === 911 && $individualIoValue !== null) {
                $individualIoValue = ! $individualIoValue;
            }

            $namedValue = $this->firstValue($sources, [
                $state,
                "can_{$state}",
                "ssf_{$state}",
            ]);

            $states[$state] = $individualIoValue;

            if ($states[$state] === null && array_key_exists($state, self::P4_BITS) && $p4Flags !== null) {
                $states[$state] = $this->bitIsSet($p4Flags, self::P4_BITS[$state]);
            }

            if ($states[$state] === null && array_key_exists($state, self::P2_BITS) && $p2Flags !== null) {
                $states[$state] = $this->bitIsSet($p2Flags, self::P2_BITS[$state]);
            }

            if ($states[$state] === null) {
                $states[$state] = $this->booleanValue($namedValue);
            }

            if ($states[$state] === null && array_key_exists($state, self::DOOR_STATUS_MASKS) && $doorStatus !== null) {
                $states[$state] = $this->maskIsSet($doorStatus, self::DOOR_STATUS_MASKS[$state]);
            }
        }

        $doorStates = array_filter([
            $states['front_left_door_open'],
            $states['front_right_door_open'],
            $states['rear_left_door_open'],
            $states['rear_right_door_open'],
        ], static fn (?bool $value): bool => $value !== null);

        $states['doors_open'] = $doorStates === [] ? null : in_array(true, $doorStates, true);

        return $states;
    }

    /**
     * @param  array<int|string, mixed>  $io
     * @param  array<int|string, mixed>  $rawPayload
     * @return list<array<int|string, mixed>>
     */
    private function sources(array $io, array $rawPayload): array
    {
        $candidates = [
            $io,
            data_get($rawPayload, 'io'),
            data_get($rawPayload, 'can'),
            data_get($rawPayload, 'payload.io'),
            data_get($rawPayload, 'payload.can'),
            data_get($rawPayload, 'payload'),
            $rawPayload,
        ];

        return array_values(array_filter($candidates, 'is_array'));
    }

    /**
     * @param  list<array<int|string, mixed>>  $sources
     * @param  list<int|string>  $keys
     */
    private function firstValue(array $sources, array $keys): mixed
    {
        foreach ($keys as $key) {
            foreach ($sources as $source) {
                foreach ([$key, (string) $key] as $candidate) {
                    if (array_key_exists($candidate, $source) && $source[$candidate] !== null && $source[$candidate] !== '') {
                        return $source[$candidate];
                    }
                }
            }
        }

        return null;
    }

    private function booleanValue(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return match ((float) $value) {
                0.0 => false,
                1.0 => true,
                default => null,
            };
        }

        if (! is_string($value)) {
            return null;
        }

        return match (strtolower(trim($value))) {
            '1', 'true', 'yes', 'on', 'active', 'opened', 'open' => true,
            '0', 'false', 'no', 'off', 'inactive', 'closed', 'close' => false,
            default => null,
        };
    }

    private function maskIsSet(mixed $value, int $mask): ?bool
    {
        $integer = $this->integerValue($value);

        return $integer === null ? null : ($integer & $mask) === $mask;
    }

    private function bitIsSet(mixed $value, int $bit): ?bool
    {
        if (is_string($value) && str_starts_with(strtolower(trim($value)), '0x')) {
            $hex = ltrim(substr(trim($value), 2), '0');
            $hex = $hex === '' ? '0' : $hex;
            $nibble = strlen($hex) - 1 - intdiv($bit, 4);

            if ($nibble < 0) {
                return false;
            }

            return ((hexdec($hex[$nibble]) >> ($bit % 4)) & 1) === 1;
        }

        $integer = $this->integerValue($value);

        return $integer === null ? null : (($integer >> $bit) & 1) === 1;
    }

    private function integerValue(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value) && is_finite($value) && $value <= PHP_INT_MAX) {
            return (int) $value;
        }

        if (is_string($value)) {
            $value = trim($value);

            if (preg_match('/^-?\d+$/', $value) === 1 && (float) $value <= PHP_INT_MAX) {
                return (int) $value;
            }
        }

        return null;
    }
}
