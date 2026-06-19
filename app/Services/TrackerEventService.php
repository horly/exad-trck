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
        ?bool $previousMovement,
        ?bool $previousIgnition
    ): void {
        $device->loadMissing(['fleet:id,name,code', 'vehicle:id,name,registration_number']);

        if ($position->movement !== null) {
            $this->recordMovementChange($device, $position, $previousMovement);
        }

        if ($position->ignition !== null) {
            $this->recordIgnitionChange($device, $position, $previousIgnition);
        }

        $this->recordTelemetryEvents($device, $position);
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

    private function recordTelemetryEvents(Device $device, Position $position): void
    {
        $events = data_get($position->raw_data, 'payload.events', []);

        if (! is_array($events)) {
            return;
        }

        foreach ($events as $event) {
            $type = is_string($event)
                ? $event
                : (is_array($event) ? (string) ($event['type'] ?? '') : '');

            $type = $this->normalizeEventType($type);

            if ($type === '' || in_array($type, ['movement_started', 'movement_stopped', 'ignition_on', 'ignition_off'], true)) {
                continue;
            }

            $this->createTelemetryEvent($device, $position, $type, is_array($event) ? $event : []);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createTelemetryEvent(Device $device, Position $position, string $type, array $payload): TrackerEvent
    {
        $vehicle = $this->vehicleName($device);
        $titleKey = "trackers.event_{$type}_title";
        $messageKey = "trackers.event_{$type}_message";
        $title = __($titleKey);
        $message = __($messageKey, ['vehicle' => $vehicle]);

        if ($title === $titleKey) {
            $title = (string) ($payload['title'] ?? str($type)->replace('_', ' ')->title());
        }

        if ($message === $messageKey) {
            $message = (string) ($payload['message'] ?? __('trackers.event_generic_message', [
                'vehicle' => $vehicle,
                'event' => $title,
            ]));
        }

        return $this->create([
            'fleet_id' => $device->fleet_id,
            'vehicle_id' => $device->vehicle_id,
            'device_id' => $device->id,
            'position_id' => $position->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'started_at' => $position->server_time,
            'latitude' => $position->latitude,
            'longitude' => $position->longitude,
            'metadata' => [
                'telemetry' => $payload,
                'translation' => $this->translation($titleKey, $messageKey, [
                    'vehicle' => $vehicle,
                ]),
            ],
        ]);
    }

    private function normalizeEventType(string $type): string
    {
        return str($type)
            ->lower()
            ->replace(['-', ' '], '_')
            ->replaceMatches('/[^a-z0-9_]/', '')
            ->squish()
            ->toString();
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
