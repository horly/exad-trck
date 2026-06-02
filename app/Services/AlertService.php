<?php

namespace App\Services;

use App\Events\AlertCreated;
use App\Models\Alert;
use App\Models\Device;
use App\Models\Position;
use App\Models\Vehicle;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

class AlertService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, bool $broadcast = true): Alert
    {
        $alert = Alert::query()->create([
            'fleet_id' => $attributes['fleet_id'] ?? null,
            'vehicle_id' => $attributes['vehicle_id'] ?? null,
            'device_id' => $attributes['device_id'] ?? null,
            'position_id' => $attributes['position_id'] ?? null,
            'type' => $attributes['type'],
            'severity' => $attributes['severity'] ?? 'medium',
            'status' => $attributes['status'] ?? 'new',
            'title' => $attributes['title'],
            'message' => $attributes['message'],
            'latitude' => $attributes['latitude'] ?? null,
            'longitude' => $attributes['longitude'] ?? null,
            'speed' => $attributes['speed'] ?? null,
            'metadata' => $attributes['metadata'] ?? null,
            'occurred_at' => $attributes['occurred_at'] ?? now(),
        ]);

        if ($broadcast) {
            try {
                event(new AlertCreated($alert));
            } catch (Throwable $exception) {
                Log::warning('Alert broadcast failed.', [
                    'alert_id' => $alert->id,
                    'alert_type' => $alert->type,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $alert;
    }

    public function createNoSignalAlert(Device $device): Alert
    {
        $device->loadMissing(['fleet:id,name,code', 'vehicle:id,name,registration_number']);
        $tracker = $device->name ?: $device->imei;
        $vehicle = $device->vehicle?->name;

        return $this->create([
            'fleet_id' => $device->fleet_id,
            'vehicle_id' => $device->vehicle_id,
            'device_id' => $device->id,
            'type' => 'no_signal',
            'severity' => 'high',
            'title' => __('alerts.type_no_signal'),
            'message' => __('alerts.message_no_signal', [
                'tracker' => $tracker,
                'vehicle' => $vehicle ?: __('alerts.unknown_vehicle'),
            ]),
            'latitude' => $device->last_latitude,
            'longitude' => $device->last_longitude,
            'speed' => $device->last_speed,
            'metadata' => [
                'imei' => $device->imei,
                'last_seen_at' => $device->last_seen_at?->toDateTimeString(),
                'translation' => $this->translation('alerts.type_no_signal', 'alerts.message_no_signal', [
                    'tracker' => $tracker,
                    'vehicle' => $vehicle ?: ['trans_key' => 'alerts.unknown_vehicle'],
                ]),
            ],
        ]);
    }

    public function createSignalRecoveredAlert(Device $device, Position $position, string $previousStatus): Alert
    {
        $device->loadMissing(['fleet:id,name,code', 'vehicle:id,name,registration_number']);
        $tracker = $device->name ?: $device->imei;
        $vehicle = $device->vehicle?->name;

        return $this->create([
            'fleet_id' => $device->fleet_id,
            'vehicle_id' => $device->vehicle_id,
            'device_id' => $device->id,
            'position_id' => $position->id,
            'type' => 'signal_recovered',
            'severity' => 'medium',
            'title' => __('alerts.type_signal_recovered'),
            'message' => __('alerts.message_signal_recovered', [
                'tracker' => $tracker,
                'vehicle' => $vehicle ?: __('alerts.unknown_vehicle'),
            ]),
            'latitude' => $position->latitude,
            'longitude' => $position->longitude,
            'speed' => $position->speed,
            'metadata' => [
                'imei' => $device->imei,
                'previous_status' => $previousStatus,
                'translation' => $this->translation('alerts.type_signal_recovered', 'alerts.message_signal_recovered', [
                    'tracker' => $tracker,
                    'vehicle' => $vehicle ?: ['trans_key' => 'alerts.unknown_vehicle'],
                ]),
            ],
            'occurred_at' => $position->server_time,
        ]);
    }

    public function createOverspeedAlert(Device $device, Position $position, int $limit): Alert
    {
        $device->loadMissing(['fleet:id,name,code', 'vehicle:id,name,registration_number']);
        $vehicle = $device->vehicle?->name ?: $device->name ?: $device->imei;

        return $this->create([
            'fleet_id' => $device->fleet_id,
            'vehicle_id' => $device->vehicle_id,
            'device_id' => $device->id,
            'position_id' => $position->id,
            'type' => 'overspeed',
            'severity' => 'high',
            'title' => __('alerts.type_overspeed'),
            'message' => __('alerts.message_overspeed', [
                'vehicle' => $vehicle,
                'speed' => $position->speed,
                'limit' => $limit,
            ]),
            'latitude' => $position->latitude,
            'longitude' => $position->longitude,
            'speed' => $position->speed,
            'metadata' => [
                'speed_limit' => $limit,
                'imei' => $device->imei,
                'translation' => $this->translation('alerts.type_overspeed', 'alerts.message_overspeed', [
                    'vehicle' => $vehicle,
                    'speed' => $position->speed,
                    'limit' => $limit,
                ]),
            ],
            'occurred_at' => $position->server_time,
        ]);
    }

    public function demo(?Vehicle $vehicle = null): Alert
    {
        $device = $vehicle?->device;
        $vehicleName = $vehicle?->name ?? $device?->name;

        return $this->create([
            'fleet_id' => $vehicle?->fleet_id ?? $device?->fleet_id,
            'vehicle_id' => $vehicle?->id ?? $device?->vehicle_id,
            'device_id' => $device?->id,
            'type' => 'sos',
            'severity' => 'critical',
            'title' => __('alerts.type_sos'),
            'message' => __('alerts.message_demo', [
                'vehicle' => $vehicleName ?? __('alerts.unknown_vehicle'),
            ]),
            'latitude' => Arr::get($device?->toArray() ?? [], 'last_latitude'),
            'longitude' => Arr::get($device?->toArray() ?? [], 'last_longitude'),
            'metadata' => [
                'source' => 'alerts:demo',
                'translation' => $this->translation('alerts.type_sos', 'alerts.message_demo', [
                    'vehicle' => $vehicleName ?? ['trans_key' => 'alerts.unknown_vehicle'],
                ]),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $replace
     * @return array{title_key: string, message_key: string, replace: array<string, mixed>}
     */
    private function translation(string $titleKey, string $messageKey, array $replace): array
    {
        return [
            'title_key' => $titleKey,
            'message_key' => $messageKey,
            'replace' => $replace,
        ];
    }
}
