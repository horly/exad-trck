<?php

use App\Events\AlertCreated;
use App\Models\Alert;
use App\Models\ApplicationSetting;
use App\Models\Device;
use App\Models\Driver;
use App\Models\DriverIdentifier;
use App\Models\Fleet;
use App\Models\Position;
use App\Models\Subscription;
use App\Models\TrackerEvent;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleSubscriptionFeature;
use App\Models\VehicleSubscriptionPlan;
use App\Services\ReverseGeocodingService;
use Database\Seeders\AlertRuleSeeder;
use Database\Seeders\VehicleSubscriptionFeatureSeeder;
use Database\Seeders\VehicleSubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
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
        ->assertSee('Positions période')
        ->assertSee('Carte mondiale des traceurs')
        ->assertSee('Évolution des positions')
        ->assertSee('vendor/d3/d3.min.js', false)
        ->assertSee('vendor/topojson/topojson.min.js', false)
        ->assertSee('vendor/datamaps/datamaps.world.min.js', false);
});

test('superadmin can add subscription plans from a modal with existing features', function () {
    $this->seed(VehicleSubscriptionFeatureSeeder::class);
    $this->seed(VehicleSubscriptionPlanSeeder::class);

    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)
        ->withSession(['locale' => 'fr'])
        ->get(route('subscriptions.index'))
        ->assertSuccessful()
        ->assertSee('Nouvel abonnement')
        ->assertSee('data-bs-target="#subscriptionPlanModal"', false)
        ->assertSee('subscriptionPlanModal', false)
        ->assertSee('Matrice');

    $features = VehicleSubscriptionFeature::query()->pluck('code')->take(3)->all();

    $this->actingAs($superadmin)
        ->withSession(['locale' => 'fr'])
        ->patch(route('subscriptions.update'), [
            'new_plan' => [
                'name' => 'Entreprise',
                'description' => 'Plan personnalise pour flotte avancee.',
                'color' => '#1f4ed8',
                'features' => $features,
            ],
        ])
        ->assertRedirect(route('subscriptions.index'))
        ->assertSessionHas('status');

    $plan = VehicleSubscriptionPlan::query()->where('code', 'entreprise')->first();

    expect($plan)->not->toBeNull()
        ->and($plan->features)->toBe($features);
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

test('admin users are redirected from home to their dashboard', function () {
    $subscription = Subscription::factory()->create();
    $admin = User::factory()->admin($subscription)->create();

    $this->actingAs($admin)
        ->get('/')
        ->assertRedirect(route('dashboard'));
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
            'gsm_signal' => 4,
            'battery_level' => 92,
            'external_voltage' => 12.4,
            'battery_voltage' => 4.1,
            'address' => 'Kinsuka Pecheur, Ngaliema, Kinshasa',
            'events' => [
                ['type' => 'door_open'],
                ['type' => 'harsh_braking'],
            ],
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

    $this->assertDatabaseMissing('tracker_events', [
        'device_id' => $device->id,
        'type' => 'signal_restored',
    ]);

    $this->assertDatabaseHas('tracker_events', [
        'device_id' => $device->id,
        'type' => 'movement_started',
    ]);

    $this->assertDatabaseHas('tracker_events', [
        'device_id' => $device->id,
        'type' => 'door_open',
    ]);

    $this->assertDatabaseHas('tracker_events', [
        'device_id' => $device->id,
        'type' => 'harsh_braking',
    ]);

    $invalidAngleCode = Artisan::call('gps:ingest-position', [
        '--payload' => json_encode([
            'imei' => '356307042441013',
            'lat' => -4.3255,
            'lng' => 15.3125,
            'speed' => 65535,
            'angle' => 65535,
            'altitude' => 65535,
            'satellites' => 255,
            'gsm_signal' => 255,
            'battery_level' => 255,
        ]),
    ]);

    expect($invalidAngleCode)->toBe(0)
        ->and($device->refresh()->last_angle)->toBe(90);

    $this->assertDatabaseHas('positions', [
        'device_id' => $device->id,
        'latitude' => -4.3255,
        'longitude' => 15.3125,
        'speed' => 0,
        'angle' => 90,
        'altitude' => null,
        'satellites' => null,
    ]);

    $northAngleCode = Artisan::call('gps:ingest-position', [
        '--payload' => json_encode([
            'imei' => '356307042441013',
            'lat' => -4.3257,
            'lng' => 15.3127,
            'speed' => 20,
            'angle' => 360,
        ]),
    ]);

    expect($northAngleCode)->toBe(0)
        ->and($device->refresh()->last_angle)->toBe(0);

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

    $this->assertDatabaseMissing('tracker_events', [
        'device_id' => $device->id,
        'type' => 'signal_lost',
    ]);
});

test('gps ingestion normalizes fmb140 can mileage and signed engine temperature', function () {
    $device = Device::factory()->create([
        'imei' => '353201357467643',
        'status' => 'inactive',
    ]);

    $exitCode = Artisan::call('gps:ingest-position', [
        '--payload' => json_encode([
            'imei' => $device->imei,
            'lat' => -4.343545,
            'lng' => 15.2864733,
            'speed' => 6,
            'io' => [
                16 => 224264,
                66 => 14220,
                85 => 1218,
                115 => 65176,
                199 => 616,
            ],
            'can' => [
                'total_mileage_km' => 0.616,
            ],
        ], JSON_THROW_ON_ERROR),
    ]);

    $device->refresh();

    expect($exitCode)->toBe(0)
        ->and((float) $device->last_odometer_km)->toBe(224.26)
        ->and((float) $device->last_can_total_mileage_km)->toBe(224.26)
        ->and((float) $device->last_obd_engine_temperature_c)->toBe(-36.0)
        ->and($device->last_obd_rpm)->toBe(1218)
        ->and($device->last_obd_module_voltage)->toBeNull();
});

test('historical gps replay is stored without replacing live tracker state', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-20 10:30:00', config('app.timezone')));
    $this->beforeApplicationDestroyed(fn () => Carbon::setTestNow());

    $device = Device::factory()->create([
        'imei' => '353201355315547',
        'status' => 'online',
        'last_seen_at' => now()->subMinute(),
        'last_position_at' => now()->subMinute(),
        'last_latitude' => -4.349633,
        'last_longitude' => 15.297787,
        'last_speed' => 0,
        'last_angle' => 13,
        'last_movement' => false,
        'last_ignition' => false,
    ]);

    $exitCode = Artisan::call('gps:ingest-position', [
        '--payload' => json_encode([
            'imei' => $device->imei,
            'lat' => -4.338925,
            'lng' => 15.255545,
            'speed' => 28,
            'angle' => 95,
            'movement' => true,
            'ignition' => true,
            'gps_time' => now()->subHours(14)->toIso8601String(),
        ]),
    ]);

    $outOfOrderCode = Artisan::call('gps:ingest-position', [
        '--payload' => json_encode([
            'imei' => $device->imei,
            'lat' => -4.340000,
            'lng' => 15.260000,
            'speed' => 35,
            'angle' => 120,
            'movement' => true,
            'ignition' => true,
            'gps_time' => now()->subMinutes(2)->toIso8601String(),
        ]),
    ]);

    $device->refresh();

    expect($exitCode)->toBe(0)
        ->and($outOfOrderCode)->toBe(0)
        ->and($device->status)->toBe('online')
        ->and($device->last_seen_at->equalTo(now()))->toBeTrue()
        ->and($device->last_position_at->equalTo(now()->subMinute()))->toBeTrue()
        ->and((float) $device->last_latitude)->toBe(-4.349633)
        ->and((float) $device->last_longitude)->toBe(15.297787)
        ->and($device->last_speed)->toBe(0)
        ->and($device->last_angle)->toBe(13)
        ->and($device->last_movement)->toBeFalse()
        ->and($device->last_ignition)->toBeFalse()
        ->and(Artisan::output())->toContain('"updates_live_state":false');

    $this->assertDatabaseHas('positions', [
        'device_id' => $device->id,
        'latitude' => -4.338925,
        'longitude' => 15.255545,
        'speed' => 28,
        'movement' => true,
    ]);

    $this->assertDatabaseMissing('tracker_events', [
        'device_id' => $device->id,
        'type' => 'movement_started',
    ]);
});

test('delayed gps replay advances a stale known position without triggering realtime events', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-08 14:00:00', config('app.timezone')));
    $this->beforeApplicationDestroyed(fn () => Carbon::setTestNow());

    $device = Device::factory()->create([
        'imei' => '353201355315547',
        'status' => 'online',
        'last_seen_at' => now()->subMinute(),
        'last_position_at' => Carbon::parse('2026-07-31 23:45:44', config('app.timezone')),
        'last_latitude' => -4.349815,
        'last_longitude' => 15.2977483,
        'last_speed' => 0,
        'last_angle' => 0,
        'last_movement' => false,
        'last_ignition' => false,
    ]);
    $gpsTime = now()->subHours(3);

    $exitCode = Artisan::call('gps:ingest-position', [
        '--payload' => json_encode([
            'imei' => $device->imei,
            'lat' => -4.0580516,
            'lng' => 15.5559816,
            'speed' => 19,
            'angle' => 72,
            'movement' => true,
            'ignition' => true,
            'gps_time' => $gpsTime->toIso8601String(),
        ]),
    ]);

    $device->refresh();

    expect($exitCode)->toBe(0)
        ->and($device->last_position_at->equalTo($gpsTime))->toBeTrue()
        ->and((float) $device->last_latitude)->toBe(-4.0580516)
        ->and((float) $device->last_longitude)->toBe(15.5559816)
        ->and($device->last_speed)->toBe(19)
        ->and($device->last_movement)->toBeTrue()
        ->and($device->last_ignition)->toBeTrue()
        ->and(Artisan::output())->toContain('"updates_live_state":false');

    $this->assertDatabaseMissing('tracker_events', [
        'device_id' => $device->id,
        'type' => 'movement_started',
    ]);
});

test('gps ingestion never copies a previous address onto new coordinates', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-08 09:30:00', config('app.timezone')));
    $this->beforeApplicationDestroyed(fn () => Carbon::setTestNow());

    $device = Device::factory()->create([
        'imei' => '353201355315547',
        'status' => 'online',
        'last_position_at' => now()->subMinute(),
        'last_latitude' => -4.349815,
        'last_longitude' => 15.2977483,
        'last_address' => '196, Avenue de L ECOLE, Ngaliema, Kinshasa',
    ]);

    $exitCode = Artisan::call('gps:ingest-position', [
        '--payload' => json_encode([
            'imei' => $device->imei,
            'lat' => -4.350500,
            'lng' => 15.311000,
            'speed' => 0,
            'movement' => false,
            'ignition' => false,
            'gps_time' => now()->toIso8601String(),
        ]),
    ]);

    $position = Position::query()->where('device_id', $device->id)->latest('id')->firstOrFail();

    expect($exitCode)->toBe(0)
        ->and($position->address)->toBeNull()
        ->and($device->refresh()->last_address)->toBeNull();
});

test('superadmin can browse tracker events with ajax datatable', function () {
    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create(['name' => 'EXAD CARS']);
    $vehicle = Vehicle::factory()->create([
        'fleet_id' => $fleet->id,
        'name' => 'Palisade',
        'registration_number' => '0943BL01',
    ]);
    $device = Device::factory()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'name' => 'Tracker Palisade',
        'imei' => '353201355315547',
    ]);
    $otherVehicle = Vehicle::factory()->create([
        'fleet_id' => $fleet->id,
        'name' => 'Toyota Land Cruiser',
        'registration_number' => '2058AG10',
    ]);
    $otherDevice = Device::factory()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $otherVehicle->id,
        'name' => 'Tracker Land Cruiser',
        'imei' => '865456047193582',
    ]);

    TrackerEvent::query()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'device_id' => $device->id,
        'type' => 'door_open',
        'title' => __('trackers.event_door_open_title'),
        'message' => __('trackers.event_door_open_message', ['vehicle' => $vehicle->name]),
        'started_at' => now(),
        'metadata' => [
            'translation' => [
                'title_key' => 'trackers.event_door_open_title',
                'message_key' => 'trackers.event_door_open_message',
                'replace' => ['vehicle' => $vehicle->name],
            ],
        ],
    ]);
    TrackerEvent::query()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'device_id' => $device->id,
        'type' => 'signal_restored',
        'title' => 'Signal restored technical',
        'message' => 'This technical device alert must not appear as a vehicle event.',
        'started_at' => now()->subMinute(),
    ]);
    TrackerEvent::query()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $otherVehicle->id,
        'device_id' => $otherDevice->id,
        'type' => 'ignition_on',
        'title' => __('trackers.event_ignition_on_title'),
        'message' => __('trackers.event_ignition_on_message', ['vehicle' => $otherVehicle->name]),
        'started_at' => now(),
    ]);

    $this->actingAs($superadmin)
        ->get(route('events.index'))
        ->assertRedirect(route('trackers.index'))
        ->assertSessionHas('status', __('events.select_tracker'));

    $this->actingAs($superadmin)
        ->get(route('events.index', ['device' => $device->id]))
        ->assertSuccessful()
        ->assertSee(__('events.title'))
        ->assertSee('Palisade')
        ->assertSee(__('trackers.event_door_open_title'))
        ->assertDontSee('Signal restored technical')
        ->assertDontSee('Toyota Land Cruiser');

    $this->actingAs($superadmin)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('events.index', ['device' => $device->id, 'search' => '0943BL01', 'sort' => 'vehicle', 'direction' => 'asc']))
        ->assertSuccessful()
        ->assertJsonStructure(['html'])
        ->assertSee('0943BL01');
});

test('superadmin can open tracker details with fleet and latest events', function () {
    config(['services.google_maps.api_key' => '', 'services.mapbox.public_token' => '']);

    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create(['name' => 'EXAD CARS', 'code' => 'EX-CRS']);
    $staleDeviceFleet = Fleet::factory()->create(['name' => 'Ancienne flotte traceur']);
    $vehicle = Vehicle::factory()->create([
        'fleet_id' => $fleet->id,
        'name' => 'Suzuki Swift Horly',
        'registration_number' => '6823BV01',
    ]);
    $device = Device::factory()->create([
        'fleet_id' => $staleDeviceFleet->id,
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
        'last_io' => [
            90 => 0,
            132 => 33554435,
            517 => 317,
        ],
        'last_driver_identifier_uid' => '142F748E0200006C',
        'operator_name' => 'Vodacom',
        'last_address' => 'Avenue des Ecuries, Joli Parc, Ngaliema, Kinshasa, Congo-Kinshasa',
        'last_position_at' => now(),
    ]);
    $driver = Driver::query()->create([
        'fleet_id' => $fleet->id,
        'first_name' => 'David',
        'last_name' => 'Lukusa',
        'employee_id' => 'DRV-001',
        'phone' => '+243810000000',
        'status' => 'active',
    ]);
    $driver->vehicles()->attach($vehicle);
    DriverIdentifier::query()->create([
        'driver_id' => $driver->id,
        'type' => 'ibutton',
        'uid' => '6C0000028E742F14',
        'active' => true,
    ]);
    Position::factory()->forDevice($device)->create([
        'latitude' => -4.33509,
        'longitude' => 15.22408,
        'altitude' => 296,
        'address' => 'Avenue des Ecuries, Joli Parc, Ngaliema, Kinshasa, Congo-Kinshasa',
        'server_time' => now(),
        'gps_time' => now(),
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
        ->toContain('tracker-details-overview')
        ->toContain('tracker-details-card--identity')
        ->toContain('tracker-details-technical')
        ->toContain('Synthèse opérationnelle')
        ->toContain('Flotte : EXAD CARS')
        ->toContain('Suzuki Swift Horly')
        ->toContain('Alimentation')
        ->toContain('Conducteur')
        ->toContain('Nom : David Lukusa')
        ->toContain('Matricule : DRV-001')
        ->toContain('Identifiant conducteur : 6C0000028E742F14')
        ->toContain('Avenue des Ecuries')
        ->toContain('altitude : 296 mètres')
        ->toContain('Parking')
        ->toContain('Tension externe : 12.6 V')
        ->toContain('Vodacom')
        ->toContain('États CAN du véhicule')
        ->toContain('Porte arrière droite')
        ->toContain('État de l’allumage')
        ->toContain('Allumé')
        ->toContain('Toutes fermées')
        ->toContain('Début de déplacement')
        ->not->toContain('Groupe');
});

test('tracker details show the latest stopped or parked address', function () {
    config(['services.google_maps.api_key' => '', 'services.mapbox.public_token' => '']);

    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create(['name' => 'EXAD CARS']);
    $vehicle = Vehicle::factory()->create([
        'fleet_id' => $fleet->id,
        'name' => 'Suzuki Swift Horly',
    ]);
    $device = Device::factory()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'last_address' => 'Adresse de position courante',
        'last_movement' => true,
        'last_ignition' => true,
        'last_position_at' => now()->setTime(8, 30),
    ]);

    Position::factory()->forDevice($device)->create([
        'server_time' => now()->setTime(8, 10),
        'gps_time' => now()->setTime(8, 10),
        'latitude' => -4.328,
        'longitude' => 15.312,
        'address' => '128 Rue De Bolobo, Kinshasa, République démocratique du Congo',
        'movement' => false,
        'speed' => 0,
        'ignition' => false,
    ]);
    Position::factory()->forDevice($device)->create([
        'server_time' => now()->setTime(8, 30),
        'gps_time' => now()->setTime(8, 30),
        'latitude' => -4.331,
        'longitude' => 15.315,
        'address' => 'Position courante en mouvement',
        'movement' => true,
        'speed' => 24,
        'ignition' => true,
    ]);

    $response = $this->actingAs($superadmin)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('trackers.details', $device))
        ->assertSuccessful();

    expect($response->json('html'))
        ->toContain('128 Rue De Bolobo')
        ->not->toContain('Position courante en mouvement');

    expect($device->refresh()->last_address)->toBe('Adresse de position courante');
});

test('tracker details use the current parking start position for location data', function () {
    config(['services.google_maps.api_key' => '', 'services.mapbox.public_token' => '']);
    Carbon::setTestNow(Carbon::parse('2026-06-19 10:00:00', config('app.timezone')));
    $this->beforeApplicationDestroyed(fn () => Carbon::setTestNow());

    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create(['name' => 'EXAD CARS']);
    $vehicle = Vehicle::factory()->create([
        'fleet_id' => $fleet->id,
        'name' => 'Palisade',
    ]);
    $device = Device::factory()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'last_address' => 'Dernier ping parking',
        'last_seen_at' => now()->subMinute(),
        'last_position_at' => now()->subMinute(),
        'last_ignition' => false,
        'last_movement' => false,
        'last_angle' => 90,
    ]);

    Position::factory()->forDevice($device)->create([
        'server_time' => now()->subMinutes(12),
        'gps_time' => now()->subMinutes(12),
        'latitude' => -4.349,
        'longitude' => 15.309,
        'address' => 'Position avant parking',
        'movement' => true,
        'speed' => 24,
        'ignition' => true,
        'angle' => 45,
    ]);
    Position::factory()->forDevice($device)->create([
        'server_time' => now()->subMinutes(8),
        'gps_time' => now()->subMinutes(8),
        'latitude' => -4.350066,
        'longitude' => 15.3106833,
        'altitude' => 297,
        'address' => '32 Rue De Sandoa, Kasa-Vubu, Kinshasa',
        'movement' => false,
        'speed' => 0,
        'ignition' => false,
        'angle' => 180,
    ]);
    Position::factory()->forDevice($device)->create([
        'server_time' => now()->subMinute(),
        'gps_time' => now()->subMinute(),
        'latitude' => -4.350500,
        'longitude' => 15.311000,
        'address' => 'Dernier ping parking',
        'movement' => false,
        'speed' => 0,
        'ignition' => false,
        'angle' => 90,
    ]);

    $response = $this->actingAs($superadmin)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('trackers.details', $device))
        ->assertSuccessful();

    expect($response->json('html'))
        ->toContain('32 Rue De Sandoa')
        ->toContain('Latitude : -4.3500660, Longitude : 15.3106833')
        ->toContain('altitude : 297')
        ->toContain('Direction : S')
        ->toContain('Parking')
        ->toContain('8 minutes')
        ->not->toContain('Dernier ping parking');
});

test('tracker details geocode the exact parking coordinates instead of a copied listener address', function () {
    config([
        'services.maps.provider' => 'google',
        'services.google_maps.api_key' => 'AIza-test-key',
        'services.mapbox.public_token' => '',
    ]);
    Carbon::setTestNow(Carbon::parse('2026-08-08 09:30:00', config('app.timezone')));
    $this->beforeApplicationDestroyed(fn () => Carbon::setTestNow());

    Http::fake(fn () => Http::response([
        'status' => 'OK',
        'results' => [[
            'types' => ['street_address'],
            'formatted_address' => 'Avenue Kasa-Vubu, Bayaka, Kinshasa, République démocratique du Congo',
            'geometry' => ['location_type' => 'ROOFTOP'],
            'address_components' => [
                ['long_name' => 'Avenue Kasa-Vubu', 'types' => ['route']],
                ['long_name' => 'Bayaka', 'types' => ['neighborhood']],
                ['long_name' => 'Kinshasa', 'types' => ['locality']],
                ['long_name' => 'République démocratique du Congo', 'types' => ['country']],
            ],
        ]],
    ]));

    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create();
    $vehicle = Vehicle::factory()->for($fleet)->create(['name' => 'Suzuki Horly YANGO']);
    $gpsTime = now()->subWeek();
    $staleAddress = '196, Avenue de L ECOLE, Ngaliema, Kinshasa';
    $device = Device::factory()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'last_position_at' => $gpsTime,
        'last_latitude' => -4.349815,
        'last_longitude' => 15.2977483,
        'last_address' => $staleAddress,
        'last_ignition' => true,
        'last_movement' => true,
    ]);
    $parkingPosition = Position::factory()->forDevice($device)->create([
        'gps_time' => $gpsTime,
        'server_time' => now(),
        'latitude' => -4.349815,
        'longitude' => 15.2977483,
        'address' => $staleAddress,
        'speed' => 0,
        'movement' => true,
        'ignition' => true,
        'raw_data' => ['source' => 'gps-listener-server-local', 'payload' => []],
    ]);
    Position::factory()->forDevice($device)->create([
        'gps_time' => $gpsTime->copy()->addHour(),
        'server_time' => now()->addMinute(),
        'latitude' => -4.200000,
        'longitude' => 15.500000,
        'address' => $staleAddress,
        'speed' => 0,
        'movement' => false,
        'ignition' => false,
        'raw_data' => ['source' => 'gps-listener-server-local', 'payload' => []],
    ]);

    $response = $this->actingAs($superadmin)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('trackers.details', $device))
        ->assertSuccessful();

    expect($response->json('html'))
        ->toContain('Avenue Kasa-Vubu')
        ->toContain('Latitude : -4.3498150, Longitude : 15.2977483')
        ->not->toContain($staleAddress)
        ->not->toContain('-4.2000000');

    expect($parkingPosition->refresh()->address)->toContain('Avenue Kasa-Vubu');
});

test('superadmin can display tracker trips as html and geojson', function () {
    config(['services.google_maps.api_key' => '', 'services.mapbox.public_token' => '']);

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
        ->assertJsonPath('geojson.features.0.properties.id', 'trip-1')
        ->assertJsonPath('geojson.features.0.properties.point_count', 3)
        ->assertJsonPath('geojson.features.0.properties.max_speed_kmh', 28)
        ->assertJsonPath('geojson.features.0.properties.color', '#2563eb')
        ->assertJsonPath('summary.count', 1);

    expect($response->json('html'))
        ->toContain('Aujourd’hui')
        ->toContain('Kinsuka Pecheur')
        ->toContain('Centre cité')
        ->toContain('Total : 1 trajets')
        ->toContain('data-trip-replay')
        ->toContain('data-trip-select="1"')
        ->toContain('data-trip-color="1"')
        ->toContain('x300')
        ->and($response->json('geojson.features.0.geometry.type'))->toBe('LineString');
});

test('tracker trips are built from stopped and parking boundaries with the position timezone', function () {
    config([
        'services.maps.provider' => 'google',
        'services.google_maps.api_key' => 'AIza-test-key',
        'services.google_maps.roads_enabled' => false,
        'services.mapbox.public_token' => '',
    ]);

    Http::fake([
        'maps.googleapis.com/maps/api/timezone/json*' => Http::response([
            'status' => 'OK',
            'timeZoneId' => 'Africa/Kinshasa',
        ]),
        'maps.googleapis.com/maps/api/geocode/json*' => Http::response(['status' => 'ZERO_RESULTS']),
    ]);

    $superadmin = User::factory()->superadmin()->create();
    $device = Device::factory()->create();

    Position::factory()->forDevice($device)->create([
        'server_time' => today()->setTime(7, 4),
        'gps_time' => today()->setTime(7, 4),
        'latitude' => -4.33507,
        'longitude' => 15.25042,
        'address' => 'Avenue De Kapanga, Kinshasa, République démocratique du Congo',
        'movement' => false,
        'speed' => 0,
        'ignition' => false,
    ]);
    Position::factory()->forDevice($device)->create([
        'server_time' => today()->setTime(7, 12),
        'gps_time' => today()->setTime(7, 12),
        'latitude' => -4.33400,
        'longitude' => 15.25600,
        'address' => 'Point en mouvement',
        'movement' => true,
        'speed' => 32,
        'ignition' => true,
    ]);
    Position::factory()->forDevice($device)->create([
        'server_time' => today()->setTime(7, 19),
        'gps_time' => today()->setTime(7, 19),
        'latitude' => -4.32800,
        'longitude' => 15.31200,
        'address' => '128 Rue De Bolobo, Kinshasa, République démocratique du Congo',
        'movement' => false,
        'speed' => 0,
        'ignition' => true,
    ]);

    $response = $this->actingAs($superadmin)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('trackers.trips', ['device' => $device, 'period' => 'today']))
        ->assertSuccessful()
        ->assertJsonPath('summary.count', 1);

    expect($response->json('html'))
        ->toContain('08:04')
        ->toContain('08:19')
        ->toContain('Avenue De Kapanga')
        ->toContain('128 Rue De Bolobo')
        ->and($response->json('geojson.features.0.geometry.coordinates'))
        ->toHaveCount(3);
});

test('tracker trips keep short stops inside one continuous trip', function () {
    config(['services.google_maps.api_key' => '', 'services.mapbox.public_token' => '']);

    $superadmin = User::factory()->superadmin()->create();
    $device = Device::factory()->create();

    $points = [
        ['time' => now()->setTime(8, 0), 'lat' => -4.33000, 'lng' => 15.22000, 'address' => 'Depot Limete', 'movement' => false, 'speed' => 0],
        ['time' => now()->setTime(8, 5), 'lat' => -4.33100, 'lng' => 15.22500, 'address' => 'Boulevard Lumumba', 'movement' => true, 'speed' => 28],
        ['time' => now()->setTime(8, 12), 'lat' => -4.33300, 'lng' => 15.23000, 'address' => 'Arret court', 'movement' => false, 'speed' => 0],
        ['time' => now()->setTime(8, 14), 'lat' => -4.33400, 'lng' => 15.23500, 'address' => 'Reprise trajet', 'movement' => true, 'speed' => 24],
        ['time' => now()->setTime(8, 24), 'lat' => -4.34000, 'lng' => 15.25000, 'address' => 'Arrivee Gombe', 'movement' => false, 'speed' => 0],
        ['time' => now()->setTime(8, 31), 'lat' => -4.34000, 'lng' => 15.25000, 'address' => 'Arrivee Gombe', 'movement' => false, 'speed' => 0],
    ];

    foreach ($points as $point) {
        Position::factory()->forDevice($device)->create([
            'server_time' => $point['time'],
            'gps_time' => $point['time'],
            'latitude' => $point['lat'],
            'longitude' => $point['lng'],
            'address' => $point['address'],
            'movement' => $point['movement'],
            'speed' => $point['speed'],
        ]);
    }

    $response = $this->actingAs($superadmin)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('trackers.trips', ['device' => $device, 'period' => 'today']))
        ->assertSuccessful()
        ->assertJsonPath('summary.count', 1);

    expect($response->json('html'))
        ->toContain('Total : 1 trajets')
        ->toContain('Depot Limete')
        ->toContain('Arrivee Gombe')
        ->not->toContain('Trajet 2')
        ->and($response->json('geojson.features.0.geometry.coordinates'))
        ->toHaveCount(5);
});

test('tracker trips split sustained zero speed stops even when movement remains true', function () {
    config(['services.google_maps.api_key' => '', 'services.mapbox.public_token' => '']);

    $superadmin = User::factory()->superadmin()->create();
    $device = Device::factory()->create();

    $points = [
        ['time' => now()->setTime(7, 27), 'lat' => -4.33000, 'lng' => 15.22000, 'address' => 'Depart Kasa-Vubu', 'speed' => 24],
        ['time' => now()->setTime(7, 35), 'lat' => -4.33200, 'lng' => 15.22500, 'address' => 'Parcours Kasa-Vubu', 'speed' => 26],
        ['time' => now()->setTime(7, 46), 'lat' => -4.33500, 'lng' => 15.23000, 'address' => 'Arrivee Avenue Saio', 'speed' => 18],
        ['time' => now()->setTime(7, 47), 'lat' => -4.33500, 'lng' => 15.23000, 'address' => 'Arret Avenue Saio', 'speed' => 0],
        ['time' => now()->setTime(7, 49), 'lat' => -4.33500, 'lng' => 15.23000, 'address' => 'Arret Avenue Saio', 'speed' => 0],
        ['time' => now()->setTime(7, 50), 'lat' => -4.33500, 'lng' => 15.23000, 'address' => 'Arret Avenue Saio', 'speed' => 0],
        ['time' => now()->setTime(7, 53), 'lat' => -4.33600, 'lng' => 15.23500, 'address' => 'Reprise Avenue Saio', 'speed' => 20],
        ['time' => now()->setTime(8, 1), 'lat' => -4.34000, 'lng' => 15.24500, 'address' => 'Arrivee Rue Bosobolo', 'speed' => 22],
        ['time' => now()->setTime(8, 2), 'lat' => -4.34000, 'lng' => 15.24500, 'address' => 'Arret Rue Bosobolo', 'speed' => 0],
        ['time' => now()->setTime(8, 6), 'lat' => -4.34000, 'lng' => 15.24500, 'address' => 'Arret Rue Bosobolo', 'speed' => 0],
    ];

    foreach ($points as $index => $point) {
        Position::factory()->forDevice($device)->create([
            'server_time' => now()->setTime(12, 0)->addSeconds($index),
            'gps_time' => $point['time'],
            'latitude' => $point['lat'],
            'longitude' => $point['lng'],
            'address' => $point['address'],
            'movement' => true,
            'ignition' => true,
            'speed' => $point['speed'],
        ]);
    }

    $response = $this->actingAs($superadmin)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('trackers.trips', ['device' => $device, 'period' => 'today']))
        ->assertSuccessful()
        ->assertJsonPath('summary.count', 2)
        ->assertJsonPath('geojson.features.0.properties.start_time', '08:27')
        ->assertJsonPath('geojson.features.0.properties.end_time', '08:47')
        ->assertJsonPath('geojson.features.1.properties.start_time', '08:50')
        ->assertJsonPath('geojson.features.1.properties.end_time', '09:02');

    expect($response->json('html'))
        ->toContain('Total : 2 trajets')
        ->toContain('Trajet 1')
        ->toContain('Trajet 2');
});

test('tracker trips ignore parking jitter without delaying the next departure', function () {
    config(['services.google_maps.api_key' => '', 'services.mapbox.public_token' => '']);

    $superadmin = User::factory()->superadmin()->create();
    $device = Device::factory()->create();
    $points = [
        ['time' => now()->setTime(9, 55), 'lat' => -4.33000, 'lng' => 15.22000, 'speed' => 0],
        ['time' => now()->setTime(10, 0), 'lat' => -4.33000, 'lng' => 15.22000, 'speed' => 0],
        ['time' => now()->setTime(10, 0, 10), 'lat' => -4.33005, 'lng' => 15.22005, 'speed' => 3],
        ['time' => now()->setTime(10, 0, 20), 'lat' => -4.33005, 'lng' => 15.22005, 'speed' => 0],
        ['time' => now()->setTime(10, 5, 20), 'lat' => -4.33005, 'lng' => 15.22005, 'speed' => 0],
        ['time' => now()->setTime(10, 5, 30), 'lat' => -4.33100, 'lng' => 15.22500, 'speed' => 24],
        ['time' => now()->setTime(10, 15), 'lat' => -4.34000, 'lng' => 15.24500, 'speed' => 28],
        ['time' => now()->setTime(10, 16), 'lat' => -4.34000, 'lng' => 15.24500, 'speed' => 0],
        ['time' => now()->setTime(10, 21), 'lat' => -4.34000, 'lng' => 15.24500, 'speed' => 0],
    ];

    foreach ($points as $point) {
        Position::factory()->forDevice($device)->create([
            'server_time' => $point['time'],
            'gps_time' => $point['time'],
            'latitude' => $point['lat'],
            'longitude' => $point['lng'],
            'address' => 'Position test',
            'movement' => $point['speed'] > 0,
            'ignition' => true,
            'speed' => $point['speed'],
        ]);
    }

    $this->actingAs($superadmin)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('trackers.trips', ['device' => $device, 'period' => 'today']))
        ->assertSuccessful()
        ->assertJsonPath('summary.count', 1)
        ->assertJsonPath('geojson.features.0.properties.start_time', '11:00')
        ->assertJsonPath('geojson.features.0.properties.end_time', '11:16');
});

test('tracker trips resolve missing addresses with mapbox reverse geocoding', function () {
    config([
        'services.maps.provider' => 'mapbox',
        'services.google_maps.api_key' => '',
        'services.mapbox.public_token' => 'pk.test',
    ]);

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

test('reverse geocoding replaces a generic locality with the street returned by the fallback provider', function () {
    app()->setLocale('fr');

    config([
        'services.maps.provider' => 'google',
        'services.google_maps.api_key' => 'AIza-test-key',
        'services.mapbox.public_token' => 'pk.test',
    ]);

    $latitude = -4.38888;
    $longitude = 15.29999;

    Cache::put(
        'reverse-geocode:v2:fr:-4.38888:15.29999',
        'Ngaliema, Kinshasa, République démocratique du Congo',
        now()->addDays(30),
    );
    Cache::forget('reverse-geocode:v3:fr:-4.38888:15.29999');

    Http::preventStrayRequests();
    Http::fake(function ($request) {
        if (str_contains($request->url(), 'maps.googleapis.com/maps/api/geocode/json')) {
            return Http::response([
                'status' => 'OK',
                'results' => [[
                    'types' => ['locality'],
                    'formatted_address' => 'Ngaliema, Kinshasa, République démocratique du Congo',
                    'geometry' => ['location_type' => 'APPROXIMATE'],
                    'address_components' => [
                        ['long_name' => 'Ngaliema', 'types' => ['sublocality_level_1']],
                        ['long_name' => 'Kinshasa', 'types' => ['locality']],
                        ['long_name' => 'République démocratique du Congo', 'types' => ['country']],
                    ],
                ]],
            ]);
        }

        if (str_contains($request->url(), 'api.mapbox.com/search/geocode/v6/reverse')) {
            return Http::response([
                'features' => [
                    [
                        'properties' => [
                            'feature_type' => 'neighborhood',
                            'full_address' => 'Ngaliema, Kinshasa, République démocratique du Congo',
                        ],
                    ],
                    [
                        'properties' => [
                            'feature_type' => 'street',
                            'name' => 'Avenue Nguma',
                            'place_formatted' => 'Ngaliema, Kinshasa, République démocratique du Congo',
                        ],
                    ],
                ],
            ]);
        }

        return Http::response([], 404);
    });

    $address = app(ReverseGeocodingService::class)->resolve($latitude, $longitude);

    expect($address)->toBe('Avenue Nguma, Ngaliema, Kinshasa, République démocratique du Congo');

    Http::assertSent(function ($request): bool {
        if (! str_contains($request->url(), 'api.mapbox.com/search/geocode/v6/reverse')) {
            return false;
        }

        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return ! isset($query['limit'])
            && ($query['types'] ?? null) === 'address,street,neighborhood,locality,place';
    });
});

test('tracker trip addresses are resolved before the optional enrichment budget expires', function () {
    config([
        'services.maps.provider' => 'google',
        'services.google_maps.api_key' => 'AIza-test-key',
        'services.google_maps.roads_enabled' => false,
        'services.mapbox.public_token' => '',
    ]);

    $timezoneDelayed = false;

    Http::fake(function ($request) use (&$timezoneDelayed) {
        if (str_contains($request->url(), '/maps/api/timezone/json')) {
            if (! $timezoneDelayed) {
                $timezoneDelayed = true;
                usleep(5_100_000);
            }

            return Http::response([
                'status' => 'OK',
                'timeZoneId' => 'Africa/Kinshasa',
            ]);
        }

        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
        $address = str_starts_with((string) ($query['latlng'] ?? ''), '-4.352')
            ? 'Avenue Kasa-Vubu, Kinshasa, Congo-Kinshasa'
            : 'Avenue des Huileries, Kinshasa, Congo-Kinshasa';

        return Http::response([
            'status' => 'OK',
            'results' => [[
                'types' => ['route'],
                'formatted_address' => $address,
                'geometry' => ['location_type' => 'GEOMETRIC_CENTER'],
                'address_components' => [
                    ['long_name' => str($address)->before(',')->toString(), 'types' => ['route']],
                    ['long_name' => 'Kinshasa', 'types' => ['locality']],
                    ['long_name' => 'Congo-Kinshasa', 'types' => ['country']],
                ],
            ]],
        ]);
    });

    $superadmin = User::factory()->superadmin()->create();
    $device = Device::factory()->create();

    foreach ([
        ['time' => now()->setTime(11, 5), 'lat' => -4.3476283, 'lng' => 15.3145250],
        ['time' => now()->setTime(11, 9), 'lat' => -4.3520000, 'lng' => 15.3210600],
    ] as $point) {
        Position::factory()->forDevice($device)->create([
            'server_time' => $point['time'],
            'gps_time' => $point['time'],
            'latitude' => $point['lat'],
            'longitude' => $point['lng'],
            'address' => null,
            'movement' => true,
            'speed' => 20,
            'raw_data' => null,
        ]);
    }

    $response = $this->actingAs($superadmin)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('trackers.trips', ['device' => $device, 'period' => 'today']))
        ->assertSuccessful();

    expect($response->json('html'))
        ->toContain('Avenue des Huileries')
        ->toContain('Avenue Kasa-Vubu')
        ->not->toContain('Latitude :')
        ->and($timezoneDelayed)->toBeTrue();
});

test('tracker trips resolve each boundary from its own coordinates when stored addresses are stale', function () {
    config([
        'services.maps.provider' => 'google',
        'services.google_maps.api_key' => 'AIza-test-key',
        'services.mapbox.public_token' => '',
    ]);

    Http::fake(function ($request) {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        $address = str_starts_with((string) ($query['latlng'] ?? ''), '-4.342')
            ? '128 Rue De Bolobo, Kinshasa, Congo-Kinshasa'
            : 'Avenue De Kapanga, Kinshasa, Congo-Kinshasa';

        return Http::response([
            'status' => 'OK',
            'results' => [[
                'types' => ['street_address'],
                'formatted_address' => $address,
                'geometry' => ['location_type' => 'ROOFTOP'],
                'address_components' => [
                    ['long_name' => str($address)->before(',')->toString(), 'types' => ['route']],
                    ['long_name' => 'Kinshasa', 'types' => ['locality']],
                    ['long_name' => 'Congo-Kinshasa', 'types' => ['country']],
                ],
            ]],
        ]);
    });

    $superadmin = User::factory()->superadmin()->create();
    $device = Device::factory()->create();
    $staleAddress = '196, Avenue de l Ecole, Kinshasa, Congo-Kinshasa';

    Position::factory()->forDevice($device)->create([
        'server_time' => now()->setTime(10, 0),
        'gps_time' => now()->setTime(10, 0),
        'latitude' => -4.3414,
        'longitude' => 15.2867,
        'address' => $staleAddress,
        'movement' => true,
        'speed' => 20,
    ]);
    Position::factory()->forDevice($device)->create([
        'server_time' => now()->setTime(10, 8),
        'gps_time' => now()->setTime(10, 8),
        'latitude' => -4.3420,
        'longitude' => 15.2872,
        'address' => $staleAddress,
        'movement' => true,
        'speed' => 18,
    ]);

    $response = $this->actingAs($superadmin)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('trackers.trips', ['device' => $device, 'period' => 'today']))
        ->assertSuccessful();

    expect($response->json('html'))
        ->toContain('Avenue De Kapanga')
        ->toContain('128 Rue De Bolobo')
        ->not->toContain($staleAddress);
});

test('tracker trips improve generic addresses with google geocoding and snap path to roads', function () {
    config([
        'services.maps.provider' => 'google',
        'services.google_maps.api_key' => 'AIza-test-key',
        'services.google_maps.roads_enabled' => true,
        'services.mapbox.public_token' => '',
    ]);

    Http::fake([
        'maps.googleapis.com/maps/api/geocode/json*' => Http::response([
            'status' => 'OK',
            'results' => [
                [
                    'types' => ['route'],
                    'formatted_address' => 'Avenue des Ecuries, Joli Parc, Ngaliema, Kinshasa, Congo-Kinshasa',
                    'geometry' => ['location_type' => 'GEOMETRIC_CENTER'],
                    'address_components' => [
                        ['long_name' => 'Avenue des Ecuries', 'types' => ['route']],
                        ['long_name' => 'Joli Parc', 'types' => ['neighborhood']],
                        ['long_name' => 'Ngaliema', 'types' => ['sublocality_level_1']],
                        ['long_name' => 'Kinshasa', 'types' => ['locality']],
                        ['long_name' => 'Congo-Kinshasa', 'types' => ['country']],
                    ],
                ],
                [
                    'types' => ['locality'],
                    'formatted_address' => 'Kinshasa, Kinshasa, République démocratique du Congo',
                    'geometry' => ['location_type' => 'APPROXIMATE'],
                ],
            ],
        ]),
        'roads.googleapis.com/v1/snapToRoads*' => Http::response([
            'snappedPoints' => [
                ['location' => ['latitude' => -4.33510, 'longitude' => 15.25010]],
                ['location' => ['latitude' => -4.33520, 'longitude' => 15.25110]],
                ['location' => ['latitude' => -4.33540, 'longitude' => 15.25210]],
            ],
        ]),
        'maps.googleapis.com/maps/api/timezone/json*' => Http::response([
            'status' => 'OK',
            'timeZoneId' => 'Africa/Kinshasa',
        ]),
    ]);

    $superadmin = User::factory()->superadmin()->create();
    $device = Device::factory()->create();
    $start = Position::factory()->forDevice($device)->create([
        'server_time' => now()->setTime(7, 4),
        'gps_time' => now()->setTime(7, 4),
        'latitude' => -4.33507,
        'longitude' => 15.25042,
        'address' => 'Kinshasa, Kinshasa, République démocratique du Congo',
        'movement' => true,
        'speed' => 24,
    ]);
    Position::factory()->forDevice($device)->create([
        'server_time' => now()->setTime(7, 19),
        'gps_time' => now()->setTime(7, 19),
        'latitude' => -4.33590,
        'longitude' => 15.25240,
        'address' => 'Kinshasa, Kinshasa, République démocratique du Congo',
        'movement' => true,
        'speed' => 21,
    ]);

    $response = $this->actingAs($superadmin)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('trackers.trips', ['device' => $device, 'period' => 'today']))
        ->assertSuccessful();

    expect($response->json('html'))
        ->toContain('Avenue des Ecuries')
        ->not->toContain('Kinshasa, Kinshasa, République démocratique du Congo')
        ->and($response->json('geojson.features.0.geometry.coordinates'))
        ->toBe([
            [15.2501, -4.3351],
            [15.2511, -4.3352],
            [15.2521, -4.3354],
        ])
        ->and($start->refresh()->address)
        ->toBe('Avenue des Ecuries, Joli Parc, Ngaliema, Kinshasa, Congo-Kinshasa');
});

test('authenticated users can view the map page with google maps as default provider', function () {
    $user = User::factory()->superadmin()->create();
    config([
        'services.google_maps.api_key' => 'AIza-test-key',
    ]);

    $this->actingAs($user)
        ->get(route('map.index'))
        ->assertSuccessful()
        ->assertSee('maps.googleapis.com/maps/api/js', false)
        ->assertSee('js/google-map.js', false)
        ->assertSee('trackerDetailsModal', false)
        ->assertSee('js/tracker-details.js', false)
        ->assertSee('20260903-tracker-details-corporate', false)
        ->assertDontSee('vendor/mapbox/mapbox-gl.css', false)
        ->assertDontSee('vendor/mapbox/mapbox-gl.js', false)
        ->assertDontSee('js/map.js?v=20260602-mapbox-trips', false)
        ->assertSee('exadMapConfig', false);
});

test('the tracking map uses the mapbox provider selected in application settings', function () {
    $user = User::factory()->superadmin()->create();
    ApplicationSetting::query()->firstOrFail()->update(['map_provider' => 'mapbox']);

    $this->actingAs($user)
        ->get(route('map.index'))
        ->assertSuccessful()
        ->assertSee('vendor/mapbox/mapbox-gl.css', false)
        ->assertSee('vendor/mapbox/mapbox-gl.js', false)
        ->assertSee('js/map.js', false)
        ->assertDontSee('maps.googleapis.com/maps/api/js', false)
        ->assertDontSee('https://api.mapbox.com/mapbox-gl-js', false)
        ->assertSee('exadMapConfig', false);
});

test('map devices endpoint returns geojson for every positioned tracker to superadmin', function () {
    config(['services.google_maps.api_key' => '']);

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
        'last_speed' => 0,
        'last_movement' => false,
        'last_ignition' => false,
        'last_angle' => 90,
        'last_seen_at' => now(),
    ]);

    $idleVehicle = Vehicle::factory()->create([
        'fleet_id' => $fleet->id,
        'name' => 'Toyota Ralenti',
        'registration_number' => 'MAP-002',
    ]);
    Device::factory()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $idleVehicle->id,
        'imei' => '356307042441014',
        'name' => 'Traceur Ralenti',
        'status' => 'online',
        'last_latitude' => -4.326,
        'last_longitude' => 15.313,
        'last_speed' => 0,
        'last_movement' => false,
        'last_ignition' => true,
        'last_seen_at' => now(),
    ]);

    $movingVehicle = Vehicle::factory()->create([
        'fleet_id' => $fleet->id,
        'name' => 'Toyota Mouvement',
        'registration_number' => 'MAP-003',
    ]);
    $movingDevice = Device::factory()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $movingVehicle->id,
        'imei' => '356307042441015',
        'name' => 'Traceur Mouvement',
        'status' => 'online',
        'last_latitude' => -4.328,
        'last_longitude' => 15.315,
        'last_speed' => 36,
        'last_movement' => true,
        'last_ignition' => true,
        'last_angle' => 180,
        'last_seen_at' => now(),
    ]);
    Position::factory()->forDevice($movingDevice)->create([
        'latitude' => -4.330,
        'longitude' => 15.314,
        'speed' => 32,
        'movement' => true,
        'server_time' => now()->subMinutes(3),
    ]);
    Position::factory()->forDevice($movingDevice)->create([
        'latitude' => -4.329,
        'longitude' => 15.3145,
        'speed' => 34,
        'movement' => true,
        'server_time' => now()->subMinute(),
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
        ->assertJsonPath('summary.total', 4)
        ->assertJsonPath('summary.positioned', 4);

    $features = collect($response->json('geojson.features'));
    $toyotaFeature = $features->firstWhere('properties.vehicle', 'Toyota Carte');
    $idleFeature = $features->firstWhere('properties.vehicle', 'Toyota Ralenti');
    $movingFeature = $features->firstWhere('properties.vehicle', 'Toyota Mouvement');

    expect($features->all())
        ->toHaveCount(4)
        ->and($features->pluck('properties.vehicle')->all())
        ->toContain('Toyota Carte')
        ->and($toyotaFeature['properties']['is_parking'])
        ->toBeTrue()
        ->and($toyotaFeature['properties']['is_stationary_running'])
        ->toBeFalse()
        ->and($idleFeature['properties']['is_parking'])
        ->toBeFalse()
        ->and($idleFeature['properties']['is_stationary_running'])
        ->toBeTrue()
        ->and($movingFeature['properties']['is_moving'])
        ->toBeTrue()
        ->and($movingFeature['properties']['trail'])
        ->toHaveCount(3)
        ->and($response->json('geojson.features.0.properties.details_url'))->toContain('/trackers/')
        ->and($response->json('geojson.features.0.properties.trips_url'))->toContain('/trackers/');
});

test('map fleet filtering uses the assigned vehicle fleet when the tracker fleet is stale', function () {
    $superadmin = User::factory()->superadmin()->create();
    $staleFleet = Fleet::factory()->create(['name' => 'Ancienne flotte']);
    $vehicleFleet = Fleet::factory()->create(['name' => 'Flotte du vehicule', 'code' => 'VEH']);
    $staleFleetUser = User::factory()->admin($staleFleet->subscription)->forFleet($staleFleet)->create();
    $vehicleFleetUser = User::factory()->admin($vehicleFleet->subscription)->forFleet($vehicleFleet)->create();
    $vehicle = Vehicle::factory()->for($vehicleFleet)->create(['name' => 'Vehicule transfere']);

    Device::factory()->online()->create([
        'fleet_id' => $staleFleet->id,
        'vehicle_id' => $vehicle->id,
        'last_latitude' => -4.325,
        'last_longitude' => 15.312,
    ]);

    $this->actingAs($superadmin)
        ->getJson(route('map.devices', ['fleet_id' => $vehicleFleet->id]))
        ->assertSuccessful()
        ->assertJsonPath('summary.total', 1)
        ->assertJsonPath('geojson.features.0.properties.fleet', 'Flotte du vehicule')
        ->assertJsonPath('geojson.features.0.properties.fleet_code', 'VEH');

    $this->actingAs($superadmin)
        ->getJson(route('map.devices', ['fleet_id' => $staleFleet->id]))
        ->assertSuccessful()
        ->assertJsonPath('summary.total', 0)
        ->assertJsonCount(0, 'geojson.features');

    $this->actingAs($staleFleetUser)
        ->getJson(route('map.devices'))
        ->assertSuccessful()
        ->assertJsonCount(0, 'geojson.features');

    $this->actingAs($vehicleFleetUser)
        ->getJson(route('map.devices'))
        ->assertSuccessful()
        ->assertJsonCount(1, 'geojson.features');
});

test('map devices endpoint can filter positioned trackers by address city', function () {
    $superadmin = User::factory()->superadmin()->create();

    $kinshasaDevice = Device::factory()->create([
        'name' => 'Traceur Kin',
        'status' => 'online',
        'last_latitude' => -4.335,
        'last_longitude' => 15.225,
        'last_address' => 'Avenue Du Kwango, Gombe, Kinshasa, Republique democratique du Congo',
    ]);

    Device::factory()->create([
        'name' => 'Traceur Matadi',
        'status' => 'online',
        'last_latitude' => -5.81,
        'last_longitude' => 13.45,
        'last_address' => 'Avenue du Port, Matadi, Republique democratique du Congo',
    ]);

    $response = $this->actingAs($superadmin)
        ->getJson(route('map.devices', ['search' => 'Kinshasa']))
        ->assertSuccessful()
        ->assertJsonPath('summary.total', 1)
        ->assertJsonPath('summary.positioned', 1);

    expect($response->json('geojson.features'))
        ->toHaveCount(1)
        ->and($response->json('geojson.features.0.properties.id'))->toBe($kinshasaDevice->id);
});

test('map device marker keeps the exact gps position and movement trail follows recorded gps points', function () {
    config([
        'services.google_maps.api_key' => 'AIza-test-key',
        'services.google_maps.roads_enabled' => true,
    ]);

    Http::fake([
        'roads.googleapis.com/v1/snapToRoads*' => Http::response([
            'snappedPoints' => [
                ['location' => ['latitude' => -4.3000, 'longitude' => 15.3000]],
                ['location' => ['latitude' => -4.3010, 'longitude' => 15.3010]],
                ['location' => ['latitude' => -4.3020, 'longitude' => 15.3020]],
            ],
        ]),
        'roads.googleapis.com/v1/nearestRoads*' => Http::response([
            'snappedPoints' => [
                ['location' => ['latitude' => -4.3020, 'longitude' => 15.3020]],
            ],
        ]),
    ]);

    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create(['name' => 'Flotte precision']);
    $vehicle = Vehicle::factory()->create([
        'fleet_id' => $fleet->id,
        'name' => 'Toyota Land Cruiser',
        'registration_number' => '2058AG10',
    ]);
    $device = Device::factory()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'status' => 'online',
        'last_latitude' => -4.3330,
        'last_longitude' => 15.3330,
        'last_speed' => 24,
        'last_movement' => true,
        'last_ignition' => true,
        'last_seen_at' => now(),
    ]);

    Position::factory()->forDevice($device)->create([
        'latitude' => -4.3310,
        'longitude' => 15.3310,
        'speed' => 20,
        'movement' => true,
        'server_time' => now()->subMinutes(2),
    ]);
    Position::factory()->forDevice($device)->create([
        'latitude' => -4.3320,
        'longitude' => 15.3320,
        'speed' => 22,
        'movement' => true,
        'server_time' => now()->subMinute(),
    ]);

    $response = $this->actingAs($superadmin)
        ->getJson(route('map.devices'))
        ->assertSuccessful();

    $feature = $response->json('geojson.features.0');

    expect($feature['geometry']['coordinates'])
        ->toBe([15.333, -4.333])
        ->and($feature['properties']['trail'])
        ->toBe([
            [15.331, -4.331],
            [15.332, -4.332],
            [15.333, -4.333],
        ]);

    Http::assertNotSent(fn ($request): bool => str_contains((string) $request->url(), 'nearestRoads'));
});

test('map marker state gives priority to ignition off parking over stale movement data', function () {
    config(['services.google_maps.api_key' => '']);

    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create(['name' => 'Flotte parking']);
    $vehicle = Vehicle::factory()->create([
        'fleet_id' => $fleet->id,
        'name' => 'Suzuki Horly',
        'registration_number' => '5062BE01',
    ]);
    $device = Device::factory()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'status' => 'online',
        'last_latitude' => -4.3330,
        'last_longitude' => 15.3330,
        'last_speed' => 24,
        'last_movement' => true,
        'last_ignition' => false,
        'last_seen_at' => now(),
    ]);

    Position::factory()->forDevice($device)->create([
        'latitude' => -4.3310,
        'longitude' => 15.3310,
        'speed' => 20,
        'movement' => true,
        'server_time' => now()->subMinute(),
    ]);

    $feature = $this->actingAs($superadmin)
        ->getJson(route('map.devices'))
        ->assertSuccessful()
        ->json('geojson.features.0');

    expect($feature['properties']['is_parking'])
        ->toBeTrue()
        ->and($feature['properties']['is_moving'])
        ->toBeFalse()
        ->and($feature['properties']['is_stationary_running'])
        ->toBeFalse()
        ->and($feature['properties']['trail'])
        ->toBe([]);
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

test('superadmin can manage alert rules with ajax datatable', function () {
    $this->seed(AlertRuleSeeder::class);

    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create([
        'name' => 'EXAD CARS',
        'code' => 'EXAD1505',
    ]);
    $vehicle = Vehicle::factory()->create([
        'fleet_id' => $fleet->id,
        'name' => 'Toyota Hilux',
        'registration_number' => '1234BV01',
    ]);
    $device = Device::factory()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'name' => 'OBD2',
        'imei' => '353201355315547',
    ]);

    $this->actingAs($superadmin)
        ->withSession(['locale' => 'fr'])
        ->get(route('alert-rules.index'))
        ->assertSuccessful()
        ->assertSee('alertRuleModal', false)
        ->assertSee('data-rule-create', false)
        ->assertSee('data-datatable-search-form', false)
        ->assertSee('datatable-sort-link', false)
        ->assertSee('no_signal', false)
        ->assertSee('platform', false);

    $response = $this->actingAs($superadmin)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->getJson(route('alert-rules.index', [
            'search' => 'signal',
            'sort' => 'severity',
            'direction' => 'asc',
        ]))
        ->assertSuccessful()
        ->assertJsonStructure([
            'html',
            'stats' => ['total', 'active', 'equipment', 'vehicle'],
        ]);

    expect($response->json('html'))
        ->toContain('data-datatable-sort')
        ->toContain('no_signal');

    $this->actingAs($superadmin)
        ->post(route('alert-rules.store'), [
            'name' => 'Vitesse Gombe',
            'type' => 'overspeed',
            'category' => 'vehicle',
            'severity' => 'high',
            'scope_type' => 'vehicle',
            'vehicle_id' => $vehicle->id,
            'device_id' => $device->id,
            'threshold_value' => 90,
            'threshold_unit' => 'km/h',
            'channels' => ['platform', 'email'],
            'schedule_days' => ['mon', 'tue'],
            'starts_at' => '08:00',
            'ends_at' => '18:00',
            'is_active' => '1',
        ])
        ->assertRedirect(route('alert-rules.index'))
        ->assertSessionHas('status');

    $this->assertDatabaseHas('alert_rules', [
        'name' => 'Vitesse Gombe',
        'type' => 'overspeed',
        'category' => 'vehicle',
        'scope_type' => 'vehicle',
        'vehicle_id' => $vehicle->id,
        'device_id' => null,
        'threshold_unit' => 'km/h',
    ]);
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

    Event::assertDispatched(AlertCreated::class);
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
