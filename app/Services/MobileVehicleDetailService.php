<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DriverIdentifier;
use App\Models\Position;
use App\Models\Vehicle;
use App\Support\DriverIdentifierUid;

class MobileVehicleDetailService
{
    public function __construct(
        private readonly PositionAddressService $positionAddress,
        private readonly CanBusStateService $canBusState,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(
        Vehicle $vehicle,
        bool $includeTrackerIdentity = false,
        bool $includeDriverIdentifier = false,
    ): array {
        $device = $vehicle->device;

        if (! $device instanceof Device) {
            return [
                ...($includeTrackerIdentity ? ['tracker' => null] : []),
                'location' => null,
                'driver' => null,
                'power' => null,
                'gsm' => null,
                'diagnostic' => null,
                'obd_can' => null,
                'recent_events' => [],
            ];
        }

        $device->load([
            'trackerEvents' => fn ($query) => $query
                ->vehicleEvents()
                ->latest('started_at')
                ->latest('id')
                ->limit(5),
        ]);
        $latestPosition = $this->latestPosition($device);
        $latestStoppedPosition = $this->latestStoppedPosition($device);
        $parkingStart = $this->parkingStartPosition($device);
        $locationPosition = $parkingStart ?: ($latestStoppedPosition ?: $latestPosition);
        $locationAddress = $locationPosition instanceof Position
            ? $this->positionAddress->resolve($locationPosition)
            : null;
        $driverIdentifier = $this->currentDriverIdentifier($device, $vehicle);
        $driver = $driverIdentifier?->driver;
        $gsmSignal = $device->last_gsm_signal;

        if ($gsmSignal !== null && $gsmSignal <= 5) {
            $gsmSignal = min(100, $gsmSignal * 20);
        }

        return [
            ...($includeTrackerIdentity ? [
                'tracker' => [
                    'id' => $device->id,
                    'name' => $device->name,
                    'imei' => $device->imei,
                    'brand' => $device->brand,
                    'model' => $device->model,
                ],
            ] : []),
            'location' => [
                'gps_quality_percent' => $device->last_satellites !== null
                    ? min(100, max(0, $device->last_satellites * 7))
                    : null,
                'latitude' => $locationPosition?->latitude !== null
                    ? (float) $locationPosition->latitude
                    : ($device->last_latitude !== null ? (float) $device->last_latitude : null),
                'longitude' => $locationPosition?->longitude !== null
                    ? (float) $locationPosition->longitude
                    : ($device->last_longitude !== null ? (float) $device->last_longitude : null),
                'altitude_meters' => $locationPosition?->altitude !== null
                    ? (float) $locationPosition->altitude
                    : null,
                'address' => $locationAddress,
                'heading_degrees' => (int) ($locationPosition?->angle ?? $device->last_angle),
                'movement' => $locationPosition?->movement ?? $device->last_movement,
                'ignition' => $locationPosition?->ignition ?? $device->last_ignition,
                'parking_started_at' => ($parkingStart?->gps_time ?: $parkingStart?->server_time)?->toISOString(),
                'updated_at' => ($locationPosition?->gps_time ?: $locationPosition?->server_time ?: $device->last_position_at)?->toISOString(),
            ],
            'driver' => $driver ? [
                'full_name' => $driver->full_name,
                'employee_id' => $driver->employee_id,
                'department' => $driver->department?->name,
                ...($includeDriverIdentifier ? [
                    'identifier_uid' => $driverIdentifier?->uid ?: $device->last_driver_identifier_uid,
                    'identifier_type' => $driverIdentifier?->type,
                ] : []),
                'phone' => $driver->phone,
                'status' => $driver->status,
            ] : null,
            'power' => [
                'external_voltage' => $this->floatOrNull($device->last_external_voltage),
                'internal_battery_voltage' => $this->floatOrNull($device->last_battery_voltage),
                'battery_level_percent' => $device->last_battery_level,
                'ignition' => $device->last_ignition,
                'updated_at' => ($device->last_seen_at ?: $device->last_position_at)?->toISOString(),
            ],
            'gsm' => [
                'signal_percent' => $gsmSignal,
                'operator_name' => $device->operator_name,
                'sim_number' => $device->sim_number,
                'codec' => $device->codec,
                'updated_at' => ($device->last_seen_at ?: $device->last_position_at)?->toISOString(),
            ],
            'diagnostic' => [
                'satellites' => $device->last_satellites,
                'protocol' => $device->protocol ? strtoupper($device->protocol) : null,
                ...($includeDriverIdentifier ? [
                    'driver_identifier_uid' => $device->last_driver_identifier_uid,
                ] : []),
                'odometer_km' => $this->floatOrNull($device->last_odometer_km ?? $device->last_can_total_mileage_km),
                'engine_seconds' => $device->last_engine_seconds,
                'io_count' => is_array($device->last_io) ? count($device->last_io) : 0,
                'sensor_count' => is_array($device->last_sensors) ? count($device->last_sensors) : 0,
                'updated_at' => ($device->last_diagnostic_updated_at ?: $device->last_seen_at ?: $device->last_position_at)?->toISOString(),
            ],
            'obd_can' => [
                'runtime_seconds' => $device->last_obd_runtime_seconds,
                'rpm' => $device->last_obd_rpm,
                'speed_kmh' => $device->last_obd_speed,
                'throttle_percent' => $this->floatOrNull($device->last_obd_throttle_percent),
                'engine_temperature_c' => $this->floatOrNull($device->last_obd_engine_temperature_c),
                'module_voltage' => $this->normalizedVoltage($device->last_obd_module_voltage),
                'engine_load_percent' => $this->floatOrNull($device->last_obd_engine_load_percent),
                'fuel_level_percent' => $this->normalizedPercent($device->last_can_fuel_level_percent),
                'fault_distance_km' => $device->last_obd_fault_distance_km,
                'errors_count' => $device->last_obd_errors_count,
                'distance_since_clear_km' => $device->last_obd_distance_since_clear_km,
                'states' => $this->canBusState->forDevice($device),
                'updated_at' => ($device->last_obd_updated_at ?: $device->last_seen_at ?: $device->last_position_at)?->toISOString(),
            ],
            'recent_events' => $device->trackerEvents->map(fn ($event): array => [
                'id' => $event->id,
                'type' => $event->type,
                'title' => $event->localizedTitle(),
                'message' => $event->localizedMessage(),
                'started_at' => $event->started_at?->toISOString(),
            ])->values()->all(),
        ];
    }

    private function latestPosition(Device $device): ?Position
    {
        return Position::query()
            ->where('device_id', $device->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($device->last_position_at, fn ($query, $lastPositionAt) => $query->where('gps_time', '<=', $lastPositionAt))
            ->latest('gps_time')
            ->latest('server_time')
            ->latest('id')
            ->first([
                'id',
                'device_id',
                'gps_time',
                'latitude',
                'longitude',
                'address',
                'altitude',
                'server_time',
                'speed',
                'angle',
                'movement',
                'ignition',
                'raw_data',
            ]);
    }

    private function latestStoppedPosition(Device $device): ?Position
    {
        return Position::query()
            ->where('device_id', $device->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where(function ($query): void {
                $query
                    ->where('movement', false)
                    ->orWhere('speed', 0)
                    ->orWhere('ignition', false);
            })
            ->when($device->last_position_at, fn ($query, $lastPositionAt) => $query->where('gps_time', '<=', $lastPositionAt))
            ->latest('gps_time')
            ->latest('server_time')
            ->latest('id')
            ->first([
                'id',
                'device_id',
                'gps_time',
                'latitude',
                'longitude',
                'address',
                'altitude',
                'server_time',
                'speed',
                'angle',
                'movement',
                'ignition',
                'raw_data',
            ]);
    }

    private function parkingStartPosition(Device $device): ?Position
    {
        if ($device->last_ignition !== false) {
            return null;
        }

        $positions = Position::query()
            ->where('device_id', $device->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($device->last_position_at, fn ($query, $lastPositionAt) => $query->where('gps_time', '<=', $lastPositionAt))
            ->latest('gps_time')
            ->latest('server_time')
            ->latest('id')
            ->limit(250)
            ->get([
                'id',
                'device_id',
                'gps_time',
                'latitude',
                'longitude',
                'address',
                'altitude',
                'server_time',
                'speed',
                'angle',
                'movement',
                'ignition',
                'raw_data',
            ]);
        $parkingStart = null;

        foreach ($positions as $position) {
            if ($position->ignition !== false) {
                break;
            }

            $parkingStart = $position;
        }

        return $parkingStart ?: Position::query()
            ->where('device_id', $device->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('ignition', false)
            ->when($device->last_position_at, fn ($query, $lastPositionAt) => $query->where('gps_time', '<=', $lastPositionAt))
            ->latest('gps_time')
            ->latest('server_time')
            ->latest('id')
            ->first([
                'id',
                'device_id',
                'gps_time',
                'latitude',
                'longitude',
                'address',
                'altitude',
                'server_time',
                'speed',
                'angle',
                'movement',
                'ignition',
                'raw_data',
            ]);
    }

    private function currentDriverIdentifier(Device $device, Vehicle $vehicle): ?DriverIdentifier
    {
        if ($device->vehicle_id === null || blank($device->last_driver_identifier_uid)) {
            return null;
        }

        return DriverIdentifier::query()
            ->with('driver.department:id,name,code')
            ->whereIn('uid', DriverIdentifierUid::candidates($device->last_driver_identifier_uid))
            ->where('active', true)
            ->whereHas('driver', fn ($query) => $query->where('fleet_id', $vehicle->fleet_id))
            ->whereHas('driver.vehicles', fn ($query) => $query->whereKey($device->vehicle_id))
            ->first();
    }

    private function floatOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function normalizedVoltage(mixed $value): ?float
    {
        $voltage = $this->floatOrNull($value);

        return $voltage !== null && $voltage > 100 ? $voltage / 1000 : $voltage;
    }

    private function normalizedPercent(mixed $value): ?float
    {
        $percent = $this->floatOrNull($value);

        return $percent !== null && $percent <= 1 ? $percent * 100 : $percent;
    }
}
