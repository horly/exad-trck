<?php

use App\Models\Department;
use App\Models\Device;
use App\Models\Driver;
use App\Models\DriverIdentifier;
use App\Models\DriverSession;
use App\Models\Fleet;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('fleet organization pages are reserved to superadmin users', function () {
    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create();

    foreach ([route('drivers.index'), route('departments.index')] as $url) {
        $this->actingAs($superadmin)->get($url)->assertSuccessful();
        $this->actingAs($admin)->get($url)->assertForbidden();
    }
});

test('sidebar exposes fleet resources as one expandable navigation group', function () {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)
        ->get(route('drivers.index'))
        ->assertSuccessful()
        ->assertSee('data-sidebar-menu', false)
        ->assertSee(route('fleets.index'), false)
        ->assertSee(route('vehicles.index'), false)
        ->assertSee(route('trackers.index'), false)
        ->assertSee(route('drivers.index'), false)
        ->assertSee(route('departments.index'), false);
});

test('superadmin can create a department in a fleet', function () {
    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create();

    $this->actingAs($superadmin)
        ->post(route('departments.store'), [
            'fleet_id' => $fleet->id,
            'name' => 'Exploitation',
            'code' => 'EXP',
            'description' => 'Equipe des operations terrain.',
            'status' => 'active',
        ])
        ->assertRedirect(route('departments.index'))
        ->assertSessionHas('status', __('departments.created'));

    $this->assertDatabaseHas('departments', [
        'fleet_id' => $fleet->id,
        'name' => 'Exploitation',
        'code' => 'EXP',
        'status' => 'active',
    ]);
});

test('superadmin can create a driver with a normalized badge and authorized vehicles', function () {
    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create();
    $department = Department::query()->create([
        'fleet_id' => $fleet->id,
        'name' => 'Transport',
        'code' => 'TRP',
        'status' => 'active',
    ]);
    $firstVehicle = Vehicle::factory()->create(['fleet_id' => $fleet->id]);
    $secondVehicle = Vehicle::factory()->create(['fleet_id' => $fleet->id]);

    $this->actingAs($superadmin)
        ->post(route('drivers.store'), [
            'fleet_id' => $fleet->id,
            'department_id' => $department->id,
            'first_name' => 'David',
            'middle_name' => 'Mwamba',
            'last_name' => 'Lukusa',
            'employee_id' => 'DRV-001',
            'social_security_number' => 'SS-243-001',
            'identifier_type' => 'rfid',
            'rfid_uid' => 'ab-cd 12:34',
            'authorized_vehicle_ids' => [$firstVehicle->id, $secondVehicle->id],
            'phone' => '+243810000000',
            'email' => 'david@example.com',
            'address' => 'Mimosas, Kinshasa',
            'location_radius_meters' => 150,
            'license_number' => 'PC-2026-001',
            'license_type' => 'C',
            'license_issued_at' => '2026-01-01',
            'license_expires_at' => '2028-01-01',
            'tags' => 'permanent, transport, permanent',
            'status' => 'active',
        ])
        ->assertRedirect(route('drivers.index'))
        ->assertSessionHas('status', __('drivers.created'));

    $driver = Driver::query()->where('employee_id', 'DRV-001')->firstOrFail();

    expect($driver->full_name)->toBe('David Mwamba Lukusa')
        ->and($driver->tags)->toBe(['permanent', 'transport'])
        ->and($driver->social_security_number)->toBe('SS-243-001')
        ->and($driver->location_radius_meters)->toBe(150)
        ->and($driver->vehicles()->pluck('vehicles.id')->sort()->values()->all())
        ->toBe(collect([$firstVehicle->id, $secondVehicle->id])->sort()->values()->all());

    $this->assertDatabaseHas('driver_identifiers', [
        'driver_id' => $driver->id,
        'type' => 'rfid',
        'uid' => 'ABCD1234',
        'active' => true,
    ]);
});

test('driver assignment rejects departments and vehicles from another fleet', function () {
    $superadmin = User::factory()->superadmin()->create();
    $ownFleet = Fleet::factory()->create();
    $otherFleet = Fleet::factory()->create();
    $otherDepartment = Department::query()->create([
        'fleet_id' => $otherFleet->id,
        'name' => 'Autre departement',
        'status' => 'active',
    ]);
    $otherVehicle = Vehicle::factory()->create(['fleet_id' => $otherFleet->id]);

    $this->actingAs($superadmin)
        ->post(route('drivers.store'), [
            'fleet_id' => $ownFleet->id,
            'department_id' => $otherDepartment->id,
            'first_name' => 'Conducteur refuse',
            'identifier_type' => 'rfid',
            'authorized_vehicle_ids' => [$otherVehicle->id],
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['department_id', 'authorized_vehicle_ids']);

    $this->assertDatabaseMissing('drivers', ['first_name' => 'Conducteur refuse']);
});

test('driver and department tables provide ajax search sort and five row pagination', function () {
    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create(['name' => 'EXAD CARS']);

    foreach (range(1, 6) as $index) {
        Department::query()->create([
            'fleet_id' => $fleet->id,
            'name' => "Departement {$index}",
            'code' => "D{$index}",
            'status' => 'active',
        ]);
    }

    $this->actingAs($superadmin)
        ->get(route('departments.index'))
        ->assertSuccessful()
        ->assertSee('5 / 6 lignes')
        ->assertSee('data-datatable-search', false)
        ->assertSee('datatable-sort-link', false);

    $response = $this->actingAs($superadmin)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('departments.index', [
            'search' => 'Departement 6',
            'sort' => 'name',
            'direction' => 'asc',
        ]))
        ->assertSuccessful();

    expect($response->json('html'))
        ->toContain('Departement 6')
        ->toContain('direction=desc');
});

test('gps ingestion opens and closes a session for an authorized driver badge', function () {
    $fleet = Fleet::factory()->create();
    $vehicle = Vehicle::factory()->create(['fleet_id' => $fleet->id]);
    $device = Device::factory()->create([
        'vehicle_id' => $vehicle->id,
        'fleet_id' => $fleet->id,
        'imei' => '353201355315547',
    ]);
    $driver = Driver::query()->create([
        'fleet_id' => $fleet->id,
        'first_name' => 'David',
        'last_name' => 'Lukusa',
        'status' => 'active',
    ]);
    $driver->vehicles()->attach($vehicle);
    $identifier = DriverIdentifier::query()->create([
        'driver_id' => $driver->id,
        'type' => 'ibutton',
        'uid' => 'ABCD1234',
        'active' => true,
    ]);

    $startCode = Artisan::call('gps:ingest-position', [
        '--payload' => json_encode([
            'imei' => $device->imei,
            'lat' => -4.325,
            'lng' => 15.312,
            'address' => 'Kinshasa',
            'ignition' => true,
            'gps_time' => '2026-07-16T10:00:00+01:00',
            'io' => ['ibutton_id' => 'ab-cd 12:34'],
        ]),
    ]);

    $session = DriverSession::query()->firstOrFail();
    $device->refresh();

    expect($startCode)->toBe(0)
        ->and($session->driver_id)->toBe($driver->id)
        ->and($session->driver_identifier_id)->toBe($identifier->id)
        ->and($session->vehicle_id)->toBe($vehicle->id)
        ->and($session->device_id)->toBe($device->id)
        ->and($session->status)->toBe('active')
        ->and($session->end_position_id)->toBeNull()
        ->and($device->last_driver_identifier_uid)->toBe('ABCD1234');

    $stopCode = Artisan::call('gps:ingest-position', [
        '--payload' => json_encode([
            'imei' => $device->imei,
            'lat' => -4.326,
            'lng' => 15.313,
            'address' => 'Kinshasa',
            'ignition' => false,
            'gps_time' => '2026-07-16T10:30:00+01:00',
        ]),
    ]);

    $session->refresh();

    expect($stopCode)->toBe(0)
        ->and($session->status)->toBe('completed')
        ->and($session->ended_at)->not->toBeNull()
        ->and($session->end_position_id)->not->toBeNull()
        ->and($session->metadata['close_reason'])->toBe('ignition_off');
});

test('gps ingestion rejects a badge whose driver is not authorized for the vehicle', function () {
    $fleet = Fleet::factory()->create();
    $vehicle = Vehicle::factory()->create(['fleet_id' => $fleet->id]);
    $otherVehicle = Vehicle::factory()->create(['fleet_id' => $fleet->id]);
    $device = Device::factory()->create([
        'vehicle_id' => $vehicle->id,
        'fleet_id' => $fleet->id,
        'imei' => '353201355315548',
    ]);
    $driver = Driver::query()->create([
        'fleet_id' => $fleet->id,
        'first_name' => 'Conducteur',
        'status' => 'active',
    ]);
    $driver->vehicles()->attach($otherVehicle);
    DriverIdentifier::query()->create([
        'driver_id' => $driver->id,
        'type' => 'rfid',
        'uid' => 'RFID9999',
        'active' => true,
    ]);

    $exitCode = Artisan::call('gps:ingest-position', [
        '--payload' => json_encode([
            'imei' => $device->imei,
            'lat' => -4.325,
            'lng' => 15.312,
            'address' => 'Kinshasa',
            'ignition' => true,
            'rfid_uid' => 'RFID-9999',
        ]),
    ]);

    $device->refresh();

    expect($exitCode)->toBe(0)
        ->and(DriverSession::query()->count())->toBe(0)
        ->and($device->last_driver_identifier_uid)->toBe('RFID9999');
});
