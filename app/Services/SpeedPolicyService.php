<?php

namespace App\Services;

use App\Models\AlertRuleState;
use App\Models\Device;
use App\Models\Position;
use Illuminate\Support\Facades\DB;

class SpeedPolicyService
{
    public function __construct(private readonly AlertService $alerts)
    {
    }

    public function evaluate(Device $device, Position $position): void
    {
        if ($device->vehicle_id === null || $position->speed === null) {
            return;
        }

        $vehicle = $device->vehicle()->with('speedPolicy')->first();
        $rule = $vehicle?->speedPolicy;

        if ($rule === null || ! $rule->is_active || $rule->threshold_value === null) {
            return;
        }

        $speed = (float) $position->speed;
        $limit = (int) $rule->threshold_value;

        DB::transaction(function () use ($device, $position, $rule, $speed, $limit): void {
            AlertRuleState::query()->firstOrCreate([
                'alert_rule_id' => $rule->id,
                'device_id' => $device->id,
            ]);

            $state = AlertRuleState::query()
                ->where('alert_rule_id', $rule->id)
                ->where('device_id', $device->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($speed > $limit) {
                if (! $state->is_triggered) {
                    $this->alerts->createOverspeedAlert($device, $position, $limit);
                }

                $state->forceFill([
                    'is_triggered' => true,
                    'last_value' => $speed,
                    'triggered_at' => $state->is_triggered ? $state->triggered_at : $position->server_time,
                ])->save();

                return;
            }

            if ($state->is_triggered) {
                $state->forceFill([
                    'is_triggered' => false,
                    'last_value' => $speed,
                    'triggered_at' => null,
                ])->save();
            }
        });
    }
}
