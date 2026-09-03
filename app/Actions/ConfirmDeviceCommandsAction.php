<?php

namespace App\Actions;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Position;

class ConfirmDeviceCommandsAction
{
    public function execute(Device $device): void
    {
        $position = Position::query()
            ->where('device_id', $device->id)
            ->latest('server_time')
            ->latest('id')
            ->first(['raw_data']);
        $io = data_get($position?->raw_data, 'payload.io', []);

        if (! is_array($io)) {
            return;
        }

        DeviceCommand::query()
            ->where('device_id', $device->id)
            ->whereIn('status', [DeviceCommand::STATUS_SENT, DeviceCommand::STATUS_ACKNOWLEDGED])
            ->oldest('id')
            ->get()
            ->each(function (DeviceCommand $command) use ($io): void {
                $desired = $command->desired_outputs;
                $dout1 = $io['179'] ?? $io[179] ?? null;
                $dout2 = $io['180'] ?? $io[180] ?? null;

                if ($dout1 === null || $dout2 === null) {
                    return;
                }

                if ((int) $dout1 !== (int) ($desired[1] ?? $desired['1'] ?? -1)
                    || (int) $dout2 !== (int) ($desired[2] ?? $desired['2'] ?? -1)) {
                    return;
                }

                $command->update([
                    'status' => DeviceCommand::STATUS_CONFIRMED,
                    'confirmed_at' => now(),
                ]);
                $command->commandAttempts()->latest('attempt_number')->first()?->update([
                    'status' => DeviceCommand::STATUS_CONFIRMED,
                    'finished_at' => now(),
                ]);
            });
    }
}
