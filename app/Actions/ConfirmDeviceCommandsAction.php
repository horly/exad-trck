<?php

namespace App\Actions;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Position;
use Illuminate\Support\Facades\DB;

class ConfirmDeviceCommandsAction
{
    public function execute(Device $device): void
    {
        $position = Position::query()
            ->where('device_id', $device->id)
            ->latest('server_time')
            ->latest('id')
            ->first(['raw_data', 'server_time']);
        $io = data_get($position?->raw_data, 'payload.io', []);

        if (! is_array($io) || $position?->server_time === null) {
            return;
        }

        DB::transaction(function () use ($device, $io, $position): void {
            DeviceCommand::query()
                ->where('device_id', $device->id)
                ->whereIn('status', [DeviceCommand::STATUS_SENT, DeviceCommand::STATUS_ACKNOWLEDGED])
                ->whereNotNull('sent_at')
                ->where('sent_at', '<', $position->server_time)
                ->lockForUpdate()
                ->oldest('id')
                ->get()
                ->each(function (DeviceCommand $command) use ($io): void {
                    $confirmed = $command->targetOutputs() !== [];

                    foreach ($command->targetOutputs() as $output) {
                        $avlId = $output === 1 ? 179 : 180;
                        $actual = $io[(string) $avlId] ?? $io[$avlId] ?? null;

                        if ($actual === null || (bool) $actual !== $command->desiredStateFor($output)) {
                            $confirmed = false;
                            break;
                        }
                    }

                    if (! $confirmed) {
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
        }, 3);
    }
}
