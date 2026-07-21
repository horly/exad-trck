<?php

use App\Models\Alert;
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
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('core fleet organization stays reserved while clients manage garages and maintenance', function () {
    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create();
    $admin = User::factory()->admin($fleet->subscription)->forFleet($fleet)->create();

    foreach ([route('drivers.index'), route('departments.index')] as $url) {
        $this->actingAs($superadmin)->get($url)->assertSuccessful();
        $this->actingAs($admin)->get($url)->assertForbidden();
    }

    foreach ([route('garages.index'), route('maintenance.index')] as $url) {
        $this->actingAs($superadmin)->get($url)->assertSuccessful();
        $this->actingAs($admin)->get($url)->assertSuccessful();
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
        ->assertSee(route('departments.index'), false)
        ->assertSee(route('garages.index'), false)
        ->assertSee(route('maintenance.index'), false);
});

test('every management form modal displays a contextual title icon', function () {
    $superadmin = User::factory()->superadmin()->create();

    foreach ([
        'users.index' => 'fa-user-plus',
        'subscriptions.index' => 'fa-layer-group',
        'fleets.index' => 'fa-warehouse',
        'vehicles.index' => 'fa-car-side',
        'trackers.index' => 'fa-satellite-dish',
        'drivers.index' => 'fa-id-card',
        'departments.index' => 'fa-sitemap',
        'garages.index' => 'fa-screwdriver-wrench',
        'maintenance.index' => 'fa-clipboard-check',
        'alert-rules.index' => 'fa-bell',
        'reports.index' => 'fa-calendar-check',
    ] as $routeName => $icon) {
        $this->actingAs($superadmin)
            ->get(route($routeName))
            ->assertSuccessful()
            ->assertSee($icon, false);
    }
});

test('database backed selects are searchable and match standard field height', function () {
    $superadmin = User::factory()->superadmin()->create();

    foreach ([
        'vehicles.index' => 2,
        'departments.index' => 1,
        'drivers.index' => 2,
        'fleets.index' => 1,
        'maintenance.index' => 2,
        'alert-rules.index' => 3,
        'reports.index' => 3,
        'map.index' => 1,
    ] as $routeName => $minimumSearchableFields) {
        $response = $this->actingAs($superadmin)->get(route($routeName));

        $response->assertSuccessful()->assertSee('js/searchable-select.js', false);
        expect(substr_count($response->getContent(), 'data-searchable-database'))
            ->toBeGreaterThanOrEqual($minimumSearchableFields);
    }

    $dashboardCss = file_get_contents(public_path('css/dashboard.css'));
    expect($dashboardCss)->toContain('.searchable-select-toggle {')
        ->toContain('height: 40px;')
        ->toContain('min-height: 40px;');
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
            'location_latitude' => -4.3250000,
            'location_longitude' => 15.3120000,
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
        ->and((float) $driver->location_latitude)->toBe(-4.325)
        ->and((float) $driver->location_longitude)->toBe(15.312)
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

test('superadmin can search real driver addresses with the configured map provider', function () {
    config()->set('services.maps.provider', 'google');
    config()->set('services.google_maps.api_key', 'test-google-key');

    Http::fake([
        'maps.googleapis.com/maps/api/geocode/json*' => Http::response([
            'status' => 'OK',
            'results' => [
                [
                    'formatted_address' => '32 Avenue de la Paix, Gombe, Kinshasa, RDC',
                    'geometry' => [
                        'location' => ['lat' => -4.3094, 'lng' => 15.2867],
                    ],
                ],
            ],
        ]),
    ]);

    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)
        ->getJson(route('addresses.search', ['query' => 'Avenue de la Paix']))
        ->assertSuccessful()
        ->assertJsonPath('results.0.address', '32 Avenue de la Paix, Gombe, Kinshasa, RDC')
        ->assertJsonPath('results.0.latitude', -4.3094)
        ->assertJsonPath('results.0.longitude', 15.2867);

    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://maps.googleapis.com/maps/api/geocode/json')
        && $request['address'] === 'Avenue de la Paix'
        && $request['region'] === 'cd');
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
            'gps_time' => now()->subMinute()->toIso8601String(),
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
            'gps_time' => now()->toIso8601String(),
        ]),
    ]);

    $session->refresh();

    expect($stopCode)->toBe(0)
        ->and($session->status)->toBe('completed')
        ->and($session->ended_at)->not->toBeNull()
        ->and($session->end_position_id)->not->toBeNull()
        ->and($session->metadata['close_reason'])->toBe('ignition_off');
});

test('gps ingestion alerts once per driver geofence exit and rearms after reentry', function () {
    $fleet = Fleet::factory()->create();
    $vehicle = Vehicle::factory()->create(['fleet_id' => $fleet->id, 'name' => 'Toyota Test']);
    $device = Device::factory()->create([
        'vehicle_id' => $vehicle->id,
        'fleet_id' => $fleet->id,
        'imei' => '353201355315560',
    ]);
    $driver = Driver::query()->create([
        'fleet_id' => $fleet->id,
        'first_name' => 'David',
        'last_name' => 'Lukusa',
        'address' => '32 Avenue de la Paix, Kinshasa',
        'location_latitude' => -4.325,
        'location_longitude' => 15.312,
        'location_radius_meters' => 150,
        'status' => 'active',
    ]);
    $driver->vehicles()->attach($vehicle);
    DriverIdentifier::query()->create([
        'driver_id' => $driver->id,
        'type' => 'rfid',
        'uid' => 'GEOFENCE01',
        'active' => true,
    ]);

    $ingest = function (float $latitude, string $time, ?string $identifier = null) use ($device): int {
        return Artisan::call('gps:ingest-position', [
            '--payload' => json_encode(array_filter([
                'imei' => $device->imei,
                'lat' => $latitude,
                'lng' => 15.312,
                'address' => 'Kinshasa',
                'ignition' => true,
                'gps_time' => $time,
                'rfid_uid' => $identifier,
            ], fn (mixed $value): bool => $value !== null)),
        ]);
    };

    expect($ingest(-4.330, now()->subMinutes(3)->toIso8601String(), 'GEOFENCE01'))->toBe(0)
        ->and(Alert::query()->where('type', 'geofence_exit')->count())->toBe(1);

    expect($ingest(-4.331, now()->subMinutes(2)->toIso8601String()))->toBe(0)
        ->and(Alert::query()->where('type', 'geofence_exit')->count())->toBe(1);

    expect($ingest(-4.3255, now()->subMinute()->toIso8601String()))->toBe(0)
        ->and(DriverSession::query()->firstOrFail()->fresh()->geofence_status)->toBe('inside');

    expect($ingest(-4.330, now()->toIso8601String()))->toBe(0)
        ->and(Alert::query()->where('type', 'geofence_exit')->count())->toBe(2);

    $alert = Alert::query()->where('type', 'geofence_exit')->latest()->firstOrFail();

    expect($alert->vehicle_id)->toBe($vehicle->id)
        ->and($alert->device_id)->toBe($device->id)
        ->and($alert->metadata['driver_id'])->toBe($driver->id)
        ->and($alert->metadata['radius_meters'])->toBe(150)
        ->and($alert->localizedMessage())->toContain('David Lukusa');
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
