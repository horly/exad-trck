<?php

use App\Models\Device;
use App\Models\Driver;
use App\Models\DriverIdentifier;
use App\Models\DriverSession;
use App\Models\Fleet;
use App\Models\Vehicle;
use App\Services\CurrentDriverSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('it returns only the active driver session for the device and vehicle', function () {
    $fleet = Fleet::factory()->create();
    $vehicle = Vehicle::factory()->for($fleet)->create();
    $device = Device::factory()->for($fleet)->create(['vehicle_id' => $vehicle->id]);
    $oldDriver = Driver::query()->create([
        'fleet_id' => $fleet->id,
        'first_name' => 'Arnold',
        'status' => 'active',
    ]);
    $currentDriver = Driver::query()->create([
        'fleet_id' => $fleet->id,
        'first_name' => 'Conducteur actuel',
        'status' => 'active',
    ]);
    $oldDriver->vehicles()->attach($vehicle);
    $currentDriver->vehicles()->attach($vehicle);
    $oldIdentifier = DriverIdentifier::query()->create([
        'driver_id' => $oldDriver->id,
        'type' => 'ibutton',
        'uid' => '38000009A29C2114',
        'active' => true,
    ]);
    $currentIdentifier = DriverIdentifier::query()->create([
        'driver_id' => $currentDriver->id,
        'type' => 'ibutton',
        'uid' => '6C0000028E742F14',
        'active' => true,
    ]);
    DriverSession::query()->create([
        'driver_id' => $oldDriver->id,
        'driver_identifier_id' => $oldIdentifier->id,
        'vehicle_id' => $vehicle->id,
        'device_id' => $device->id,
        'started_at' => now()->subHour(),
        'ended_at' => now()->subMinutes(30),
        'status' => 'completed',
    ]);
    DriverSession::query()->create([
        'driver_id' => $currentDriver->id,
        'driver_identifier_id' => $currentIdentifier->id,
        'vehicle_id' => $vehicle->id,
        'device_id' => $device->id,
        'started_at' => now()->subMinute(),
        'status' => 'active',
    ]);

    $session = app(CurrentDriverSessionService::class)->forDevice($device->load('vehicle'));

    expect($session?->driver_id)->toBe($currentDriver->id)
        ->and($session?->identifier?->uid)->toBe('6C0000028E742F14');
});

test('it does not expose a stale badge when no driver session is active', function () {
    $fleet = Fleet::factory()->create();
    $vehicle = Vehicle::factory()->for($fleet)->create();
    $device = Device::factory()->for($fleet)->create([
        'vehicle_id' => $vehicle->id,
        'last_driver_identifier_uid' => '38000009A29C2114',
    ]);

    expect(app(CurrentDriverSessionService::class)->forDevice($device->load('vehicle')))->toBeNull();
});
