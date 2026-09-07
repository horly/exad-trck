<?php

use App\Http\Middleware\ApplyClientPreview;
use App\Models\Alert;
use App\Models\Department;
use App\Models\Device;
use App\Models\Driver;
use App\Models\DriverIdentifier;
use App\Models\DriverSession;
use App\Models\Fleet;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\DriverIdentifierUid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('driver and department lists are readable by clients while core fleet management stays reserved', function () {
    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create();
    $admin = User::factory()->admin($fleet->subscription)->forFleet($fleet)->create();

    $this->actingAs($superadmin)->get(route('drivers.index'))->assertSuccessful();
    $this->actingAs($admin)->get(route('drivers.index'))->assertSuccessful();
    $this->actingAs($superadmin)->get(route('departments.index'))->assertSuccessful();
    $this->actingAs($admin)->get(route('departments.index'))->assertSuccessful();

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
        'vehicles.index' => 1,
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

test('client accounts only see departments from their own fleet', function () {
    $ownFleet = Fleet::factory()->create();
    $otherFleet = Fleet::factory()->create();
    $admin = User::factory()->admin($ownFleet->subscription)->forFleet($ownFleet)->create();
    $user = User::factory()->simpleUser($ownFleet->subscription)->forFleet($ownFleet)->create();
    $superadmin = User::factory()->superadmin()->create();
    $ownDepartment = Department::query()->create([
        'fleet_id' => $ownFleet->id,
        'name' => 'Departement client visible',
        'status' => 'active',
    ]);
    $otherDepartment = Department::query()->create([
        'fleet_id' => $otherFleet->id,
        'name' => 'Departement client masque',
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->get(route('departments.index'))
        ->assertSuccessful()
        ->assertSee($ownDepartment->name)
        ->assertDontSee($otherDepartment->name)
        ->assertSee(route('departments.update', $ownDepartment), false)
        ->assertDontSee('data-confirm-delete', false);

    $this->actingAs($user)
        ->get(route('departments.index'))
        ->assertSuccessful()
        ->assertSee($ownDepartment->name)
        ->assertDontSee($otherDepartment->name)
        ->assertDontSee('data-department-create', false)
        ->assertDontSee(route('departments.update', $ownDepartment), false);

    $this->actingAs($superadmin)
        ->get(route('departments.index'))
        ->assertSuccessful()
        ->assertSee($ownDepartment->name)
        ->assertSee($otherDepartment->name);
});

test('client admin creates updates and deactivates departments only in their fleet', function () {
    $ownFleet = Fleet::factory()->create();
    $otherFleet = Fleet::factory()->create();
    $admin = User::factory()->admin($ownFleet->subscription)->forFleet($ownFleet)->create();

    $this->actingAs($admin)
        ->post(route('departments.store'), [
            'fleet_id' => $otherFleet->id,
            'name' => 'Exploitation client',
            'code' => 'CLIENT-EXP',
            'status' => 'active',
        ])
        ->assertRedirect(route('departments.index'))
        ->assertSessionHasNoErrors();

    $department = Department::query()->where('name', 'Exploitation client')->firstOrFail();

    expect($department->fleet_id)->toBe($ownFleet->id);

    $this->actingAs($admin)
        ->put(route('departments.update', $department), [
            'fleet_id' => $otherFleet->id,
            'name' => 'Exploitation client modifiee',
            'code' => 'CLIENT-EXP',
            'status' => 'inactive',
        ])
        ->assertRedirect(route('departments.index'))
        ->assertSessionHasNoErrors();

    $department->refresh();

    expect($department->fleet_id)->toBe($ownFleet->id)
        ->and($department->name)->toBe('Exploitation client modifiee')
        ->and($department->status)->toBe('inactive');
});

test('client users cannot write outside their department permissions', function () {
    $ownFleet = Fleet::factory()->create();
    $otherFleet = Fleet::factory()->create();
    $admin = User::factory()->admin($ownFleet->subscription)->forFleet($ownFleet)->create();
    $user = User::factory()->simpleUser($ownFleet->subscription)->forFleet($ownFleet)->create();
    $ownDepartment = Department::query()->create([
        'fleet_id' => $ownFleet->id,
        'name' => 'Departement propre',
        'status' => 'active',
    ]);
    $otherDepartment = Department::query()->create([
        'fleet_id' => $otherFleet->id,
        'name' => 'Departement etranger',
        'status' => 'active',
    ]);
    $payload = [
        'fleet_id' => $ownFleet->id,
        'name' => 'Modification refusee',
        'status' => 'inactive',
    ];

    $this->actingAs($admin)
        ->put(route('departments.update', $otherDepartment), $payload)
        ->assertForbidden();

    $this->actingAs($admin)
        ->delete(route('departments.destroy', $ownDepartment))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('departments.store'), $payload)
        ->assertForbidden();

    $this->actingAs($user)
        ->put(route('departments.update', $ownDepartment), $payload)
        ->assertForbidden();

    expect($ownDepartment->fresh()->status)->toBe('active')
        ->and($otherDepartment->fresh()->name)->toBe('Departement etranger');
});

test('superadmin only deletes departments without assigned drivers', function () {
    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create();
    $occupiedDepartment = Department::query()->create([
        'fleet_id' => $fleet->id,
        'name' => 'Departement occupe',
        'status' => 'active',
    ]);
    $emptyDepartment = Department::query()->create([
        'fleet_id' => $fleet->id,
        'name' => 'Departement vide',
        'status' => 'active',
    ]);
    $driver = Driver::query()->create([
        'fleet_id' => $fleet->id,
        'department_id' => $occupiedDepartment->id,
        'first_name' => 'Conducteur affecte',
        'status' => 'active',
    ]);

    $this->actingAs($superadmin)
        ->delete(route('departments.destroy', $occupiedDepartment))
        ->assertRedirect(route('departments.index'))
        ->assertSessionHas('status', __('departments.delete_blocked'));

    $this->assertModelExists($occupiedDepartment);
    expect($driver->fresh()->department_id)->toBe($occupiedDepartment->id);

    $this->actingAs($superadmin)
        ->delete(route('departments.destroy', $emptyDepartment))
        ->assertRedirect(route('departments.index'))
        ->assertSessionHas('status', __('departments.deleted'));

    $this->assertModelMissing($emptyDepartment);
});

test('occupied departments cannot be moved to another fleet', function () {
    $superadmin = User::factory()->superadmin()->create();
    $ownFleet = Fleet::factory()->create();
    $otherFleet = Fleet::factory()->create();
    $department = Department::query()->create([
        'fleet_id' => $ownFleet->id,
        'name' => 'Transport',
        'status' => 'active',
    ]);
    Driver::query()->create([
        'fleet_id' => $ownFleet->id,
        'department_id' => $department->id,
        'first_name' => 'Conducteur lie',
        'status' => 'active',
    ]);

    $this->actingAs($superadmin)
        ->put(route('departments.update', $department), [
            'fleet_id' => $otherFleet->id,
            'name' => $department->name,
            'status' => 'active',
        ])
        ->assertSessionHasErrors('fleet_id');

    expect($department->fresh()->fleet_id)->toBe($ownFleet->id);
});

test('client preview keeps departments scoped and read only', function () {
    $superadmin = User::factory()->superadmin()->create();
    $ownFleet = Fleet::factory()->create();
    $otherFleet = Fleet::factory()->create();
    $ownDepartment = Department::query()->create([
        'fleet_id' => $ownFleet->id,
        'name' => 'Departement apercu',
        'status' => 'active',
    ]);
    $otherDepartment = Department::query()->create([
        'fleet_id' => $otherFleet->id,
        'name' => 'Departement hors apercu',
        'status' => 'active',
    ]);

    $this->actingAs($superadmin)
        ->withSession([ApplyClientPreview::SESSION_KEY => $ownFleet->id])
        ->get(route('departments.index'))
        ->assertSuccessful()
        ->assertSee($ownDepartment->name)
        ->assertDontSee($otherDepartment->name)
        ->assertDontSee('data-department-create', false)
        ->assertDontSee(route('departments.update', $ownDepartment), false);
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

    $this->actingAs($superadmin)
        ->get(route('drivers.index'))
        ->assertSuccessful()
        ->assertSee('ABCD1234');
});

test('superadmin can replace a driver badge', function () {
    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create();
    $driver = Driver::query()->create([
        'fleet_id' => $fleet->id,
        'first_name' => 'Conducteur',
        'last_name' => 'iButton',
        'status' => 'active',
    ]);
    $oldIdentifier = $driver->identifiers()->create([
        'type' => 'ibutton',
        'uid' => '6C0000028E742F14',
        'active' => true,
    ]);

    $this->actingAs($superadmin)
        ->put(route('drivers.update', $driver), [
            'fleet_id' => $fleet->id,
            'first_name' => 'Conducteur',
            'last_name' => 'iButton',
            'identifier_type' => 'ibutton',
            'rfid_uid' => '38000009A29C2114',
            'status' => 'active',
        ])
        ->assertRedirect(route('drivers.index'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', __('drivers.updated'));

    expect($oldIdentifier->fresh()->active)->toBeFalse();

    $this->assertDatabaseHas('driver_identifiers', [
        'driver_id' => $driver->id,
        'type' => 'ibutton',
        'uid' => '38000009A29C2114',
        'active' => true,
    ]);

    $this->actingAs($superadmin)
        ->get(route('drivers.index'))
        ->assertSuccessful()
        ->assertSee('38000009A29C2114')
        ->assertDontSee('6C0000028E742F14');
});

test('driver badges cannot be duplicated in reversed byte order', function () {
    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create();
    $driver = Driver::query()->create([
        'fleet_id' => $fleet->id,
        'first_name' => 'Premier chauffeur',
        'status' => 'active',
    ]);
    $driver->identifiers()->create([
        'type' => 'ibutton',
        'uid' => '38000009A29C2114',
        'active' => true,
    ]);

    $this->actingAs($superadmin)
        ->post(route('drivers.store'), [
            'fleet_id' => $fleet->id,
            'first_name' => 'Second chauffeur',
            'identifier_type' => 'ibutton',
            'rfid_uid' => '14219CA209000038',
            'status' => 'active',
        ])
        ->assertSessionHasErrors('rfid_uid');

    expect(Driver::query()->where('first_name', 'Second chauffeur')->exists())->toBeFalse();
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
        'uid' => '38000009A29C2114',
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
            'io' => ['ibutton_id' => '14219CA209000038'],
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
        ->and($device->last_driver_identifier_uid)->toBe('38000009A29C2114');

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

test('gps ingestion ignores the all-zero iButton sentinel', function () {
    $fleet = Fleet::factory()->create();
    $vehicle = Vehicle::factory()->create(['fleet_id' => $fleet->id]);
    $device = Device::factory()->create([
        'vehicle_id' => $vehicle->id,
        'fleet_id' => $fleet->id,
    ]);

    $exitCode = Artisan::call('gps:ingest-position', [
        '--payload' => json_encode([
            'imei' => $device->imei,
            'lat' => -4.325,
            'lng' => 15.312,
            'ignition' => true,
            'gps_time' => now()->toIso8601String(),
            'driver_identifier_uid' => '0000000000000000',
        ]),
    ]);

    expect($exitCode)->toBe(0)
        ->and($device->fresh()->last_driver_identifier_uid)->toBeNull()
        ->and(DriverSession::query()->count())->toBe(0)
        ->and(DriverIdentifierUid::normalize('00000000abcd1234'))->toBe('00000000ABCD1234');
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
