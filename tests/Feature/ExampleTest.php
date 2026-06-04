<?php

use App\Models\Device;
use App\Models\Alert;
use App\Models\Fleet;
use App\Models\Position;
use App\Models\Subscription;
use App\Models\TrackerEvent;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('guests are redirected from the home page to login', function () {
    $this->get('/')
        ->assertRedirect(route('login'));
});

test('users can switch the login language from the language button', function () {
    $this->from(route('login'))
        ->get(route('lang.switch', 'en'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('locale', 'en');

    $this->withSession(['locale' => 'en'])
        ->get(route('login'))
        ->assertSuccessful()
        ->assertSee('Sign in to EXAD Tracking')
        ->assertSee('English');
});

test('unsupported locales are not accepted', function () {
    $this->get('/lang/de')
        ->assertNotFound();
});

test('login validation messages are translated in french', function () {
    $this->withSession(['locale' => 'fr'])
        ->from(route('login'))
        ->post(route('login'), [
            'email' => '',
            'password' => '',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'email' => 'Le champ adresse email est obligatoire.',
            'password' => 'Le champ mot de passe est obligatoire.',
        ]);
});

test('authenticated users can view dashboard metrics', function () {
    $subscription = Subscription::factory()->create();
    $user = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create(['subscription_id' => null]);
    $onlineDevice = Device::factory()->online()->create([
        'subscription_id' => $subscription->id,
        'name' => 'Camion Kin 01',
    ]);
    $movingDevice = Device::factory()->moving()->create([
        'subscription_id' => $subscription->id,
        'name' => 'Pick-up Gombe',
    ]);

    Position::factory()->forDevice($onlineDevice)->create(['server_time' => now()]);
    Position::factory()->forDevice($movingDevice)->create(['server_time' => now()->subDay()]);
    Vehicle::factory()->create([
        'fleet_id' => $fleet->id,
        'name' => 'Toyota Hilux terrain',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Tableau de bord')
        ->assertSee('Véhicules')
        ->assertSee('Camion Kin 01')
        ->assertSee('Pick-up Gombe')
        ->assertSee('Traceurs')
        ->assertSee('Positions du jour')
        ->assertSee('Évolution des positions');
});

test('superadmin console pages load realtime alert toasts globally', function () {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('data-alert-live-toast', false)
        ->assertSee('exadRealtimeConfig', false)
        ->assertSee('alerts-realtime.js', false);

    $this->actingAs($superadmin)
        ->get(route('map.index'))
        ->assertSuccessful()
        ->assertSee('data-alert-live-toast', false)
        ->assertSee('alerts-realtime.js', false);
});

test('superadmin topbar shows new alerts notification count', function () {
    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create();

    Alert::query()->create([
        'fleet_id' => $fleet->id,
        'type' => 'no_signal',
        'severity' => 'high',
        'status' => 'new',
        'title' => 'Alerte nouvelle',
        'message' => 'Message nouvelle',
        'occurred_at' => now(),
    ]);

    Alert::query()->create([
        'fleet_id' => $fleet->id,
        'type' => 'signal_recovered',
        'severity' => 'medium',
        'status' => 'acknowledged',
        'title' => 'Alerte traitee',
        'message' => 'Message traitee',
        'occurred_at' => now(),
    ]);

    $this->actingAs($superadmin)
        ->withSession(['locale' => 'fr'])
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('alert-notification-button', false)
        ->assertSee('data-alert-notification-count', false)
        ->assertSee('1 nouvelle alerte')
        ->assertSee('>1</span>', false)
        ->assertSeeInOrder(['data-fullscreen-toggle', 'data-theme-toggle', 'alert-notification-button', 'dashboard-language-menu', 'user-pill'], false)
        ->assertSee(route('alerts.index'), false);
});

test('map alerts and customization pages display the shared sidebar version', function () {
    $superadmin = User::factory()->superadmin()->create();

    foreach ([route('map.index'), route('alerts.index'), route('customization.index')] as $url) {
        $this->actingAs($superadmin)
            ->get($url)
            ->assertSuccessful()
            ->assertSee('EXAD Tracking - v.1.0')
            ->assertSee('dashboard.css', false)
            ->assertSee('sidebar-version', false);
    }
});

test('admin users are redirected from home to fleets', function () {
    $subscription = Subscription::factory()->create();
    $admin = User::factory()->admin($subscription)->create();

    $this->actingAs($admin)
        ->get('/')
        ->assertRedirect(route('fleets.index'));
});

test('superadmin users are redirected from home to dashboard', function () {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)
        ->get('/')
        ->assertRedirect(route('dashboard'));
});

test('non superadmin users cannot access superadmin fleet console', function () {
    $ownSubscription = Subscription::factory()->create();
    $admin = User::factory()->admin($ownSubscription)->create();
    $user = User::factory()->simpleUser($ownSubscription)->create();

    $this->actingAs($admin)
        ->get(route('fleets.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('fleets.index'))
        ->assertForbidden();
});

test('fleets table uses shared datatable interactions', function () {
    $subscription = Subscription::factory()->create();
    $admin = User::factory()->admin($subscription)->create();
    $superadmin = User::factory()->superadmin()->create();

    foreach (range(1, 6) as $index) {
        $fleet = Fleet::factory()->create([
            'subscription_id' => null,
            'name' => "Flotte {$index}",
            'code' => "FLT-{$index}",
            'created_at' => now()->subDays($index),
        ]);

        $fleet->users()->attach($admin->id, ['permission' => 'manager']);

        if ($index === 1) {
            Vehicle::factory()->create([
                'fleet_id' => $fleet->id,
                'subscription_plan' => 'premium',
            ]);
            Vehicle::factory()->create([
                'fleet_id' => $fleet->id,
                'subscription_plan' => 'basic',
            ]);
        }
    }

    $this->actingAs($superadmin)
        ->get(route('fleets.index'))
        ->assertSuccessful()
        ->assertSee('data-datatable-search-form', false)
        ->assertSee('data-datatable-search', false)
        ->assertSee('datatable-sort-link', false)
        ->assertSee('5 / 6 lignes')
        ->assertSee('Affichage de 1 à 5 sur 6')
        ->assertSee('datatable-pagination', false)
        ->assertSee('Flotte 1')
        ->assertSee('>2</td>', false)
        ->assertSee('>1</td>', false)
        ->assertDontSee('Flotte 6')
        ->assertSee('data-confirm-delete', false)
        ->assertSee('Supprimer cette flotte ?', false);

    $response = $this->actingAs($superadmin)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('fleets.index', ['search' => 'Flotte 1']))
        ->assertSuccessful()
        ->assertJsonStructure(['html']);

    expect($response->json('html'))
        ->toContain('Flotte 1')
        ->toContain('data-datatable-sort');
});

test('vehicles table uses shared datatable interactions and fleet access', function () {
    $subscription = Subscription::factory()->create();
    $admin = User::factory()->admin($subscription)->create();
    $superadmin = User::factory()->superadmin()->create();

    $fleet = Fleet::factory()->create(['subscription_id' => null, 'name' => 'Flotte véhicules']);
    $fleet->users()->attach([
        $admin->id => ['permission' => 'manager'],
    ]);

    foreach (range(1, 6) as $index) {
        Vehicle::factory()->create([
            'fleet_id' => $fleet->id,
            'name' => "Véhicule {$index}",
            'registration_number' => "KIN-{$index}",
            'created_at' => now()->subDays($index),
        ]);
    }

    $this->actingAs($superadmin)
        ->get(route('vehicles.index'))
        ->assertSuccessful()
        ->assertSee('data-datatable-search-form', false)
        ->assertSee('data-datatable-search', false)
        ->assertSee('datatable-sort-link', false)
        ->assertSee('5 / 6 lignes')
        ->assertSee('Affichage de 1 à 5 sur 6')
        ->assertSee('datatable-pagination', false)
        ->assertSee('Véhicule 1')
        ->assertDontSee('Véhicule 6')
        ->assertSee('data-confirm-delete', false)
        ->assertSee('Supprimer ce véhicule ?', false);

    $response = $this->actingAs($superadmin)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('vehicles.index', ['search' => 'KIN-6']))
        ->assertSuccessful()
        ->assertJsonStructure(['html']);

    expect($response->json('html'))
        ->toContain('Véhicule 6')
        ->toContain('data-datatable-sort');
});

test('superadmin can create and delete vehicles in managed fleets', function () {
    $subscription = Subscription::factory()->create();
    $admin = User::factory()->admin($subscription)->create();
    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create(['subscription_id' => null]);
    $fleet->users()->attach($admin->id, ['permission' => 'manager']);

    $this->actingAs($superadmin)
        ->post(route('vehicles.store'), [
            'fleet_id' => $fleet->id,
            'name' => 'Toyota Hilux terrain',
            'registration_number' => 'KIN-2026',
            'brand' => 'Toyota',
            'model' => 'Hilux',
            'vehicle_type' => 'truck',
            'subscription_plan' => 'premium',
            'status' => 'active',
        ])
        ->assertRedirect(route('vehicles.index'))
        ->assertSessionHas('status', __('vehicles.created'));

    $vehicle = Vehicle::query()->where('registration_number', 'KIN-2026')->first();

    expect($vehicle)
        ->not->toBeNull()
        ->and($vehicle->fleet_id)->toBe($fleet->id)
        ->and($vehicle->subscription_plan)->toBe('premium');

    $this->actingAs($superadmin)
        ->delete(route('vehicles.destroy', $vehicle))
        ->assertRedirect(route('vehicles.index'))
        ->assertSessionHas('status_type', 'danger')
        ->assertSessionHas('status', __('vehicles.deleted'));

    $this->assertModelMissing($vehicle);
});

test('trackers table uses shared datatable interactions and fleet access', function () {
    $subscription = Subscription::factory()->create();
    $admin = User::factory()->admin($subscription)->create();
    $superadmin = User::factory()->superadmin()->create();

    $fleet = Fleet::factory()->create(['subscription_id' => null, 'name' => 'Flotte traceurs']);
    $fleet->users()->attach([
        $admin->id => ['permission' => 'manager'],
    ]);
    $vehicle = Vehicle::factory()->create(['fleet_id' => $fleet->id, 'name' => 'Toyota Hilux traceur']);

    foreach (range(1, 6) as $index) {
        Device::factory()->create([
            'subscription_id' => null,
            'fleet_id' => $fleet->id,
            'vehicle_id' => $vehicle->id,
            'imei' => "3563070424410{$index}",
            'name' => "Traceur {$index}",
            'created_at' => now()->subDays($index),
        ]);
    }

    $this->actingAs($superadmin)
        ->get(route('trackers.index'))
        ->assertSuccessful()
        ->assertSee('data-datatable-search-form', false)
        ->assertSee('data-datatable-search', false)
        ->assertSee('datatable-sort-link', false)
        ->assertSee('5 / 6 lignes')
        ->assertSee('Affichage de 1 à 5 sur 6')
        ->assertSee('Traceur 1')
        ->assertDontSee('Traceur 6')
        ->assertSee('data-confirm-delete', false)
        ->assertSee('data-trips-open', false)
        ->assertSee('trackerTripsModal', false)
        ->assertSee('Supprimer ce traceur ?', false);

    $response = $this->actingAs($superadmin)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('trackers.index', ['search' => '35630704244106']))
        ->assertSuccessful()
        ->assertJsonStructure(['html']);

    expect($response->json('html'))
        ->toContain('Traceur 6')
        ->toContain('data-datatable-sort');
});

test('superadmin can create and delete trackers for managed vehicles', function () {
    $subscription = Subscription::factory()->create();
    $admin = User::factory()->admin($subscription)->create();
    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create(['subscription_id' => null]);
    $fleet->users()->attach($admin->id, ['permission' => 'manager']);
    $vehicle = Vehicle::factory()->create(['fleet_id' => $fleet->id]);
    $assignedVehicle = Vehicle::factory()->create([
        'fleet_id' => $fleet->id,
        'name' => 'Vehicule deja equipe',
    ]);
    Device::factory()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $assignedVehicle->id,
        'imei' => '356307042441099',
    ]);

    $this->actingAs($superadmin)
        ->get(route('trackers.index'))
        ->assertSuccessful()
        ->assertSee('data-vehicle-assigned="true"', false);

    $this->actingAs($superadmin)
        ->post(route('trackers.store'), [
            'vehicle_id' => $assignedVehicle->id,
            'imei' => '356307042441014',
            'name' => 'Traceur refuse',
            'brand' => 'teltonika',
            'model' => 'FMB920',
            'protocol' => 'TCP',
        ])
        ->assertSessionHasErrors('vehicle_id');

    $this->actingAs($superadmin)
        ->post(route('trackers.store'), [
            'vehicle_id' => $vehicle->id,
            'imei' => '356307042441013',
            'name' => 'Traceur Hilux',
            'brand' => 'teltonika',
            'model' => 'FMB920',
            'sim_number' => '+243000000000',
            'operator_name' => 'Vodacom',
            'protocol' => 'TCP',
        ])
        ->assertRedirect(route('trackers.index'))
        ->assertSessionHas('status', __('trackers.created'));

    $device = Device::query()->where('imei', '356307042441013')->first();

    expect($device)
        ->not->toBeNull()
        ->and($device->vehicle_id)->toBe($vehicle->id)
        ->and($device->brand)->toBe('teltonika')
        ->and($device->model)->toBe('FMB920')
        ->and($device->operator_name)->toBe('Vodacom')
        ->and($device->status)->toBe('inactive')
        ->and($device->fleet_id)->toBe($fleet->id)
        ->and($device->subscription_id)->toBe($subscription->id);

    $this->actingAs($superadmin)
        ->delete(route('trackers.destroy', $device))
        ->assertRedirect(route('trackers.index'))
        ->assertSessionHas('status_type', 'danger')
        ->assertSessionHas('status', __('trackers.deleted'));

    $this->assertModelMissing($device);
});

test('local gps listener commands update registered trackers only', function () {
    $device = Device::factory()->create([
        'imei' => '356307042441013',
        'status' => 'inactive',
        'last_seen_at' => null,
        'last_latitude' => null,
        'last_longitude' => null,
        'last_movement' => null,
        'last_ignition' => null,
    ]);

    $exitCode = Artisan::call('gps:ingest-position', [
        '--payload' => json_encode([
            'imei' => '356307042441013',
            'lat' => -4.325,
            'lng' => 15.312,
            'speed' => 42,
            'angle' => 90,
            'satellites' => 12,
            'gsm_signal' => 80,
            'battery_level' => 92,
            'external_voltage' => 12.4,
            'battery_voltage' => 4.1,
            'address' => 'Kinsuka Pecheur, Ngaliema, Kinshasa',
        ]),
    ]);

    $device->refresh();

    expect($exitCode)
        ->toBe(0)
        ->and($device->status)->toBe('online')
        ->and((float) $device->last_latitude)->toBe(-4.325)
        ->and((float) $device->last_longitude)->toBe(15.312)
        ->and($device->last_speed)->toBe(42)
        ->and($device->last_angle)->toBe(90)
        ->and($device->last_movement)->toBeTrue()
        ->and($device->last_satellites)->toBe(12)
        ->and($device->last_gsm_signal)->toBe(80)
        ->and($device->last_battery_level)->toBe(92)
        ->and((float) $device->last_external_voltage)->toBe(12.4)
        ->and((float) $device->last_battery_voltage)->toBe(4.1)
        ->and($device->last_address)->toBe('Kinsuka Pecheur, Ngaliema, Kinshasa');

    $this->assertDatabaseHas('positions', [
        'device_id' => $device->id,
        'imei' => '356307042441013',
        'speed' => 42,
        'angle' => 90,
    ]);

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->id,
        'type' => 'signal_recovered',
        'severity' => 'medium',
    ]);

    $this->assertDatabaseHas('tracker_events', [
        'device_id' => $device->id,
        'type' => 'signal_restored',
    ]);

    $this->assertDatabaseHas('tracker_events', [
        'device_id' => $device->id,
        'type' => 'movement_started',
    ]);

    $secondCode = Artisan::call('gps:ingest-position', [
        '--payload' => json_encode([
            'imei' => '356307042441013',
            'lat' => -4.326,
            'lng' => 15.313,
            'speed' => 12,
        ]),
    ]);

    expect($secondCode)->toBe(0)
        ->and(Alert::query()->where('device_id', $device->id)->where('type', 'signal_recovered')->count())->toBe(1);

    $stoppedCode = Artisan::call('gps:ingest-position', [
        '--payload' => json_encode([
            'imei' => '356307042441013',
            'lat' => -4.327,
            'lng' => 15.314,
            'speed' => 0,
            'movement' => false,
        ]),
    ]);

    expect($stoppedCode)->toBe(0)
        ->and(TrackerEvent::query()->where('device_id', $device->id)->where('type', 'movement_stopped')->exists())->toBeTrue();

    $unknownCode = Artisan::call('gps:ingest-position', [
        '--payload' => json_encode([
            'imei' => '000000000000000',
            'lat' => -4.325,
            'lng' => 15.312,
        ]),
    ]);

    expect($unknownCode)->toBe(2);
});

test('local gps stale command marks silent online trackers offline', function () {
    $device = Device::factory()->create([
        'status' => 'online',
        'last_seen_at' => now()->subMinutes(10),
    ]);

    $exitCode = Artisan::call('gps:mark-stale', ['--minutes' => 5]);

    expect($exitCode)->toBe(0)
        ->and($device->refresh()->status)->toBe('offline');

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->id,
        'type' => 'no_signal',
        'severity' => 'high',
    ]);

    $this->assertDatabaseHas('tracker_events', [
        'device_id' => $device->id,
        'type' => 'signal_lost',
    ]);
});

test('superadmin can open tracker details with fleet and latest events', function () {
    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create(['name' => 'EXAD CARS', 'code' => 'EX-CRS']);
    $vehicle = Vehicle::factory()->create([
        'fleet_id' => $fleet->id,
        'name' => 'Suzuki Swift Horly',
        'registration_number' => '6823BV01',
    ]);
    $device = Device::factory()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'brand' => 'teltonika',
        'model' => 'FMB003',
        'imei' => '353201355315547',
        'status' => 'online',
        'last_latitude' => -4.33509,
        'last_longitude' => 15.22408,
        'last_gsm_signal' => 80,
        'last_battery_level' => 76,
        'last_external_voltage' => 12.6,
        'last_battery_voltage' => 4.05,
        'last_movement' => false,
        'operator_name' => 'Vodacom',
    ]);

    TrackerEvent::query()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'device_id' => $device->id,
        'type' => 'movement_started',
        'title' => __('trackers.event_movement_started_title'),
        'message' => __('trackers.event_movement_started_message', ['vehicle' => $vehicle->name]),
        'started_at' => now(),
        'metadata' => [
            'translation' => [
                'title_key' => 'trackers.event_movement_started_title',
                'message_key' => 'trackers.event_movement_started_message',
                'replace' => ['vehicle' => $vehicle->name],
            ],
        ],
    ]);

    $response = $this->actingAs($superadmin)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('trackers.details', $device))
        ->assertSuccessful()
        ->assertJsonStructure(['html']);

    expect($response->json('html'))
        ->toContain('Flotte : EXAD CARS')
        ->toContain('Suzuki Swift Horly')
        ->toContain('Alimentation')
        ->toContain('Parking')
        ->toContain('Tension externe : 12.6 V')
        ->toContain('Vodacom')
        ->toContain('Début de déplacement')
        ->not->toContain('Groupe');
});

test('superadmin can display tracker trips as html and geojson', function () {
    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create(['name' => 'EXAD CARS']);
    $vehicle = Vehicle::factory()->create([
        'fleet_id' => $fleet->id,
        'name' => 'Toyota Trajet',
        'registration_number' => '1234BV01',
    ]);
    $device = Device::factory()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'imei' => '356307042441013',
    ]);

    $points = [
        ['time' => now()->setTime(8, 0), 'lat' => -4.33000, 'lng' => 15.22000, 'address' => 'Kinsuka Pecheur, Ngaliema'],
        ['time' => now()->setTime(8, 8), 'lat' => -4.33100, 'lng' => 15.22500, 'address' => 'Avenue de l’OUA, Kinshasa'],
        ['time' => now()->setTime(8, 16), 'lat' => -4.33500, 'lng' => 15.23200, 'address' => 'Centre cité, Avenue Kasa-Vubu'],
    ];

    foreach ($points as $point) {
        Position::factory()->forDevice($device)->create([
            'server_time' => $point['time'],
            'gps_time' => $point['time'],
            'latitude' => $point['lat'],
            'longitude' => $point['lng'],
            'address' => $point['address'],
            'movement' => true,
            'speed' => 28,
        ]);
    }

    $response = $this->actingAs($superadmin)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('trackers.trips', ['device' => $device, 'period' => 'today']))
        ->assertSuccessful()
        ->assertJsonStructure(['html', 'geojson', 'summary'])
        ->assertJsonPath('geojson.type', 'FeatureCollection')
        ->assertJsonPath('summary.count', 1);

    expect($response->json('html'))
        ->toContain('Aujourd’hui')
        ->toContain('Kinsuka Pecheur')
        ->toContain('Centre cité')
        ->toContain('Total : 1 trajets')
        ->and($response->json('geojson.features.0.geometry.type'))->toBe('LineString');
});

test('tracker trips resolve missing addresses with mapbox reverse geocoding', function () {
    Http::fake([
        'api.mapbox.com/search/geocode/v6/reverse*' => Http::response([
            'features' => [
                [
                    'properties' => [
                        'full_address' => 'Avenue de l’OUA, Ngaliema, Kinshasa, Congo-Kinshasa',
                    ],
                ],
            ],
        ]),
    ]);

    $superadmin = User::factory()->superadmin()->create();
    $device = Device::factory()->create();
    $start = Position::factory()->forDevice($device)->create([
        'server_time' => now()->setTime(9, 0),
        'gps_time' => now()->setTime(9, 0),
        'latitude' => -4.3414,
        'longitude' => 15.2867,
        'address' => null,
        'movement' => true,
        'speed' => 20,
        'raw_data' => null,
    ]);
    Position::factory()->forDevice($device)->create([
        'server_time' => now()->setTime(9, 8),
        'gps_time' => now()->setTime(9, 8),
        'latitude' => -4.3420,
        'longitude' => 15.2872,
        'address' => null,
        'movement' => true,
        'speed' => 18,
        'raw_data' => null,
    ]);

    $response = $this->actingAs($superadmin)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('trackers.trips', ['device' => $device, 'period' => 'today']))
        ->assertSuccessful();

    expect($response->json('html'))
        ->toContain('Avenue de l’OUA')
        ->not->toContain('Latitude : -4.3414000');

    expect($start->refresh()->address)->toBe('Avenue de l’OUA, Ngaliema, Kinshasa, Congo-Kinshasa');
});

test('authenticated users can view the map page with local mapbox assets', function () {
    $user = User::factory()->superadmin()->create();

    $this->actingAs($user)
        ->get(route('map.index'))
        ->assertSuccessful()
        ->assertSee('vendor/mapbox/mapbox-gl.css', false)
        ->assertSee('vendor/mapbox/mapbox-gl.js', false)
        ->assertSee('js/map.js', false)
        ->assertSee('trackerDetailsModal', false)
        ->assertSee('js/tracker-details.js', false)
        ->assertDontSee('https://api.mapbox.com/mapbox-gl-js', false)
        ->assertSee('exadMapConfig', false);
});

test('map devices endpoint returns geojson for every positioned tracker to superadmin', function () {
    $subscription = Subscription::factory()->create();
    $admin = User::factory()->admin($subscription)->create();
    $otherAdmin = User::factory()->admin()->create();
    $superadmin = User::factory()->superadmin()->create();

    $fleet = Fleet::factory()->create(['subscription_id' => null, 'name' => 'Flotte carte']);
    $fleet->users()->attach($admin->id, ['permission' => 'manager']);
    $vehicle = Vehicle::factory()->create([
        'fleet_id' => $fleet->id,
        'name' => 'Toyota Carte',
        'registration_number' => 'MAP-001',
    ]);

    Device::factory()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'imei' => '356307042441013',
        'name' => 'Traceur Carte',
        'status' => 'online',
        'last_latitude' => -4.325,
        'last_longitude' => 15.312,
        'last_speed' => 42,
        'last_angle' => 90,
        'last_seen_at' => now(),
    ]);

    $hiddenFleet = Fleet::factory()->create(['subscription_id' => null, 'name' => 'Flotte cachee carte']);
    $hiddenFleet->users()->attach($otherAdmin->id, ['permission' => 'manager']);
    $hiddenVehicle = Vehicle::factory()->create(['fleet_id' => $hiddenFleet->id]);
    Device::factory()->create([
        'fleet_id' => $hiddenFleet->id,
        'vehicle_id' => $hiddenVehicle->id,
        'imei' => '356307042449999',
        'status' => 'online',
        'last_latitude' => -4.4,
        'last_longitude' => 15.4,
    ]);

    $response = $this->actingAs($superadmin)
        ->getJson(route('map.devices'))
        ->assertSuccessful()
        ->assertJsonPath('geojson.type', 'FeatureCollection')
        ->assertJsonPath('summary.total', 2)
        ->assertJsonPath('summary.positioned', 2);

    expect($response->json('geojson.features'))
        ->toHaveCount(2)
        ->and(collect($response->json('geojson.features'))->pluck('properties.vehicle')->all())
        ->toContain('Toyota Carte')
        ->and($response->json('geojson.features.0.properties.details_url'))->toContain('/trackers/')
        ->and($response->json('geojson.features.0.properties.trips_url'))->toContain('/trackers/');
});

test('superadmin can view alerts page with local realtime client and datatable', function () {
    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create(['name' => 'Flotte alertes']);
    $vehicle = Vehicle::factory()->create(['fleet_id' => $fleet->id, 'name' => 'Toyota Alerte']);
    $device = Device::factory()->create(['fleet_id' => $fleet->id, 'vehicle_id' => $vehicle->id]);

    foreach (range(1, 6) as $index) {
        Alert::query()->create([
            'fleet_id' => $fleet->id,
            'vehicle_id' => $vehicle->id,
            'device_id' => $device->id,
            'type' => 'no_signal',
            'severity' => $index === 1 ? 'critical' : 'high',
            'status' => 'new',
            'title' => 'Alerte '.$index,
            'message' => 'Message alerte '.$index,
            'occurred_at' => now()->subMinutes($index),
        ]);
    }

    $this->actingAs($superadmin)
        ->get(route('alerts.index'))
        ->assertSuccessful()
        ->assertSee('Alertes')
        ->assertSee('alerts-realtime.js', false)
        ->assertSee('recentEndpoint', false)
        ->assertSee('app-toast-info', false)
        ->assertDontSee('data-realtime-status', false)
        ->assertDontSee('Temps réel indisponible')
        ->assertSee('data-datatable-search-form', false)
        ->assertSee('datatable-sort-link', false)
        ->assertSee('5 / 6 lignes')
        ->assertSee('Affichage de 1 à 5 sur 6')
        ->assertSee('Alerte 1')
        ->assertDontSee('Alerte 6');

    $response = $this->actingAs($superadmin)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('alerts.index', ['search' => 'Alerte 6']))
        ->assertSuccessful()
        ->assertJsonStructure(['html', 'stats']);

    expect($response->json('html'))
        ->toContain('Alerte 6')
        ->toContain('data-datatable-sort');
});

test('processed alerts are always listed after new alerts', function () {
    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create();
    $vehicle = Vehicle::factory()->create(['fleet_id' => $fleet->id]);
    $device = Device::factory()->create(['fleet_id' => $fleet->id, 'vehicle_id' => $vehicle->id]);

    Alert::query()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'device_id' => $device->id,
        'type' => 'signal_recovered',
        'severity' => 'medium',
        'status' => 'new',
        'title' => 'Alerte nouvelle ancienne',
        'message' => 'Message nouvelle',
        'occurred_at' => now()->subHour(),
    ]);

    Alert::query()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'device_id' => $device->id,
        'type' => 'no_signal',
        'severity' => 'high',
        'status' => 'acknowledged',
        'title' => 'Alerte traitee recente',
        'message' => 'Message traitee',
        'occurred_at' => now(),
        'acknowledged_at' => now(),
    ]);

    $this->actingAs($superadmin)
        ->get(route('alerts.index'))
        ->assertSuccessful()
        ->assertSeeInOrder(['Alerte nouvelle ancienne', 'Alerte traitee recente']);

    $response = $this->actingAs($superadmin)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('alerts.index', ['sort' => 'id', 'direction' => 'desc']))
        ->assertSuccessful();

    expect($response->json('html'))
        ->toContain('Alerte nouvelle ancienne')
        ->toContain('Alerte traitee recente');

    expect(strpos($response->json('html'), 'Alerte nouvelle ancienne'))
        ->toBeLessThan(strpos($response->json('html'), 'Alerte traitee recente'));
});

test('superadmin realtime fallback endpoint returns recent alerts only', function () {
    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create();
    $vehicle = Vehicle::factory()->create(['fleet_id' => $fleet->id]);
    $device = Device::factory()->create(['fleet_id' => $fleet->id, 'vehicle_id' => $vehicle->id]);

    $oldAlert = Alert::query()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'device_id' => $device->id,
        'type' => 'no_signal',
        'severity' => 'high',
        'status' => 'new',
        'title' => 'Ancienne alerte',
        'message' => 'Ancien message',
        'occurred_at' => now()->subMinute(),
    ]);

    $newAlert = Alert::query()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'device_id' => $device->id,
        'type' => 'signal_recovered',
        'severity' => 'medium',
        'status' => 'new',
        'title' => 'Nouvelle alerte',
        'message' => 'Nouveau message',
        'occurred_at' => now(),
    ]);

    $response = $this->actingAs($superadmin)
        ->getJson(route('alerts.recent', ['after' => $oldAlert->id]))
        ->assertSuccessful()
        ->assertJsonPath('latest_id', $newAlert->id);

    expect($response->json('alerts'))
        ->toHaveCount(1)
        ->and($response->json('alerts.0.title'))->toBe('Nouvelle alerte');
});

test('alert messages are localized from the active session language', function () {
    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create();
    $vehicle = Vehicle::factory()->create(['fleet_id' => $fleet->id, 'name' => 'Toyota Locale']);
    $device = Device::factory()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'name' => 'Traceur Locale',
    ]);

    $alert = Alert::query()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'device_id' => $device->id,
        'type' => 'signal_recovered',
        'severity' => 'medium',
        'status' => 'new',
        'title' => 'Signal restored',
        'message' => 'Tracker Traceur Locale for vehicle Toyota Locale is connected again.',
        'metadata' => [
            'translation' => [
                'title_key' => 'alerts.type_signal_recovered',
                'message_key' => 'alerts.message_signal_recovered',
                'replace' => [
                    'tracker' => 'Traceur Locale',
                    'vehicle' => 'Toyota Locale',
                ],
            ],
        ],
        'occurred_at' => now(),
    ]);

    $this->actingAs($superadmin)
        ->withSession(['locale' => 'fr'])
        ->getJson(route('alerts.recent', ['after' => $alert->id - 1]))
        ->assertSuccessful()
        ->assertJsonPath('alerts.0.title', 'Signal rétabli')
        ->assertJsonPath('alerts.0.message', 'Le traceur Traceur Locale du véhicule Toyota Locale est de nouveau connecté.');

    $this->actingAs($superadmin)
        ->withSession(['locale' => 'en'])
        ->getJson(route('alerts.recent', ['after' => $alert->id - 1]))
        ->assertSuccessful()
        ->assertJsonPath('alerts.0.title', 'Signal restored')
        ->assertJsonPath('alerts.0.message', 'Tracker Traceur Locale for vehicle Toyota Locale is connected again.');
});

test('legacy english gps alerts are translated on display', function () {
    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create();
    $vehicle = Vehicle::factory()->create(['fleet_id' => $fleet->id, 'name' => 'TOYOTA HIACE']);
    $device = Device::factory()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'name' => 'FMB920',
    ]);

    $alert = Alert::query()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'device_id' => $device->id,
        'type' => 'no_signal',
        'severity' => 'high',
        'status' => 'new',
        'title' => 'No signal',
        'message' => 'Tracker FMB920 for vehicle TOYOTA HIACE is no longer transmitting signal.',
        'occurred_at' => now(),
    ]);

    $this->actingAs($superadmin)
        ->withSession(['locale' => 'fr'])
        ->getJson(route('alerts.recent', ['after' => $alert->id - 1]))
        ->assertSuccessful()
        ->assertJsonPath('alerts.0.title', 'Aucun signal')
        ->assertJsonPath('alerts.0.message', 'Le traceur FMB920 du véhicule TOYOTA HIACE ne transmet plus de signal.');
});

test('alert demo command creates an alert and dispatches broadcast event', function () {
    Event::fake();

    $fleet = Fleet::factory()->create();
    $vehicle = Vehicle::factory()->create(['fleet_id' => $fleet->id, 'name' => 'Toyota Live']);
    Device::factory()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'name' => 'Traceur Live',
        'imei' => '356307042441099',
    ]);

    $exitCode = Artisan::call('alerts:demo', ['vehicle_id' => $vehicle->id]);

    expect($exitCode)->toBe(0);

    $this->assertDatabaseHas('alerts', [
        'vehicle_id' => $vehicle->id,
        'type' => 'sos',
        'severity' => 'critical',
    ]);

    Event::assertDispatched(\App\Events\AlertCreated::class);
});

test('superadmin can view server logs page and fetch whitelisted log content', function () {
    $superadmin = User::factory()->superadmin()->create();
    $path = storage_path('logs/gps-tcpdump.log');
    $previous = file_exists($path) ? file_get_contents($path) : null;

    file_put_contents($path, implode(PHP_EOL, [
        '[TCP] connection from 153.67.139.222:24291',
        '[TCP] IMEI received: 353691840797368',
        '[TCP] IMEI accepted: 353691840797368',
        '[TCP] 353691840797368 codec8_extended records=1 ACK=1',
    ]));

    try {
        $this->actingAs($superadmin)
            ->get(route('server-logs.index'))
            ->assertSuccessful()
            ->assertSee(__('server_logs.title'))
            ->assertSee(route('server-logs.content'), false);

        $response = $this->actingAs($superadmin)
            ->getJson(route('server-logs.content', ['log' => 'gps-tcpdump', 'lines' => 2]))
            ->assertSuccessful()
            ->assertJsonPath('exists', true);

        expect($response->json('content'))
            ->toContain('[TCP] IMEI accepted: 353691840797368')
            ->toContain('[TCP] 353691840797368 codec8_extended records=1 ACK=1');

        $response = $this->actingAs($superadmin)
            ->getJson(route('server-logs.content', ['log' => '../../.env']))
            ->assertSuccessful();

        expect($response->json('content'))->not->toContain('APP_KEY=');
    } finally {
        $previous === null ? @unlink($path) : file_put_contents($path, $previous);
    }
});

test('superadmin can view server monitoring metrics', function () {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)
        ->get(route('server-monitoring.index'))
        ->assertSuccessful()
        ->assertSee(__('server_monitoring.title'))
        ->assertSee(route('server-monitoring.metrics'), false);

    $this->actingAs($superadmin)
        ->getJson(route('server-monitoring.metrics'))
        ->assertSuccessful()
        ->assertJsonStructure([
            'generated_at',
            'cpu' => ['usage', 'cores'],
            'memory' => ['total', 'used', 'available', 'percent', 'swap_total', 'swap_used', 'swap_percent'],
            'disk' => ['total', 'used', 'free', 'percent'],
            'load' => ['one', 'five', 'fifteen'],
            'network' => ['interfaces', 'total_rx_rate', 'total_tx_rate'],
            'system' => ['hostname', 'os', 'php', 'laravel', 'environment', 'uptime'],
        ]);
});
