<?php

namespace App\Actions;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Services\EngineImmobilizationSafetyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClaimDeviceCommandAction
{
    public function __construct(
        private readonly EngineImmobilizationSafetyService $safety,
        private readonly ConfirmDeviceCommandsAction $confirm,
    ) {}

    /** @return array{command: ?DeviceCommand, safety: ?array<string, mixed>} */
    public function execute(Device $device): array
    {
        $this->confirm->execute($device);

        return DB::transaction(function () use ($device): array {
            DeviceCommand::query()
                ->where('device_id', $device->id)
                ->whereIn('status', DeviceCommand::ACTIVE_STATUSES)
                ->where('expires_at', '<=', now())
                ->update([
                    'status' => DeviceCommand::STATUS_EXPIRED,
                    'failure_code' => 'expired',
                    'failure_message' => 'Command expired before safe dispatch.',
                ]);

            $command = DeviceCommand::query()
                ->where('device_id', $device->id)
                ->whereIn('status', [DeviceCommand::STATUS_PENDING_SAFETY, DeviceCommand::STATUS_READY])
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->oldest('id')
                ->first();

            if ($command === null) {
                return ['command' => null, 'safety' => null];
            }

            $safety = null;

            if ($command->action === DeviceCommand::ACTION_IMMOBILIZE) {
                $safety = $this->safety->evaluate($device);
                $command->forceFill([
                    'safety_snapshot' => $safety,
                    'safety_checked_at' => now(),
                ])->save();

                if (! $safety['safe']) {
                    return ['command' => null, 'safety' => $safety];
                }
            }

            $attempt = $command->attempts + 1;
            $claimToken = (string) Str::uuid();
            $command->update([
                'status' => DeviceCommand::STATUS_CLAIMED,
                'attempts' => $attempt,
                'claim_token' => $claimToken,
                'claimed_at' => now(),
                'failure_code' => null,
                'failure_message' => null,
            ]);
            $command->commandAttempts()->create([
                'attempt_number' => $attempt,
                'status' => DeviceCommand::STATUS_CLAIMED,
                'started_at' => now(),
            ]);

            return ['command' => $command->fresh(), 'safety' => $safety];
        }, 3);
    }
}
