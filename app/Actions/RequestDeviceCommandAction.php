<?php

namespace App\Actions;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RequestDeviceCommandAction
{
    public function execute(
        Device $device,
        User $user,
        string $action,
        int $output,
        Request $request,
    ): DeviceCommand {
        return DB::transaction(function () use ($device, $user, $action, $output, $request): DeviceCommand {
            $lockedDevice = Device::query()->with('vehicle')->lockForUpdate()->findOrFail($device->id);
            $this->ensureEligible($lockedDevice);
            Gate::forUser($user)->authorize('control-engine', $lockedDevice);

            if (! in_array($output, [1, 2], true)) {
                throw ValidationException::withMessages([
                    'output' => __('trackers.output_control_invalid'),
                ]);
            }

            $active = DeviceCommand::query()
                ->where('device_id', $lockedDevice->id)
                ->whereIn('status', DeviceCommand::ACTIVE_STATUSES)
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->get()
                ->first(fn (DeviceCommand $command): bool => $command->targetsOutput($output));

            if ($active?->action === $action) {
                return $active;
            }

            if ($active !== null) {
                $active->update([
                    'status' => DeviceCommand::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                    'failure_code' => 'superseded',
                    'failure_message' => 'Superseded by a newer opposite request.',
                ]);
            }

            $state = $action === DeviceCommand::ACTION_IMMOBILIZE ? 1 : 0;
            $outputs = [
                1 => $output === 1 ? $state : null,
                2 => $output === 2 ? $state : null,
            ];
            $states = $output === 1 ? "{$state}?" : "?{$state}";
            $timeouts = $output === 1 ? '0 ?' : '? 0';

            return DeviceCommand::query()->create([
                'uuid' => (string) Str::uuid(),
                'device_id' => $lockedDevice->id,
                'vehicle_id' => $lockedDevice->vehicle_id,
                'fleet_id' => $lockedDevice->vehicle?->fleet_id ?? $lockedDevice->fleet_id,
                'requested_by' => $user->id,
                'imei' => $lockedDevice->imei,
                'vehicle_label' => $lockedDevice->vehicle?->name ?? $lockedDevice->name,
                'action' => $action,
                'status' => $action === DeviceCommand::ACTION_IMMOBILIZE
                    ? DeviceCommand::STATUS_PENDING_SAFETY
                    : DeviceCommand::STATUS_READY,
                'command_text' => "setigndigout {$states} {$timeouts}",
                'desired_outputs' => $outputs,
                'reason' => $action === DeviceCommand::ACTION_IMMOBILIZE
                    ? __('trackers.output_control_audit_activate', ['output' => $output])
                    : __('trackers.output_control_audit_release', ['output' => $output]),
                'request_ip' => $request->ip(),
                'request_user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
                'expires_at' => now()->addMinutes((int) config('engine-immobilization.command_ttl_minutes', 10)),
            ]);
        }, 3);
    }

    private function ensureEligible(Device $device): void
    {
        if (! $device->supportsEngineImmobilization() || $device->vehicle === null) {
            throw ValidationException::withMessages(['action' => __('trackers.engine_control_unsupported')]);
        }

    }
}
