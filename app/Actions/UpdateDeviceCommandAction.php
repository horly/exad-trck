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
                if ($command->status !== DeviceCommand::STATUS_CLAIMED) {
                    return $command;
                }

                $frameHash = $response;
                $command->update(['status' => DeviceCommand::STATUS_SENT, 'sent_at' => now()]);
                $attempt?->update(['status' => DeviceCommand::STATUS_SENT, 'frame_hash' => $frameHash]);

                return $command;
            }

            if ($event === 'acknowledged') {
                if (! $this->isSuccessfulTrackerResponse($command, $response)) {
                    return $this->fail($command, $response, 'tracker_rejected');
                }

                $acknowledgedAt = $command->acknowledged_at ?? now();
                $updates = [
                    'status' => DeviceCommand::STATUS_ACKNOWLEDGED,
                    'acknowledged_at' => now(),
                    'response_text' => $response,
                ];
                $attemptUpdates = [
                    'status' => DeviceCommand::STATUS_ACKNOWLEDGED,
                    'response_text' => $response,
                ];

                if ($this->responseConfirmsDesiredOutputs($command, $response)) {
                    $updates['status'] = DeviceCommand::STATUS_CONFIRMED;
                    $updates['acknowledged_at'] = $acknowledgedAt;
                    $updates['confirmed_at'] = now();
                    $attemptUpdates['status'] = DeviceCommand::STATUS_CONFIRMED;
                    $attemptUpdates['finished_at'] = now();
                }

                $command->update($updates);
                $attempt?->update($attemptUpdates);

                return $command;
            }

            if ($event === 'failed') {
                return $this->fail($command, $response, $failureCode ?: 'transport_error');
            }

            throw ValidationException::withMessages(['event' => 'Unsupported device command event.']);
        }, 3);
    }

    private function isSuccessfulTrackerResponse(DeviceCommand $command, ?string $response): bool
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

        foreach ($command->targetOutputs() as $output) {
            if (! preg_match('/\bDOUT'.$output.'\s*:/i', (string) $response)) {
                return false;
            }
        }

        return $command->targetOutputs() !== [];
    }

    private function responseConfirmsDesiredOutputs(DeviceCommand $command, ?string $response): bool
    {
        if (blank($response) || str_contains(strtoupper((string) $response), 'QUEUED')) {
            return false;
        }

        foreach ($command->targetOutputs() as $output) {
            if (! preg_match('/\\bDOUT'.$output.'\\s*:\\s*(0|1|ON|OFF)\\b/i', (string) $response, $matches)) {
                return false;
            }

            $actual = in_array(strtoupper($matches[1]), ['1', 'ON'], true);

            if ($actual !== $command->desiredStateFor($output)) {
                return false;
            }
        }

        return $command->targetOutputs() !== [];
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
