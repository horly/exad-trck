<?php

namespace App\Console\Commands;

use App\Actions\ClaimDeviceCommandAction;
use App\Models\Device;
use Illuminate\Console\Command;

class ClaimDeviceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gps:claim-device-command {device : Internal device ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Claim the next safe tracker command for a connected device';

    /**
     * Execute the console command.
     */
    public function handle(ClaimDeviceCommandAction $claim): int
    {
        $device = Device::query()->find($this->argument('device'));

        if ($device === null) {
            $this->error(json_encode(['ok' => false, 'message' => 'Unknown device.']));

            return self::FAILURE;
        }

        $result = $claim->execute($device);
        $command = $result['command'];
        $this->line(json_encode([
            'ok' => true,
            'command' => $command ? [
                'id' => $command->id,
                'uuid' => $command->uuid,
                'claim_token' => $command->claim_token,
                'text' => $command->command_text,
                'action' => $command->action,
                'desired_outputs' => $command->desired_outputs,
            ] : null,
            'safety' => $result['safety'],
        ]));

        return self::SUCCESS;
    }
}
