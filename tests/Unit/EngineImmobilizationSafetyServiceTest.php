<?php

use App\Models\Device;
use App\Models\Position;
use App\Services\EngineImmobilizationSafetyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function stoppedTelemetryWindow(Device $device, array $latestOverrides = []): void
{
    foreach ([31, 15, 0] as $secondsAgo) {
        $values = [
            'gps_time' => now()->subSeconds($secondsAgo),
            'server_time' => now()->subSeconds($secondsAgo),
            'speed' => 0,
            'ignition' => false,
            'movement' => false,
            'raw_data' => [
                'payload' => [
                    'io' => ['24' => 0, '85' => 0, '179' => 0, '180' => 0, '239' => 0, '240' => 0, '517' => 0],
                    'obd' => ['speed' => 0, 'rpm' => 0],
                    'can' => ['engine_running' => 0],
                ],
            ],
        ];

        if ($secondsAgo === 0) {
            $values = array_replace_recursive($values, $latestOverrides);
        }

        Position::factory()->forDevice($device)->create($values);
    }
}

test('accepts three complete stopped telemetry samples covering thirty seconds', function () {
    Carbon::setTestNow('2026-09-03 12:00:00');
    $device = Device::factory()->online()->create(['last_seen_at' => now(), 'last_ignition' => false]);
    stoppedTelemetryWindow($device);

    $result = app(EngineImmobilizationSafetyService::class)->evaluate($device);

    expect($result['safe'])->toBeTrue()
        ->and($result['sample_count'])->toBe(3)
        ->and($result['window_seconds'])->toBeGreaterThanOrEqual(30);
});

test('vetoes an immobilization while rpm is positive', function () {
    Carbon::setTestNow('2026-09-03 12:00:00');
    $device = Device::factory()->online()->create(['last_seen_at' => now(), 'last_ignition' => false]);
    stoppedTelemetryWindow($device, [
        'raw_data' => ['payload' => ['io' => ['85' => 708], 'obd' => ['rpm' => 708]]],
    ]);

    $result = app(EngineImmobilizationSafetyService::class)->evaluate($device);

    expect($result['safe'])->toBeFalse()
        ->and($result['checks']['rpm'])->toBeFalse();
});

test('vetoes movement ignition and missing independent speed telemetry', function (array $overrides, string $check) {
    Carbon::setTestNow('2026-09-03 12:00:00');
    $device = Device::factory()->online()->create(['last_seen_at' => now(), 'last_ignition' => false]);
    stoppedTelemetryWindow($device, $overrides);

    $result = app(EngineImmobilizationSafetyService::class)->evaluate($device);

    expect($result['safe'])->toBeFalse()
        ->and($result['checks'][$check])->toBeFalse();
})->with([
    'movement' => [['movement' => true, 'raw_data' => ['payload' => ['io' => ['240' => 1]]]], 'movement'],
    'ignition' => [['ignition' => true, 'raw_data' => ['payload' => ['io' => ['239' => 1]]]], 'ignition'],
    'missing OBD speed' => [['raw_data' => ['payload' => ['obd' => ['speed' => null], 'io' => ['24' => null]]]], 'obd_speed'],
]);
