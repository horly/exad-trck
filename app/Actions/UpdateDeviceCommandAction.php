<?php

namespace App\Actions;

use App\Models\DeviceCommand;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateDeviceCommandAction
{
    public function execute(string $claimToken, string $event, ?string $response, ?string $failureCode): DeviceCommand
    {
        return DB::transaction(function () use ($claimToken, $event, $response, $failureCode): DeviceCommand {
            $command = DeviceCommand::query()->where('claim_token', $claimToken)->lockForUpdate()->firstOrFail();
            $attempt = $command->commandAttempts()->latest('attempt_number')->first();

            if (! $command->isActive()) {
                return $command;
            }

            if ($event === 'sent') {
                $frameHash = $response;
                $command->update(['status' => DeviceCommand::STATUS_SENT, 'sent_at' => now()]);
                $attempt?->update(['status' => DeviceCommand::STATUS_SENT, 'frame_hash' => $frameHash]);

                return $command;
            }

            if ($event === 'acknowledged') {
                if (! $this->isSuccessfulTeltonikaResponse($response)) {
                    return $this->fail($command, $response, 'tracker_rejected');
                }

                $command->update([
                    'status' => DeviceCommand::STATUS_ACKNOWLEDGED,
                    'acknowledged_at' => now(),
                    'response_text' => $response,
                ]);
                $attempt?->update([
                    'status' => DeviceCommand::STATUS_ACKNOWLEDGED,
                    'response_text' => $response,
                ]);

                return $command;
            }

            if ($event === 'failed') {
                return $this->fail($command, $response, $failureCode ?: 'transport_error');
            }

            throw ValidationException::withMessages(['event' => 'Unsupported device command event.']);
        }, 3);
    }

    private function isSuccessfulTeltonikaResponse(?string $response): bool
    {
        if (blank($response)) {
            return false;
        }

        $rejected = ['SCENARIO', 'disabled from CFG', 'not available', 'Bad Syntax', 'NOFIX'];

        foreach ($rejected as $needle) {
            if (str_contains((string) $response, $needle)) {
                return false;
            }
        }

        return str_contains((string) $response, 'DOUT1:')
            && str_contains((string) $response, 'DOUT2:');
    }

    private function fail(DeviceCommand $command, ?string $message, string $code): DeviceCommand
    {
        $command->update([
            'status' => DeviceCommand::STATUS_FAILED,
            'failed_at' => now(),
            'failure_code' => $code,
            'failure_message' => $message,
            'response_text' => $message,
        ]);
        $command->commandAttempts()->latest('attempt_number')->first()?->update([
            'status' => DeviceCommand::STATUS_FAILED,
            'failure_code' => $code,
            'failure_message' => $message,
            'finished_at' => now(),
        ]);

        return $command;
    }
}
