<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Position;
use App\Models\TrackerEvent;
use Illuminate\Support\Carbon;

class TrackerEventService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): TrackerEvent
    {
        return TrackerEvent::query()->create([
            'fleet_id' => $attributes['fleet_id'] ?? null,
            'vehicle_id' => $attributes['vehicle_id'] ?? null,
            'device_id' => $attributes['device_id'],
            'position_id' => $attributes['position_id'] ?? null,
            'type' => $attributes['type'],
            'title' => $attributes['title'],
            'message' => $attributes['message'],
            'started_at' => $attributes['started_at'] ?? now(),
            'ended_at' => $attributes['ended_at'] ?? null,
            'duration_seconds' => $attributes['duration_seconds'] ?? null,
            'latitude' => $attributes['latitude'] ?? null,
            'longitude' => $attributes['longitude'] ?? null,
            'metadata' => $attributes['metadata'] ?? null,
        ]);
    }

    public function recordPosition(
        Device $device,
        Position $position,
        string $previousStatus,
        ?bool $previousMovement,
        ?bool $previousIgnition
    ): void {
        $device->loadMissing(['fleet:id,name,code', 'vehicle:id,name,registration_number']);

        if ($previousStatus !== 'online') {
            $this->createSignalRestored($device, $position, $previousStatus);
        }

        if ($position->movement !== null) {
            $this->recordMovementChange($device, $position, $previousMovement);
        }

        if ($position->ignition !== null) {
            $this->recordIgnitionChange($device, $position, $previousIgnition);
        }
    }

    public function createSignalLost(Device $device): TrackerEvent
    {
        $device->loadMissing(['fleet:id,name,code', 'vehicle:id,name,registration_number']);
        $vehicle = $this->vehicleName($device);
        $tracker = $this->trackerName($device);

        return $this->create([
            'fleet_id' => $device->fleet_id,
            'vehicle_id' => $device->vehicle_id,
            'device_id' => $device->id,
            'type' => 'signal_lost',
            'title' => __('trackers.event_signal_lost_title'),
            'message' => __('trackers.event_signal_lost_message', [
                'tracker' => $tracker,
                'vehicle' => $vehicle,
            ]),
            'latitude' => $device->last_latitude,
            'longitude' => $device->last_longitude,
            'metadata' => [
                'imei' => $device->imei,
                'translation' => $this->translation('trackers.event_signal_lost_title', 'trackers.event_signal_lost_message', [
                    'tracker' => $tracker,
                    'vehicle' => $vehicle,
                ]),
            ],
        ]);
    }

    private function createSignalRestored(Device $device, Position $position, string $previousStatus): TrackerEvent
    {
        $vehicle = $this->vehicleName($device);
        $tracker = $this->trackerName($device);

        return $this->create([
            'fleet_id' => $device->fleet_id,
            'vehicle_id' => $device->vehicle_id,
            'device_id' => $device->id,
            'position_id' => $position->id,
            'type' => 'signal_restored',
            'title' => __('trackers.event_signal_restored_title'),
            'message' => __('trackers.event_signal_restored_message', [
                'tracker' => $tracker,
                'vehicle' => $vehicle,
            ]),
            'started_at' => $position->server_time,
            'latitude' => $position->latitude,
            'longitude' => $position->longitude,
            'metadata' => [
                'imei' => $device->imei,
                'previous_status' => $previousStatus,
                'translation' => $this->translation('trackers.event_signal_restored_title', 'trackers.event_signal_restored_message', [
                    'tracker' => $tracker,
                    'vehicle' => $vehicle,
                ]),
            ],
        ]);
    }

    private function recordMovementChange(Device $device, Position $position, ?bool $previousMovement): void
    {
        $movement = (bool) $position->movement;

        if ($previousMovement === null && ! $movement) {
            return;
        }

        if ($previousMovement === $movement) {
            return;
        }

        $movement
            ? $this->createMovementStarted($device, $position)
            : $this->createMovementStopped($device, $position);
    }

    private function createMovementStarted(Device $device, Position $position): TrackerEvent
    {
        $vehicle = $this->vehicleName($device);

        return $this->create([
            'fleet_id' => $device->fleet_id,
            'vehicle_id' => $device->vehicle_id,
            'device_id' => $device->id,
            'position_id' => $position->id,
            'type' => 'movement_started',
            'title' => __('trackers.event_movement_started_title'),
            'message' => __('trackers.event_movement_started_message', ['vehicle' => $vehicle]),
            'started_at' => $position->server_time,
            'latitude' => $position->latitude,
            'longitude' => $position->longitude,
            'metadata' => [
                'translation' => $this->translation('trackers.event_movement_started_title', 'trackers.event_movement_started_message', [
                    'vehicle' => $vehicle,
                ]),
            ],
        ]);
    }

    private function createMovementStopped(Device $device, Position $position): TrackerEvent
    {
        $vehicle = $this->vehicleName($device);
        $startedEvent = TrackerEvent::query()
            ->where('device_id', $device->id)
            ->where('type', 'movement_started')
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        $startedAt = $startedEvent?->started_at ?? $device->last_position_at ?? $position->server_time;
        $duration = (int) max(0, Carbon::parse($startedAt)->diffInSeconds($position->server_time));

        $startedEvent?->forceFill([
            'ended_at' => $position->server_time,
            'duration_seconds' => $duration,
        ])->save();

        return $this->create([
            'fleet_id' => $device->fleet_id,
            'vehicle_id' => $device->vehicle_id,
            'device_id' => $device->id,
            'position_id' => $position->id,
            'type' => 'movement_stopped',
            'title' => __('trackers.event_movement_stopped_title'),
            'message' => __('trackers.event_movement_stopped_message', [
                'vehicle' => $vehicle,
                'duration' => $this->durationLabel($duration),
            ]),
            'started_at' => $position->server_time,
            'duration_seconds' => $duration,
            'latitude' => $position->latitude,
            'longitude' => $position->longitude,
            'metadata' => [
                'translation' => $this->translation('trackers.event_movement_stopped_title', 'trackers.event_movement_stopped_message', [
                    'vehicle' => $vehicle,
                    'duration' => $this->durationLabel($duration),
                ]),
            ],
        ]);
    }

    private function recordIgnitionChange(Device $device, Position $position, ?bool $previousIgnition): void
    {
        $ignition = (bool) $position->ignition;

        if ($previousIgnition === null && ! $ignition) {
            return;
        }

        if ($previousIgnition === $ignition) {
            return;
        }

        $vehicle = $this->vehicleName($device);
        $titleKey = $ignition ? 'trackers.event_ignition_on_title' : 'trackers.event_ignition_off_title';
        $messageKey = $ignition ? 'trackers.event_ignition_on_message' : 'trackers.event_ignition_off_message';

        $this->create([
            'fleet_id' => $device->fleet_id,
            'vehicle_id' => $device->vehicle_id,
            'device_id' => $device->id,
            'position_id' => $position->id,
            'type' => $ignition ? 'ignition_on' : 'ignition_off',
            'title' => __($titleKey),
            'message' => __($messageKey, ['vehicle' => $vehicle]),
            'started_at' => $position->server_time,
            'latitude' => $position->latitude,
            'longitude' => $position->longitude,
            'metadata' => [
                'translation' => $this->translation($titleKey, $messageKey, [
                    'vehicle' => $vehicle,
                ]),
            ],
        ]);
    }

    private function trackerName(Device $device): string
    {
        return $device->name ?: $device->imei;
    }

    private function vehicleName(Device $device): string
    {
        return $device->vehicle?->name ?: __('trackers.no_vehicle');
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

    private function durationLabel(int $seconds): string
    {
        if ($seconds < 60) {
            return __('trackers.duration_seconds', ['seconds' => $seconds]);
        }

        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        if ($remainingSeconds === 0) {
            return __('trackers.duration_minutes', ['minutes' => $minutes]);
        }

        return __('trackers.duration_minutes_seconds', [
            'minutes' => $minutes,
            'seconds' => $remainingSeconds,
        ]);
    }
}
