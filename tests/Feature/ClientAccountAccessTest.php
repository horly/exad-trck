<?php

use App\Models\Device;
use App\Models\Alert;
use App\Models\Fleet;
use App\Models\Position;
use App\Models\User;
use App\Models\Vehicle;
use App\Http\Middleware\ApplyClientPreview;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('client dashboard and operational lists are limited to the assigned fleet', function () {
    $ownFleet = Fleet::factory()->create(['name' => 'Flotte Client Kinshasa']);
    $otherFleet = Fleet::factory()->create(['name' => 'Flotte Confidentielle']);
    $admin = User::factory()->admin($ownFleet->subscription)->forFleet($ownFleet)->create();

    $ownVehicle = Vehicle::factory()->for($ownFleet)->create(['name' => 'Vehicule Client']);
    $otherVehicle = Vehicle::factory()->for($otherFleet)->create(['name' => 'Vehicule Masque']);
    $ownDevice = Device::factory()->online()->create([
        'subscription_id' => $ownFleet->subscription_id,
        'fleet_id' => $ownFleet->id,
        'vehicle_id' => $ownVehicle->id,
        'name' => 'Nom Technique Secret',
        'imei' => '111111111111111',
    ]);
    Device::factory()->create([
        'subscription_id' => $otherFleet->subscription_id,
        'fleet_id' => $otherFleet->id,
        'vehicle_id' => $otherVehicle->id,
        'name' => 'Traceur Masque',
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Flotte Client Kinshasa')
        ->assertSee('Vehicule Client')
        ->assertDontSee('Nom Technique Secret')
        ->assertDontSee('111111111111111')
        ->assertDontSee(route('trackers.index'), false)
        ->assertDontSee('Traceur Masque');

    $this->actingAs($admin)
        ->get(route('vehicles.index'))
        ->assertSuccessful()
        ->assertSee('Vehicule Client')
        ->assertSee(__('vehicles.tracking_online'))
        ->assertDontSee('111111111111111')
        ->assertDontSee('Vehicule Masque');

    $this->actingAs($admin)
        ->get(route('trackers.index'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('trackers.details', $ownDevice))
        ->assertForbidden();
});

test('client dashboard keeps its sections in the intended visual order', function () {
    $fleet = Fleet::factory()->create();
    $admin = User::factory()->admin($fleet->subscription)->forFleet($fleet)->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSeeInOrder([
            'class="dashboard-topbar"',
            'class="admin-metrics client-dashboard-metrics"',
            'class="admin-panel client-fleet-status-panel"',
            'class="client-dashboard-lower-grid"',
        ], false);

    $css = file_get_contents(public_path('css/dashboard.css'));

    expect($css)
        ->toContain('.client-dashboard-main > .client-fleet-status-panel')
        ->toContain('.client-dashboard-main > .client-dashboard-lower-grid')
        ->toContain('order: 4;')
        ->toContain('order: 5;');
});

test('superadmin can open an isolated client dashboard from the fleet list', function () {
    $superadmin = User::factory()->superadmin()->create();
    $firstFleet = Fleet::factory()->create(['name' => 'Flotte Apercu Kinshasa', 'code' => 'APK']);
    $secondFleet = Fleet::factory()->create(['name' => 'Flotte Externe Goma', 'code' => 'EXT']);
    $firstVehicle = Vehicle::factory()->for($firstFleet)->create(['name' => 'Vehicule Apercu']);
    $secondVehicle = Vehicle::factory()->for($secondFleet)->create(['name' => 'Vehicule Hors Apercu']);
    $firstUser = User::factory()->simpleUser($firstFleet->subscription)->forFleet($firstFleet)->create(['name' => 'Utilisateur Apercu']);
    User::factory()->simpleUser($secondFleet->subscription)->forFleet($secondFleet)->create(['name' => 'Utilisateur Hors Apercu']);
    Device::factory()->online()->create([
        'subscription_id' => $firstFleet->subscription_id,
        'fleet_id' => $firstFleet->id,
        'vehicle_id' => $firstVehicle->id,
    ]);
    Device::factory()->online()->create([
        'subscription_id' => $secondFleet->subscription_id,
        'fleet_id' => $secondFleet->id,
        'vehicle_id' => $secondVehicle->id,
    ]);

    $this->actingAs($superadmin)
        ->get(route('fleets.index'))
        ->assertSuccessful()
        ->assertSee(route('fleets.dashboard', $firstFleet), false)
        ->assertSee(__('fleets.open_client_dashboard', ['fleet' => $firstFleet->name]));

    $this->actingAs($superadmin)
        ->get(route('fleets.dashboard', $firstFleet))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas(ApplyClientPreview::SESSION_KEY, $firstFleet->id);

    $this->actingAs($superadmin)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee(__('dashboard.client_dashboard_title'))
        ->assertSee(__('dashboard.client_preview_label'))
        ->assertSee(__('dashboard.client_preview_readonly'))
        ->assertSee($firstFleet->name)
        ->assertSee($firstVehicle->name)
        ->assertDontSee('Vehicule Hors Apercu')
        ->assertDontSee(route('trackers.index'), false)
        ->assertSeeInOrder([
            'class="dashboard-topbar"',
            'class="client-preview-bar"',
            'class="admin-metrics client-dashboard-metrics"',
            'class="admin-panel client-fleet-status-panel"',
        ], false)
        ->assertSee(route('client-preview.exit'), false);

    $this->actingAs($superadmin)
        ->get(route('vehicles.index'))
        ->assertSuccessful()
        ->assertSee($firstVehicle->name)
        ->assertDontSee('Vehicule Hors Apercu')
        ->assertSee('sidebar-client-preview', false);

    $this->actingAs($superadmin)
        ->get(route('users.index'))
        ->assertSuccessful()
        ->assertSee($firstUser->name)
        ->assertDontSee('Utilisateur Hors Apercu');

    $mapResponse = $this->actingAs($superadmin)
        ->getJson(route('map.devices'))
        ->assertSuccessful()
        ->assertJsonCount(1, 'geojson.features');

    expect($mapResponse->json('geojson.features.0.properties.vehicle'))
        ->toBe($firstVehicle->name);

    $this->actingAs($superadmin)
        ->post(route('garages.store'), [])
        ->assertForbidden();

    $this->actingAs($superadmin)
        ->post(route('client-preview.exit'))
        ->assertRedirect(route('fleets.index'))
        ->assertSessionMissing(ApplyClientPreview::SESSION_KEY);

    $this->actingAs($superadmin)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertDontSee('sidebar-client-preview', false);

    expect(file_get_contents(public_path('css/dashboard.css')))
        ->toContain('.dashboard-shell:has(.sidebar-client-preview)')
        ->toContain('form[method="POST" i]:not([data-inactivity-logout]):not([data-client-preview-exit])');

    $clientAdmin = User::factory()->admin($firstFleet->subscription)->forFleet($firstFleet)->create();

    $this->actingAs($clientAdmin)
        ->get(route('fleets.dashboard', $secondFleet))
        ->assertForbidden();
});

test('client map hides fleet and tracker metadata and only searches vehicles', function () {
    $fleet = Fleet::factory()->create(['name' => 'Flotte Client']);
    $otherFleet = Fleet::factory()->create(['name' => 'Flotte Externe']);
    $admin = User::factory()->admin($fleet->subscription)->forFleet($fleet)->create();
    $vehicle = Vehicle::factory()->for($fleet)->create([
        'name' => 'Toyota Client',
        'registration_number' => 'CLIENT-001',
    ]);
    $otherVehicle = Vehicle::factory()->for($otherFleet)->create(['name' => 'Vehicule Externe']);
    $device = Device::factory()->online()->create([
        'subscription_id' => $fleet->subscription_id,
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'name' => 'Boitier Secret',
        'imei' => '222222222222222',
    ]);
    Device::factory()->online()->create([
        'subscription_id' => $otherFleet->subscription_id,
        'fleet_id' => $otherFleet->id,
        'vehicle_id' => $otherVehicle->id,
    ]);

    $this->actingAs($admin)
        ->get(route('map.index'))
        ->assertSuccessful()
        ->assertDontSee('data-map-fleet', false)
        ->assertSee(__('map.client_search'))
        ->assertSee('tracker-details.js', false)
        ->assertSee('trackerDetailsModal', false);

    $response = $this->actingAs($admin)
        ->getJson(route('map.devices', ['fleet_id' => $otherFleet->id]))
        ->assertSuccessful();

    expect($response->json('geojson.features'))->toHaveCount(1);
    $properties = $response->json('geojson.features.0.properties');
    expect($properties['vehicle'])->toBe('Toyota Client')
        ->and($properties['id'])->toBe('vehicle-'.$vehicle->id)
        ->and($properties['trips_url'])->toContain('/vehicles/'.$vehicle->id.'/trips')
        ->and($properties['trips_url'])->not->toContain('/trackers/')
        ->and($properties['details_url'])->toContain('/vehicles/'.$vehicle->id.'/tracker-details')
        ->and($properties['details_url'])->not->toContain('/trackers/')
        ->and($properties)->not->toHaveKeys(['imei', 'name', 'brand', 'model']);

    $detailsResponse = $this->actingAs($admin)
        ->getJson(route('vehicles.tracker-details', $vehicle))
        ->assertSuccessful()
        ->assertJsonStructure(['html']);

    expect($detailsResponse->json('html'))
        ->toContain('Toyota Client')
        ->toContain(__('trackers.location_title'))
        ->not->toContain('FTC920')
        ->not->toContain(__('trackers.detail_model', ['model' => '']))
        ->not->toContain('222222222222222')
        ->not->toContain('device='.$device->id)
        ->not->toContain('/trackers/'.$device->id);

    $this->actingAs($admin)
        ->getJson(route('vehicles.tracker-details', $otherVehicle))
        ->assertForbidden();

    $this->actingAs($admin)
        ->getJson(route('map.devices', ['search' => '222222222222222']))
        ->assertJsonCount(0, 'geojson.features');

    $this->actingAs($admin)
        ->getJson(route('map.devices', ['search' => 'CLIENT-001']))
        ->assertJsonCount(1, 'geojson.features');

    $this->actingAs($admin)
        ->getJson(route('vehicles.trips', $vehicle))
        ->assertSuccessful();

    $this->actingAs($admin)
        ->getJson(route('trackers.trips', $device))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('events.index'))
        ->assertSuccessful()
        ->assertDontSee('Boitier Secret')
        ->assertDontSee('222222222222222');
});

test('client alerts and reports never expose tracker technical data', function () {
    $fleet = Fleet::factory()->create(['name' => 'Flotte Client Rapports']);
    $admin = User::factory()->admin($fleet->subscription)->forFleet($fleet)->create();
    $vehicle = Vehicle::factory()->for($fleet)->create([
        'name' => 'Vehicule Rapport Client',
        'registration_number' => 'RPT-CLIENT',
    ]);
    $device = Device::factory()->create([
        'subscription_id' => $fleet->subscription_id,
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'name' => 'Nom Traceur Confidentiel',
        'imei' => '333333333333333',
    ]);

    Position::factory()->forDevice($device)->create([
        'address' => 'Adresse rapport client',
        'server_time' => now(),
    ]);
    Alert::query()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'device_id' => $device->id,
        'type' => 'no_signal',
        'severity' => 'high',
        'status' => 'new',
        'title' => 'No signal',
        'message' => 'Nom Traceur Confidentiel is no longer transmitting signal.',
        'occurred_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('alerts.index', ['search' => '333333333333333']))
        ->assertSuccessful()
        ->assertDontSee('Nom Traceur Confidentiel')
        ->assertSee(__('alerts.empty'));

    $this->actingAs($admin)
        ->get(route('alerts.index'))
        ->assertSuccessful()
        ->assertSee(__('alerts.message_no_signal_client', ['vehicle' => $vehicle->name]))
        ->assertDontSee('Nom Traceur Confidentiel')
        ->assertDontSee('333333333333333');

    $this->actingAs($admin)
        ->getJson(route('alerts.recent'))
        ->assertSuccessful()
        ->assertJsonMissing(['message' => 'Nom Traceur Confidentiel is no longer transmitting signal.'])
        ->assertJsonPath('alerts.0.message', __('alerts.message_no_signal_client', ['vehicle' => $vehicle->name]));

    $this->actingAs($admin)
        ->get(route('reports.index', [
            'device_id' => $device->id,
        ]))
        ->assertSuccessful()
        ->assertDontSee('name="device_id"', false)
        ->assertDontSee('name="fleet_id"', false)
        ->assertDontSee('Nom Traceur Confidentiel')
        ->assertDontSee('333333333333333');

    $this->actingAs($admin)
        ->get(route('reports.index', ['search' => '333333333333333']))
        ->assertSuccessful()
        ->assertSee(__('reports.empty'))
        ->assertDontSee('Nom Traceur Confidentiel');

    $csv = $this->actingAs($admin)
        ->get(route('reports.export', ['type' => 'positions', 'period' => 'week']))
        ->assertSuccessful()
        ->streamedContent();

    expect($csv)
        ->toContain('Vehicule Rapport Client')
        ->not->toContain('Nom Traceur Confidentiel')
        ->not->toContain('333333333333333');
});

test('simple user client permissions control optional modules', function () {
    $fleet = Fleet::factory()->create();
    $user = User::factory()->simpleUser($fleet->subscription)->forFleet($fleet)->create([
        'permissions' => [User::PERMISSION_MAP_VIEW],
    ]);

    $this->actingAs($user)->get(route('dashboard'))->assertSuccessful();
    $this->actingAs($user)->get(route('map.index'))->assertSuccessful();
    $this->actingAs($user)->get(route('reports.index'))->assertForbidden();
    $this->actingAs($user)->get(route('garages.index'))->assertForbidden();
    $this->actingAs($user)->get(route('maintenance.index'))->assertForbidden();
    $this->actingAs($user)->get(route('users.index'))->assertForbidden();
    $this->actingAs($user)->get(route('fleets.index'))->assertForbidden();
});

test('fleet admin has all client permissions and creates only users in its fleet', function () {
    $ownFleet = Fleet::factory()->create(['name' => 'Flotte Admin']);
    $otherFleet = Fleet::factory()->create();
    $admin = User::factory()->admin($ownFleet->subscription)->forFleet($ownFleet)->create();

    expect($admin->hasClientPermission(User::PERMISSION_MAP_VIEW))->toBeTrue()
        ->and($admin->hasClientPermission(User::PERMISSION_REPORTS_GENERATE))->toBeTrue()
        ->and($admin->hasClientPermission(User::PERMISSION_GARAGES_MANAGE))->toBeTrue()
        ->and($admin->hasClientPermission(User::PERMISSION_MAINTENANCE_MANAGE))->toBeTrue();

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertSuccessful()
        ->assertSee('Flotte Admin')
        ->assertDontSee('name="fleet_id"', false);

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Utilisateur Client',
            'email' => 'client-user@example.com',
            'password' => 'ClientPassword@123',
            'password_confirmation' => 'ClientPassword@123',
            'role' => 'admin',
            'fleet_id' => $otherFleet->id,
            'permissions' => [
                User::PERMISSION_MAP_VIEW,
                User::PERMISSION_REPORTS_GENERATE,
            ],
        ])
        ->assertRedirect(route('users.index'));

    $createdUser = User::query()->where('email', 'client-user@example.com')->firstOrFail();

    expect($createdUser->role->value)->toBe('user')
        ->and($createdUser->fleet_id)->toBe($ownFleet->id)
        ->and($createdUser->created_by)->toBe($admin->id)
        ->and($createdUser->permissions)->toBe([
            User::PERMISSION_MAP_VIEW,
            User::PERMISSION_REPORTS_GENERATE,
        ]);
});

test('fleet admin cannot manage a user from another fleet or create core resources', function () {
    $ownFleet = Fleet::factory()->create();
    $otherFleet = Fleet::factory()->create();
    $admin = User::factory()->admin($ownFleet->subscription)->forFleet($ownFleet)->create();
    $otherUser = User::factory()->simpleUser($otherFleet->subscription)->forFleet($otherFleet)->create();

    $this->actingAs($admin)
        ->put(route('users.update', $otherUser), [
            'name' => 'Modification Interdite',
            'email' => $otherUser->email,
            'role' => 'user',
        ])
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('vehicles.store'), [
            'fleet_id' => $ownFleet->id,
            'name' => 'Vehicule Interdit',
        ])
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('trackers.store'), [
            'fleet_id' => $ownFleet->id,
            'imei' => '123456789012345',
        ])
        ->assertForbidden();
});

test('client garage writes stay in the assigned fleet and maintenance rejects foreign vehicles', function () {
    $ownFleet = Fleet::factory()->create();
    $otherFleet = Fleet::factory()->create();
    $admin = User::factory()->admin($ownFleet->subscription)->forFleet($ownFleet)->create();
    $foreignVehicle = Vehicle::factory()->for($otherFleet)->create();

    $this->actingAs($admin)
        ->post(route('garages.store'), [
            'name' => 'Garage Client',
            'type' => 'internal',
            'status' => 'active',
        ])
        ->assertRedirect(route('garages.index'));

    $this->assertDatabaseHas('garages', [
        'name' => 'Garage Client',
        'fleet_id' => $ownFleet->id,
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->post(route('maintenance.store'), [
            'vehicle_id' => $foreignVehicle->id,
            'name' => 'Entretien Interdit',
            'maintenance_type' => 'corrective',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('maintenance_plans', ['name' => 'Entretien Interdit']);
});

test('superadmin assigns new admins to exactly one fleet', function () {
    $fleet = Fleet::factory()->create();
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)
        ->post(route('users.store'), [
            'name' => 'Admin Flotte',
            'email' => 'fleet-admin@example.com',
            'password' => 'AdminPassword@123',
            'password_confirmation' => 'AdminPassword@123',
            'role' => 'admin',
            'fleet_id' => $fleet->id,
        ])
        ->assertRedirect(route('users.index'));

    $admin = User::query()->where('email', 'fleet-admin@example.com')->firstOrFail();

    expect($admin->fleet_id)->toBe($fleet->id)
        ->and($admin->subscription_id)->toBe($fleet->subscription_id)
        ->and($admin->fleets()->count())->toBe(1)
        ->and($admin->fleets()->firstOrFail()->pivot->permission)->toBe('manager');
});
