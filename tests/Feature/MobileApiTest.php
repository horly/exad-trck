<?php

use App\Models\Device;
use App\Models\Department;
use App\Models\Driver;
use App\Models\DriverIdentifier;
use App\Models\Fleet;
use App\Models\MobileSession;
use App\Models\Position;
use App\Models\TrackerEvent;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Fortify;
use Laravel\Sanctum\PersonalAccessToken;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

function mobileCredentials(User $user, array $overrides = []): array
{
    return [
        'email' => $user->email,
        'password' => 'password',
        'device_identifier' => 'device-'.str()->uuid(),
        'device_name' => 'Pixel Test',
        'platform' => 'android',
        'app_version' => '1.0.0',
        ...$overrides,
    ];
}

test('mobile login issues separate hashed access and refresh tokens', function () {
    $fleet = Fleet::factory()->create();
    $user = User::factory()->admin($fleet->subscription)->forFleet($fleet)->create();

    $response = $this->postJson(route('api.v1.mobile.auth.login'), mobileCredentials($user))
        ->assertSuccessful()
        ->assertJsonPath('data.two_factor_required', false)
        ->assertJsonPath('data.user.fleet.id', $fleet->id)
        ->assertJsonStructure([
            'data' => [
                'tokens' => ['token_type', 'access_token', 'expires_in', 'refresh_token', 'refresh_expires_in', 'session_id'],
            ],
        ]);

    $accessToken = $response->json('data.tokens.access_token');
    $refreshToken = $response->json('data.tokens.refresh_token');
    [$accessId] = explode('|', $accessToken, 2);
    [$refreshId] = explode('|', $refreshToken, 2);

    expect($accessToken)->not->toBe($refreshToken)
        ->and(MobileSession::query()->count())->toBe(1)
        ->and(PersonalAccessToken::query()->count())->toBe(2)
        ->and(PersonalAccessToken::query()->findOrFail($accessId)->token)->not->toContain($accessToken)
        ->and(PersonalAccessToken::query()->findOrFail($refreshId)->token)->not->toContain($refreshToken);

    $this->withToken($accessToken)
        ->getJson(route('api.v1.mobile.me'))
        ->assertSuccessful()
        ->assertJsonPath('data.email', $user->email);

    $this->app['auth']->forgetGuards();
    $this->flushHeaders()->withToken($accessToken)
        ->getJson(route('api.v1.mobile.bootstrap'))
        ->assertSuccessful()
        ->assertJsonPath('data.api_version', 'v1')
        ->assertJsonPath('data.branding.app_name', 'EXAD Tracking');

    $this->app['auth']->forgetGuards();
    $this->flushHeaders()->withToken($refreshToken)
        ->getJson(route('api.v1.mobile.me'))
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'INVALID_ACCESS_TOKEN');
});

test('mobile refresh rotates both tokens and logout revokes the session', function () {
    $fleet = Fleet::factory()->create();
    $user = User::factory()->admin($fleet->subscription)->forFleet($fleet)->create();
    $login = $this->postJson(route('api.v1.mobile.auth.login'), mobileCredentials($user))->assertSuccessful();
    $oldAccess = $login->json('data.tokens.access_token');
    $oldRefresh = $login->json('data.tokens.refresh_token');

    $this->app['auth']->forgetGuards();
    $refreshed = $this->flushHeaders()->withToken($oldRefresh)
        ->postJson(route('api.v1.mobile.auth.refresh'))
        ->assertSuccessful();
    $newAccess = $refreshed->json('data.tokens.access_token');
    $newRefresh = $refreshed->json('data.tokens.refresh_token');

    expect($newAccess)->not->toBe($oldAccess)
        ->and($newRefresh)->not->toBe($oldRefresh)
        ->and(PersonalAccessToken::query()->count())->toBe(2);

    $this->app['auth']->forgetGuards();
    $this->flushHeaders()->withToken($oldAccess)->getJson(route('api.v1.mobile.me'))->assertUnauthorized();
    $this->app['auth']->forgetGuards();
    $this->flushHeaders()->withToken($oldRefresh)->postJson(route('api.v1.mobile.auth.refresh'))->assertUnauthorized();

    $this->app['auth']->forgetGuards();
    $this->flushHeaders()->withToken($newAccess)
        ->postJson(route('api.v1.mobile.auth.logout'))
        ->assertSuccessful();

    expect(PersonalAccessToken::query()->count())->toBe(0)
        ->and(MobileSession::query()->firstOrFail()->revoked_at)->not->toBeNull();

    $this->app['auth']->forgetGuards();
    $this->flushHeaders()->withToken($newAccess)->getJson(route('api.v1.mobile.me'))->assertUnauthorized();
});

test('a new login on the same mobile device revokes the previous token pair', function () {
    $fleet = Fleet::factory()->create();
    $user = User::factory()->admin($fleet->subscription)->forFleet($fleet)->create();
    $credentials = mobileCredentials($user, ['device_identifier' => 'stable-device-id']);
    $firstAccess = $this->postJson(route('api.v1.mobile.auth.login'), $credentials)
        ->assertSuccessful()
        ->json('data.tokens.access_token');

    $this->postJson(route('api.v1.mobile.auth.login'), $credentials)->assertSuccessful();

    expect(PersonalAccessToken::query()->count())->toBe(2)
        ->and(MobileSession::query()->count())->toBe(2)
        ->and(MobileSession::query()->whereNotNull('revoked_at')->count())->toBe(1);

    $this->app['auth']->forgetGuards();
    $this->flushHeaders()->withToken($firstAccess)
        ->getJson(route('api.v1.mobile.me'))
        ->assertUnauthorized();
});

test('mobile logout all revokes every device session for the account', function () {
    $fleet = Fleet::factory()->create();
    $user = User::factory()->admin($fleet->subscription)->forFleet($fleet)->create();
    $this->postJson(route('api.v1.mobile.auth.login'), mobileCredentials($user, [
        'device_identifier' => 'first-device',
    ]))->assertSuccessful();
    $secondAccess = $this->postJson(route('api.v1.mobile.auth.login'), mobileCredentials($user, [
        'device_identifier' => 'second-device',
    ]))->assertSuccessful()->json('data.tokens.access_token');

    $this->app['auth']->forgetGuards();
    $this->flushHeaders()->withToken($secondAccess)
        ->postJson(route('api.v1.mobile.auth.logout-all'))
        ->assertSuccessful()
        ->assertJsonPath('revoked_sessions', 2);

    expect(PersonalAccessToken::query()->count())->toBe(0)
        ->and(MobileSession::query()->whereNotNull('revoked_at')->count())->toBe(2);
});

test('mobile login requires an active account with a fleet for client roles', function () {
    $disabled = User::factory()->disabled()->create();
    $withoutFleet = User::factory()->admin()->create(['fleet_id' => null]);

    $this->postJson(route('api.v1.mobile.auth.login'), mobileCredentials($disabled))
        ->assertForbidden()
        ->assertJsonPath('error.code', 'ACCOUNT_UNAVAILABLE');

    $this->postJson(route('api.v1.mobile.auth.login'), mobileCredentials($withoutFleet))
        ->assertForbidden()
        ->assertJsonPath('error.code', 'ACCOUNT_UNAVAILABLE');

    $this->postJson(route('api.v1.mobile.auth.login'), mobileCredentials($withoutFleet, ['password' => 'incorrect']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

test('confirmed two factor authentication is completed before mobile tokens are issued', function () {
    $fleet = Fleet::factory()->create();
    $user = User::factory()->admin($fleet->subscription)->forFleet($fleet)->create();
    $secret = app(Google2FA::class)->generateSecretKey();
    $user->forceFill([
        'two_factor_secret' => Fortify::currentEncrypter()->encrypt($secret),
        'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['recovery-test-code'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $challenge = $this->postJson(route('api.v1.mobile.auth.login'), mobileCredentials($user))
        ->assertStatus(202)
        ->assertJsonPath('data.two_factor_required', true)
        ->assertJsonMissingPath('data.tokens');

    expect(PersonalAccessToken::query()->count())->toBe(0);

    $challengeToken = $challenge->json('data.challenge_token');
    $this->postJson(route('api.v1.mobile.auth.two-factor'), [
        'challenge_token' => $challengeToken,
        'code' => '000000',
    ])->assertUnprocessable()->assertJsonValidationErrors('code');

    $code = app(Google2FA::class)->getCurrentOtp($secret);
    $this->postJson(route('api.v1.mobile.auth.two-factor'), [
        'challenge_token' => $challengeToken,
        'code' => $code,
    ])->assertSuccessful()->assertJsonStructure(['data' => ['tokens' => ['access_token', 'refresh_token']]]);

    expect(PersonalAccessToken::query()->count())->toBe(2);

    $this->postJson(route('api.v1.mobile.auth.two-factor'), [
        'challenge_token' => $challengeToken,
        'code' => $code,
    ])->assertUnprocessable();
});

test('mobile operational data is restricted to the client fleet without tracker secrets', function () {
    $fleet = Fleet::factory()->create(['name' => 'Flotte Mobile']);
    $otherFleet = Fleet::factory()->create(['name' => 'Flotte Interdite']);
    $user = User::factory()->admin($fleet->subscription)->forFleet($fleet)->create();
    $vehicle = Vehicle::factory()->for($fleet)->create(['name' => 'Vehicule Mobile']);
    $otherVehicle = Vehicle::factory()->for($otherFleet)->create(['name' => 'Vehicule Secret']);
    $device = Device::factory()->moving()->create([
        'subscription_id' => $fleet->subscription_id,
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'imei' => '123456789012345',
        'name' => 'Traceur Technique',
        'model' => 'FMB920-SECRET',
        'last_ignition' => true,
        'last_latitude' => -4.325,
        'last_longitude' => 15.312,
    ]);
    Position::factory()->forDevice($device)->create([
        'latitude' => -4.326,
        'longitude' => 15.310,
        'server_time' => now()->subMinutes(2),
        'is_valid' => true,
    ]);
    Position::factory()->forDevice($device)->create([
        'latitude' => -4.3255,
        'longitude' => 15.311,
        'server_time' => now()->subMinute(),
        'is_valid' => true,
    ]);
    Device::factory()->online()->create([
        'subscription_id' => $otherFleet->subscription_id,
        'fleet_id' => $otherFleet->id,
        'vehicle_id' => $otherVehicle->id,
    ]);
    $accessToken = $this->postJson(route('api.v1.mobile.auth.login'), mobileCredentials($user))
        ->json('data.tokens.access_token');

    $response = $this->withToken($accessToken)
        ->getJson(route('api.v1.mobile.vehicles.index', ['fleet_id' => $otherFleet->id]))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Vehicule Mobile');

    $json = json_encode($response->json(), JSON_THROW_ON_ERROR);
    expect($json)
        ->not->toContain('123456789012345')
        ->not->toContain('Traceur Technique')
        ->not->toContain('FMB920-SECRET')
        ->not->toContain('Vehicule Secret');

    $this->withToken($accessToken)
        ->getJson(route('api.v1.mobile.vehicles.show', $otherVehicle->id))
        ->assertNotFound();

    $map = $this->withToken($accessToken)
        ->getJson(route('api.v1.mobile.map.vehicles', ['fleet_id' => $otherFleet->id]))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.geojson.features')
        ->assertJsonPath('data.summary.moving', 1)
        ->assertJsonPath('data.geojson.features.0.properties.is_moving', true)
        ->assertJsonPath('data.geojson.features.0.properties.is_parking', false)
        ->assertJsonPath('data.geojson.features.0.properties.is_stationary_running', false)
        ->assertJsonCount(3, 'data.geojson.features.0.properties.trail');

    expect(json_encode($map->json(), JSON_THROW_ON_ERROR))
        ->not->toContain('123456789012345')
        ->not->toContain('Traceur Technique')
        ->not->toContain('FMB920-SECRET');
});

test('simple mobile users need the map permission', function () {
    $fleet = Fleet::factory()->create();
    $restrictedUser = User::factory()->simpleUser($fleet->subscription)->forFleet($fleet)->create([
        'permissions' => [],
    ]);
    $allowedUser = User::factory()->simpleUser($fleet->subscription)->forFleet($fleet)->create([
        'permissions' => [User::PERMISSION_MAP_VIEW],
    ]);
    $restrictedToken = $this->postJson(route('api.v1.mobile.auth.login'), mobileCredentials($restrictedUser))
        ->json('data.tokens.access_token');

    $this->app['auth']->forgetGuards();
    $this->flushHeaders()->withToken($restrictedToken)
        ->getJson(route('api.v1.mobile.map.vehicles'))
        ->assertForbidden();

    $allowedToken = $this->postJson(route('api.v1.mobile.auth.login'), mobileCredentials($allowedUser))
        ->json('data.tokens.access_token');

    $this->app['auth']->forgetGuards();
    $this->flushHeaders()->withToken($allowedToken)
        ->getJson(route('api.v1.mobile.map.vehicles'))
        ->assertSuccessful();
});

test('mobile superadmin dashboard returns an aggregated fleet distribution', function () {
    $superadmin = User::factory()->superadmin()->create();
    $firstFleet = Fleet::factory()->create(['name' => 'Alpha Fleet', 'code' => 'ALP']);
    $secondFleet = Fleet::factory()->create(['name' => 'Beta Fleet', 'code' => 'BET']);
    $onlineVehicle = Vehicle::factory()->for($firstFleet)->create();
    Vehicle::factory()->for($secondFleet)->count(2)->create();
    Device::factory()->online()->create([
        'subscription_id' => $firstFleet->subscription_id,
        'fleet_id' => $firstFleet->id,
        'vehicle_id' => $onlineVehicle->id,
    ]);
    $accessToken = $this->postJson(
        route('api.v1.mobile.auth.login'),
        mobileCredentials($superadmin),
    )->assertSuccessful()->json('data.tokens.access_token');

    $this->app['auth']->forgetGuards();
    $this->flushHeaders()->withToken($accessToken)
        ->getJson(route('api.v1.mobile.dashboard'))
        ->assertSuccessful()
        ->assertJsonPath('data.summary.fleets_total', 2)
        ->assertJsonCount(2, 'data.fleet_distribution')
        ->assertJsonPath('data.fleet_distribution.0.name', 'Alpha Fleet')
        ->assertJsonPath('data.fleet_distribution.0.vehicles_total', 1)
        ->assertJsonPath('data.fleet_distribution.0.vehicles_online', 1)
        ->assertJsonPath('data.fleet_distribution.1.name', 'Beta Fleet')
        ->assertJsonPath('data.fleet_distribution.1.vehicles_total', 2)
        ->assertJsonPath('data.fleet_distribution.1.vehicles_online', 0);
});

test('mobile vehicle trips are scoped to the visible fleet without tracker secrets', function () {
    $fleet = Fleet::factory()->create();
    $otherFleet = Fleet::factory()->create();
    $user = User::factory()->admin($fleet->subscription)->forFleet($fleet)->create();
    $vehicle = Vehicle::factory()->for($fleet)->create(['name' => 'Vehicule Trajet']);
    $otherVehicle = Vehicle::factory()->for($otherFleet)->create();
    $device = Device::factory()->online()->create([
        'subscription_id' => $fleet->subscription_id,
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'imei' => '123456789012345',
        'name' => 'Traceur Secret',
    ]);
    Device::factory()->online()->create([
        'subscription_id' => $otherFleet->subscription_id,
        'fleet_id' => $otherFleet->id,
        'vehicle_id' => $otherVehicle->id,
    ]);
    Position::factory()->forDevice($device)->create([
        'server_time' => now()->startOfDay()->addHour(),
        'gps_time' => now()->startOfDay()->addHour(),
        'latitude' => -4.325,
        'longitude' => 15.31,
        'speed' => 20,
        'movement' => true,
        'address' => 'Kinshasa',
    ]);
    Position::factory()->forDevice($device)->create([
        'server_time' => now()->startOfDay()->addHour()->addMinutes(3),
        'gps_time' => now()->startOfDay()->addHour()->addMinutes(3),
        'latitude' => -4.324,
        'longitude' => 15.312,
        'speed' => 25,
        'movement' => true,
        'address' => 'Kinshasa',
    ]);
    $accessToken = $this->postJson(route('api.v1.mobile.auth.login'), mobileCredentials($user))
        ->assertSuccessful()
        ->json('data.tokens.access_token');

    $response = $this->withToken($accessToken)
        ->getJson(route('api.v1.mobile.vehicles.trips', ['vehicle' => $vehicle->id, 'period' => 'today']))
        ->assertSuccessful()
        ->assertJsonPath('data.vehicle.id', $vehicle->id)
        ->assertJsonPath('data.summary.count', 1)
        ->assertJsonCount(1, 'data.trips');

    expect(json_encode($response->json(), JSON_THROW_ON_ERROR))
        ->not->toContain('123456789012345')
        ->not->toContain('Traceur Secret');

    $this->withToken($accessToken)
        ->getJson(route('api.v1.mobile.vehicles.trips', $otherVehicle->id))
        ->assertNotFound();
});

test('mobile vehicle details mirror the operational web sections without tracker identity', function () {
    $fleet = Fleet::factory()->create();
    $user = User::factory()->admin($fleet->subscription)->forFleet($fleet)->create();
    $vehicle = Vehicle::factory()->for($fleet)->create(['name' => 'Vehicule Detail']);
    $device = Device::factory()->online()->create([
        'subscription_id' => $fleet->subscription_id,
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'imei' => '999999999999999',
        'name' => 'Nom Traceur Secret',
        'model' => 'Modele Traceur Secret',
        'last_ignition' => true,
        'last_movement' => true,
        'last_satellites' => 10,
        'last_gsm_signal' => 4,
        'last_external_voltage' => 12.8,
        'last_battery_voltage' => 4.1,
        'last_battery_level' => 90,
        'last_odometer_km' => 1524.25,
        'last_engine_seconds' => 7200,
        'last_obd_rpm' => 1800,
        'last_obd_speed' => 35,
        'last_obd_engine_temperature_c' => 84,
        'last_io' => ['ignition' => true],
        'last_sensors' => ['temperature' => 84],
        'last_driver_identifier_uid' => 'RFID-MOBILE-001',
    ]);
    $position = Position::factory()->forDevice($device)->create([
        'server_time' => now(),
        'latitude' => -4.325,
        'longitude' => 15.31,
        'altitude' => 281,
        'address' => 'Kinshasa',
        'angle' => 90,
        'movement' => true,
        'ignition' => true,
    ]);
    $department = Department::query()->create([
        'fleet_id' => $fleet->id,
        'name' => 'Operations',
        'code' => 'OPS',
    ]);
    $driver = Driver::query()->create([
        'fleet_id' => $fleet->id,
        'department_id' => $department->id,
        'first_name' => 'Jean',
        'last_name' => 'Conducteur',
        'employee_id' => 'EMP-001',
        'phone' => '+243000000000',
        'status' => 'active',
    ]);
    $driver->vehicles()->attach($vehicle);
    DriverIdentifier::query()->create([
        'driver_id' => $driver->id,
        'type' => 'rfid',
        'uid' => 'RFID-MOBILE-001',
        'active' => true,
    ]);
    TrackerEvent::query()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'device_id' => $device->id,
        'position_id' => $position->id,
        'type' => 'movement_started',
        'title' => 'Mouvement',
        'message' => 'Le vehicule est en mouvement.',
        'started_at' => now(),
    ]);
    $accessToken = $this->postJson(route('api.v1.mobile.auth.login'), mobileCredentials($user))
        ->assertSuccessful()
        ->json('data.tokens.access_token');

    $response = $this->withToken($accessToken)
        ->getJson(route('api.v1.mobile.vehicles.details', $vehicle->id))
        ->assertSuccessful()
        ->assertJsonMissingPath('data.details.tracker')
        ->assertJsonPath('data.details.location.address', 'Kinshasa')
        ->assertJsonPath('data.details.location.gps_quality_percent', 70)
        ->assertJsonPath('data.details.driver.full_name', 'Jean Conducteur')
        ->assertJsonPath('data.details.power.external_voltage', 12.8)
        ->assertJsonPath('data.details.gsm.signal_percent', 80)
        ->assertJsonPath('data.details.diagnostic.satellites', 10)
        ->assertJsonPath('data.details.obd_can.rpm', 1800)
        ->assertJsonCount(1, 'data.details.recent_events');

    expect(json_encode($response->json(), JSON_THROW_ON_ERROR))
        ->not->toContain('999999999999999')
        ->not->toContain('Nom Traceur Secret')
        ->not->toContain('Modele Traceur Secret');
});

test('mobile superadmin vehicle details include the tracker technical identity', function () {
    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create();
    $vehicle = Vehicle::factory()->for($fleet)->create(['name' => 'Vehicule Superadmin']);
    $device = Device::factory()->online()->create([
        'subscription_id' => $fleet->subscription_id,
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'imei' => '868120000000001',
        'name' => 'Traceur Direction',
        'brand' => 'teltonika',
        'model' => 'FMB920',
    ]);
    $accessToken = $this->postJson(
        route('api.v1.mobile.auth.login'),
        mobileCredentials($superadmin),
    )->assertSuccessful()->json('data.tokens.access_token');

    $this->app['auth']->forgetGuards();
    $this->flushHeaders()->withToken($accessToken)
        ->getJson(route('api.v1.mobile.vehicles.details', $vehicle->id))
        ->assertSuccessful()
        ->assertJsonPath('data.details.tracker.id', $device->id)
        ->assertJsonPath('data.details.tracker.name', 'Traceur Direction')
        ->assertJsonPath('data.details.tracker.imei', '868120000000001')
        ->assertJsonPath('data.details.tracker.brand', 'teltonika')
        ->assertJsonPath('data.details.tracker.model', 'FMB920');
});
