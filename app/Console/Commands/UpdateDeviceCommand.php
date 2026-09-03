<?php

namespace App\Console\Commands;

use App\Actions\UpdateDeviceCommandAction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateDeviceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gps:update-device-command
        {claim-token : Command claim token}
        {event : sent, acknowledged or failed}
        {--response= : Tracker response or frame hash}
        {--failure-code= : Stable failure code}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Persist a tracker command transport event';

    /**
     * Execute the console command.
     */
    public function handle(UpdateDeviceCommandAction $update): int
    {
        $data = Validator::make([
            'claim_token' => $this->argument('claim-token'),
            'event' => $this->argument('event'),
            'response' => $this->option('response'),
            'failure_code' => $this->option('failure-code'),
        ], [
            'claim_token' => ['required', 'uuid'],
            'event' => ['required', Rule::in(['sent', 'acknowledged', 'failed'])],
            'response' => ['nullable', 'string', 'max:4000'],
            'failure_code' => ['nullable', 'string', 'max:80'],
        ])->validate();

        $command = $update->execute(
            $data['claim_token'],
            $data['event'],
            $data['response'] ?? null,
            $data['failure_code'] ?? null,
        );

        $this->line(json_encode(['ok' => true, 'status' => $command->status]));

        return self::SUCCESS;
    }
}
