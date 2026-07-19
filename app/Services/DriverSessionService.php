<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DriverIdentifier;
use App\Models\DriverSession;
use App\Models\Position;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DriverSessionService
{
    /**
     * Synchronize the active driver session from a GPS payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public function sync(Device $device, Position $position, array $payload): ?DriverSession
    {
        // TrackerEventService may have loaded a reduced vehicle relation without fleet_id.
        $vehicle = $device->vehicle()->first();

        if (! $vehicle) {
            return null;
        }

        $identifierUid = $this->extractIdentifierUid($payload);
        $ignition = $this->extractBoolean($payload, 'ignition');
        $occurredAt = $position->gps_time ?? $position->server_time ?? now();

        return DB::transaction(function () use ($device, $vehicle, $position, $payload, $identifierUid, $ignition, $occurredAt): ?DriverSession {
            $activeSession = DriverSession::query()
                ->where('status', 'active')
                ->where(function ($query) use ($device, $vehicle): void {
                    $query->where('device_id', $device->id)
                        ->orWhere('vehicle_id', $vehicle->id);
                })
                ->lockForUpdate()
                ->latest('started_at')
                ->first();

            if ($ignition === false) {
                $this->close($activeSession, $position, $occurredAt, 'ignition_off');

                return null;
            }

            if ($identifierUid === null) {
                return $activeSession;
            }

            $identifier = DriverIdentifier::query()
                ->with('driver')
                ->where('uid', $identifierUid)
                ->where('active', true)
                ->lockForUpdate()
                ->first();

            if ($identifier && (
                ($identifier->issued_at && $occurredAt->isBefore($identifier->issued_at))
                || ($identifier->expires_at && $occurredAt->isAfter($identifier->expires_at))
            )) {
                $identifier = null;
            }

            $driver = $identifier?->driver;
            $isAuthorized = $driver
                && $driver->status === 'active'
                && (int) $driver->fleet_id === (int) $vehicle->fleet_id
                && $driver->vehicles()->where('vehicles.id', $vehicle->id)->exists();

            if (! $identifier || ! $isAuthorized) {
                $this->close($activeSession, $position, $occurredAt, 'identifier_rejected');

                return null;
            }

            if ($activeSession?->driver_identifier_id === $identifier->id) {
                return $activeSession;
            }

            $this->close($activeSession, $position, $occurredAt, 'driver_changed');

            return DriverSession::query()->create([
                'driver_id' => $driver->id,
                'driver_identifier_id' => $identifier->id,
                'vehicle_id' => $vehicle->id,
                'device_id' => $device->id,
                'start_position_id' => $position->id,
                'started_at' => $occurredAt,
                'status' => 'active',
                'metadata' => [
                    'identifier_type' => $identifier->type,
                    'source' => Arr::get($payload, 'source', 'gps-listener'),
                ],
            ]);
        });
    }

    private function close(?DriverSession $session, Position $position, Carbon $occurredAt, string $reason): void
    {
        if (! $session) {
            return;
        }

        $endedAt = $occurredAt->lessThan($session->started_at)
            ? $session->started_at
            : $occurredAt;

        $session->forceFill([
            'end_position_id' => $position->id,
            'ended_at' => $endedAt,
            'status' => 'completed',
            'metadata' => array_merge($session->metadata ?? [], [
                'close_reason' => $reason,
            ]),
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function extractIdentifierUid(array $payload): ?string
    {
        $keys = [
            'driver_identifier',
            'driver_identifier_uid',
            'driver_uid',
            'rfid',
            'rfid_uid',
            'ibutton',
            'ibutton_id',
            'nfc_uid',
        ];

        foreach ($keys as $key) {
            $value = $this->findRecursively($payload, $key);

            if (is_scalar($value)) {
                $normalized = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $value));

                if ($normalized !== '') {
                    return $normalized;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractBoolean(array $payload, string $key): ?bool
    {
        $value = $this->findRecursively($payload, $key);

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_string($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function findRecursively(array $payload, string $needle): mixed
    {
        foreach ($payload as $key => $value) {
            if (strtolower((string) $key) === strtolower($needle)) {
                return $value;
            }

            if (is_array($value)) {
                $nested = $this->findRecursively($value, $needle);

                if ($nested !== null) {
                    return $nested;
                }
            }
        }

        return null;
    }
}
