<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\User;

class EngineControlStateService
{
    /**
     * @return array<string, mixed>
     */
    public function forDevice(Device $device, User $user): array
    {
        $supported = $device->supportsEngineImmobilization();
        $allowed = $supported && $user->can('control-engine', $device);

        if (! $supported) {
            return [
                'supported' => false,
                'allowed' => false,
                'next_action' => null,
                'immobilized' => false,
                'busy' => false,
                'command' => null,
                'outputs' => [],
            ];
        }

        $commands = DeviceCommand::query()
            ->where('device_id', $device->id)
            ->latest('id')
            ->limit(100)
            ->get(['id', 'uuid', 'device_id', 'action', 'status', 'desired_outputs', 'expires_at', 'confirmed_at', 'created_at', 'updated_at']);
        $io = is_array($device->last_io) ? $device->last_io : [];
        $outputs = [];

        foreach ([1 => 179, 2 => 180] as $output => $avlId) {
            $latest = $commands->first(fn (DeviceCommand $command): bool => $command->targetsOutput($output));
            $confirmed = $commands->first(fn (DeviceCommand $command): bool => $command->status === DeviceCommand::STATUS_CONFIRMED
                && $command->targetsOutput($output));
            $busy = $latest?->isActive() ?? false;
            $telemetryState = $io[(string) $avlId] ?? $io[$avlId] ?? null;
            $active = $busy
                ? ($latest?->desiredStateFor($output) ?? false)
                : ($telemetryState === null
                    ? ($confirmed?->desiredStateFor($output) ?? false)
                    : (bool) $telemetryState);

            $outputs[(string) $output] = [
                'number' => $output,
                'active' => $active,
                'busy' => $busy,
                'next_action' => $active
                    ? DeviceCommand::ACTION_RELEASE
                    : DeviceCommand::ACTION_IMMOBILIZE,
            ];
        }

        $latest = $commands->first();
        $busy = collect($outputs)->contains(fn (array $output): bool => $output['busy']);
        $immobilized = collect($outputs)->contains(fn (array $output): bool => $output['active']);

        return [
            'supported' => true,
            'allowed' => $allowed,
            'next_action' => $immobilized
                ? DeviceCommand::ACTION_RELEASE
                : DeviceCommand::ACTION_IMMOBILIZE,
            'immobilized' => $immobilized,
            'busy' => $busy,
            'command' => $allowed && $latest ? [
                'uuid' => $latest->uuid,
                'action' => $latest->action,
                'output' => count($latest->targetOutputs()) === 1 ? $latest->targetOutputs()[0] : null,
                'status' => $latest->status,
                'requested_at' => $latest->created_at?->toISOString(),
                'updated_at' => $latest->updated_at?->toISOString(),
            ] : null,
            'outputs' => $outputs,
        ];
    }
}
