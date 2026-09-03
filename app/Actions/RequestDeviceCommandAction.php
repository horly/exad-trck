<?php

namespace App\Actions;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\DeviceImmobilizationProfile;
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
        Request $request,
    ): DeviceCommand {
        return DB::transaction(function () use ($device, $user, $action, $request): DeviceCommand {
            $lockedDevice = Device::query()->with('vehicle')->lockForUpdate()->findOrFail($device->id);
            $this->ensureEligible($lockedDevice);
            Gate::forUser($user)->authorize('control-engine', $lockedDevice);

            $profile = DeviceImmobilizationProfile::query()->where('device_id', $lockedDevice->id)->lockForUpdate()->first();

            if ($profile === null) {
                $profile = DeviceImmobilizationProfile::query()->create([
                    'device_id' => $lockedDevice->id,
                    'verified_by' => $user->id,
                    'verified_at' => now(),
                    'verification_note' => 'DOUT1 + DOUT2 capability verified from supported device matrix.',
                ]);
            }

            if (! $profile->is_active || $profile->verified_at === null) {
                throw ValidationException::withMessages([
                    'action' => __('trackers.engine_control_profile_inactive'),
                ]);
            }

            $active = DeviceCommand::query()
                ->where('device_id', $lockedDevice->id)
                ->whereIn('status', DeviceCommand::ACTIVE_STATUSES)
                ->lockForUpdate()
                ->first();

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

            $outputs = $profile->outputsFor($action);
            $states = implode('', [$outputs[1], $outputs[2]]);

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
                'command_text' => "setigndigout {$states} 0 0",
                'desired_outputs' => $outputs,
                'reason' => $action === DeviceCommand::ACTION_IMMOBILIZE
                    ? __('trackers.engine_control_audit_immobilize')
                    : __('trackers.engine_control_audit_release'),
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
