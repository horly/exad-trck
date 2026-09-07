<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Position;
use Illuminate\Support\Carbon;

class EngineImmobilizationSafetyService
{
    public function __construct(
        private readonly CanBusStateService $canBusState,
    ) {}

    /**
     * @return array{safe: bool, code: string, checks: array<string, bool>, sample_count: int, window_seconds: int, latest_at: ?string}
     */
    public function evaluate(Device $device): array
    {
        $freshSeconds = (int) config('engine-immobilization.telemetry_fresh_seconds', 15);
        $windowSeconds = (int) config('engine-immobilization.safety_window_seconds', 30);
        $minimumSamples = (int) config('engine-immobilization.minimum_safe_samples', 3);
        $positions = Position::query()
            ->where('device_id', $device->id)
            ->latest('server_time')
            ->latest('id')
            ->limit(30)
            ->get(['id', 'gps_time', 'server_time', 'speed', 'ignition', 'movement', 'raw_data']);
        $latest = $positions->first();
        $latestAt = $latest?->server_time;
        $window = collect();

        if ($latestAt !== null) {
            foreach ($positions as $position) {
                $window->push($position);

                if ($window->count() >= $minimumSamples
                    && $position->server_time?->diffInSeconds($latestAt) >= $windowSeconds) {
                    break;
                }
            }
        }

        $coveredSeconds = $window->count() > 1
            ? (int) $window->last()->server_time?->diffInSeconds($latestAt)
            : 0;
        $sampleChecks = $window->map(fn (Position $position): array => $this->positionChecks($position));
        $checks = [
            'online' => $device->status === 'online',
            'fresh' => $latestAt !== null && $latestAt->greaterThanOrEqualTo(now()->subSeconds($freshSeconds)),
            'samples' => $window->count() >= $minimumSamples,
            'window' => $coveredSeconds >= $windowSeconds,
            'chronological' => $this->isChronological($window->all()),
            'gps_speed' => $sampleChecks->isNotEmpty() && $sampleChecks->every('gps_speed'),
            'obd_speed' => $sampleChecks->isNotEmpty() && $sampleChecks->every('obd_speed'),
            'movement' => $sampleChecks->isNotEmpty() && $sampleChecks->every('movement'),
            'ignition' => $sampleChecks->isNotEmpty() && $sampleChecks->every('ignition'),
            'rpm' => $sampleChecks->isNotEmpty() && $sampleChecks->every('rpm'),
            'engine_state' => $sampleChecks->isNotEmpty() && $sampleChecks->every('engine_state'),
        ];
        $safe = ! in_array(false, $checks, true);

        return [
            'safe' => $safe,
            'code' => $safe ? 'safe' : $this->firstFailure($checks),
            'checks' => $checks,
            'sample_count' => $window->count(),
            'window_seconds' => $coveredSeconds,
            'latest_at' => $latestAt?->toIso8601String(),
        ];
    }

    /** @return array<string, bool> */
    private function positionChecks(Position $position): array
    {
        $payload = data_get($position->raw_data, 'payload', []);
        $io = data_get($payload, 'io', data_get($payload, 'raw.io', []));
        $io = is_array($io) ? $io : [];
        $obdSpeed = $this->firstNumber($payload, $io, ['obd.speed'], [37, 24]);
        $rpm = $this->firstNumber($payload, $io, ['obd.rpm'], [85, 36]);
        $engineRunning = $this->canBusState->decode($io, $payload)['engine_running'] ?? null;
        $reportedEngineRunning = $this->firstNumber($payload, [], ['can.engine_running'], []);

        return [
            'gps_speed' => (float) $position->speed === 0.0,
            'obd_speed' => $obdSpeed !== null && $obdSpeed === 0.0,
            'movement' => $position->movement === false && $this->ioBoolean($io, 240) === false,
            'ignition' => $position->ignition === false && $this->ioBoolean($io, 239) === false,
            'rpm' => $rpm !== null && $rpm === 0.0,
            'engine_state' => $engineRunning === false && $reportedEngineRunning !== 1.0,
        ];
    }

    /** @param array<int, Position> $positions */
    private function isChronological(array $positions): bool
    {
        $gpsTimes = collect($positions)
            ->reverse()
            ->pluck('gps_time')
            ->filter()
            ->map(fn (Carbon $time): int => $time->getTimestamp())
            ->values();

        return $gpsTimes->count() === count($positions)
            && $gpsTimes->all() === $gpsTimes->sort()->values()->all();
    }

    /** @param array<string, bool> $checks */
    private function firstFailure(array $checks): string
    {
        foreach ($checks as $check => $passed) {
            if (! $passed) {
                return $check;
            }
        }

        return 'unsafe';
    }

    /** @param array<string|int, mixed> $io */
    private function ioBoolean(array $io, int $id): ?bool
    {
        $value = $io[(string) $id] ?? $io[$id] ?? null;

        return $value === null ? null : (bool) (int) $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string|int, mixed>  $io
     * @param  list<string>  $paths
     * @param  list<int>  $ioIds
     */
    private function firstNumber(array $payload, array $io, array $paths, array $ioIds): ?float
    {
        foreach ($paths as $path) {
            $value = data_get($payload, $path);

            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        foreach ($ioIds as $id) {
            $value = $io[(string) $id] ?? $io[$id] ?? null;

            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }
}
