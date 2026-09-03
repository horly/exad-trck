<?php

use App\Actions\ClaimDeviceCommandAction;
use App\Actions\RequestDeviceCommandAction;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Fleet;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function engineControlDevice(): Device
{
    $fleet = Fleet::factory()->create();
    $vehicle = Vehicle::factory()->for($fleet)->create();

    return Device::factory()->online()->create([
        'subscription_id' => $fleet->subscription_id,
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'brand' => 'teltonika',
        'model' => 'FMB140',
        'last_seen_at' => now(),
        'last_ignition' => false,
    ]);
}

test('a superadmin can confirm an ignition-gated immobilization request', function () {
    $superadmin = User::factory()->superadmin()->create();
    $device = engineControlDevice();

    $this->actingAs($superadmin)
        ->postJson(route('trackers.engine-commands.store', $device), [
            'action' => 'immobilize',
            'confirmation' => true,
        ])
        ->assertAccepted()
        ->assertJsonPath('status', DeviceCommand::STATUS_PENDING_SAFETY);

    $command = DeviceCommand::query()->firstOrFail();
    expect($command->command_text)->toBe('setigndigout 11 0 0')
        ->and($command->desired_outputs)->toBe(['1' => 1, '2' => 1])
        ->and($command->reason)->toBe(__('trackers.engine_control_audit_immobilize'))
        ->and($command->requester->is($superadmin))->toBeTrue()
        ->and($device->immobilizationProfile()->first()?->verified_by)->toBe($superadmin->id);
});

test('a fleet administrator can issue engine commands for their fleet', function () {
    $device = engineControlDevice();
    $admin = User::factory()->admin($device->subscription)->forFleet($device->vehicle->fleet)->create();

    $this->actingAs($admin)
        ->postJson(route('vehicles.engine-commands.store', $device->vehicle), [
            'action' => 'immobilize',
            'confirmation' => true,
        ])
        ->assertAccepted();

    expect(DeviceCommand::query()->firstOrFail()->requested_by)->toBe($admin->id);
});

test('legacy vehicle plan values no longer restrict engine commands', function () {
    $device = engineControlDevice();
    $device->vehicle->forceFill(['subscription_plan' => 'legacy-disabled'])->save();
    $admin = User::factory()->admin($device->subscription)->forFleet($device->vehicle->fleet)->create();

    $this->actingAs($admin)
        ->postJson(route('vehicles.engine-commands.store', $device->vehicle), [
            'action' => 'immobilize',
            'confirmation' => true,
        ])
        ->assertAccepted();

    expect(DeviceCommand::query()->firstOrFail()->requested_by)->toBe($admin->id);
});

test('a simple user needs an explicit engine control permission', function () {
    $device = engineControlDevice();
    $user = User::factory()->simpleUser($device->subscription)->forFleet($device->vehicle->fleet)->create();

    $this->actingAs($user)
        ->postJson(route('vehicles.engine-commands.store', $device->vehicle), [
            'action' => 'immobilize',
            'confirmation' => true,
        ])
        ->assertForbidden();

    $user->update(['permissions' => [User::PERMISSION_ENGINE_CONTROL]]);

    $this->actingAs($user->fresh())
        ->postJson(route('vehicles.engine-commands.store', $device->vehicle), [
            'action' => 'immobilize',
            'confirmation' => true,
        ])
        ->assertAccepted();

    expect(DeviceCommand::query()->firstOrFail()->requested_by)->toBe($user->id);

    $user->update(['permissions' => []]);

    $this->actingAs($user->fresh())
        ->postJson(route('vehicles.engine-commands.store', $device->vehicle), [
            'action' => 'release',
            'confirmation' => true,
        ])
        ->assertForbidden();
});

test('a disabled delegated user cannot issue engine commands', function () {
    $device = engineControlDevice();
    $user = User::factory()->simpleUser($device->subscription)
        ->forFleet($device->vehicle->fleet)
        ->disabled()
        ->create([
            'permissions' => [User::PERMISSION_ENGINE_CONTROL],
        ]);

    $this->actingAs($user)
        ->postJson(route('vehicles.engine-commands.store', $device->vehicle), [
            'action' => 'immobilize',
            'confirmation' => true,
        ])
        ->assertForbidden();

    expect(DeviceCommand::query()->count())->toBe(0);
});

test('fleet users cannot control a vehicle from another fleet', function () {
    $device = engineControlDevice();
    $otherFleet = Fleet::factory()->create(['subscription_id' => $device->subscription_id]);
    $admin = User::factory()->admin($device->subscription)->forFleet($otherFleet)->create();
    $user = User::factory()->simpleUser($otherFleet->subscription)->forFleet($otherFleet)->create([
        'permissions' => [User::PERMISSION_ENGINE_CONTROL],
    ]);

    foreach ([$admin, $user] as $actor) {
        $this->actingAs($actor)
            ->postJson(route('vehicles.engine-commands.store', $device->vehicle), [
                'action' => 'immobilize',
                'confirmation' => true,
            ])
            ->assertForbidden();
    }

    expect(DeviceCommand::query()->count())->toBe(0);
});

test('the command action rechecks fleet authorization after locking the tracker', function () {
    $device = engineControlDevice();
    $otherFleet = Fleet::factory()->create(['subscription_id' => $device->subscription_id]);
    $user = User::factory()->simpleUser($otherFleet->subscription)->forFleet($otherFleet)->create([
        'permissions' => [User::PERMISSION_ENGINE_CONTROL],
    ]);

    expect(fn () => app(RequestDeviceCommandAction::class)->execute(
        $device,
        $user,
        DeviceCommand::ACTION_IMMOBILIZE,
        Request::create('/vehicles/engine-command', 'POST'),
    ))->toThrow(AuthorizationException::class);

    expect(DeviceCommand::query()->count())->toBe(0)
        ->and($device->immobilizationProfile()->count())->toBe(0);
});

test('unsupported FMB003 trackers cannot expose or receive engine commands', function () {
    $superadmin = User::factory()->superadmin()->create();
    $device = engineControlDevice();
    $device->update(['model' => 'FMB003']);

    $this->actingAs($superadmin)
        ->postJson(route('trackers.engine-commands.store', $device), [
            'action' => 'immobilize',
            'confirmation' => true,
        ])
        ->assertForbidden();

    $html = $this->getJson(route('trackers.details', $device))
        ->assertSuccessful()
        ->json('html');

    expect($superadmin->can('control-engine', $device->fresh()))->toBeFalse()
        ->and($html)->not->toContain('Immobilisation moteur')
        ->and(DeviceCommand::query()->count())->toBe(0)
        ->and($device->immobilizationProfile()->count())->toBe(0);
});

test('the command action also rejects an unsupported model directly', function () {
    $superadmin = User::factory()->superadmin()->create();
    $device = engineControlDevice();
    $device->update(['model' => 'FMB003']);

    expect(fn () => app(RequestDeviceCommandAction::class)->execute(
        $device,
        $superadmin,
        DeviceCommand::ACTION_IMMOBILIZE,
        Request::create('/trackers/engine-command', 'POST'),
    ))->toThrow(ValidationException::class);

    expect(DeviceCommand::query()->count())->toBe(0)
        ->and($device->immobilizationProfile()->count())->toBe(0);
});

test('engine controls appear for platform and authorized fleet users', function () {
    $device = engineControlDevice();
    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin($device->subscription)->forFleet($device->vehicle->fleet)->create();
    $simpleUser = User::factory()->simpleUser($device->subscription)->forFleet($device->vehicle->fleet)->create([
        'permissions' => [User::PERMISSION_MAP_VIEW],
    ]);
    $delegatedUser = User::factory()->simpleUser($device->subscription)->forFleet($device->vehicle->fleet)->create([
        'permissions' => [User::PERMISSION_MAP_VIEW, User::PERMISSION_ENGINE_CONTROL],
    ]);

    $html = $this->actingAs($superadmin)
        ->getJson(route('trackers.details', $device))
        ->assertSuccessful()
        ->json('html');

    expect($html)->toContain('Immobilisation moteur')
        ->and(substr_count($html, 'data-engine-control-trigger'))->toBe(1)
        ->and($html)->not->toContain('<dialog')
        ->and($html)->not->toContain('name="password"')
        ->and($html)->toContain('data-action="immobilize"')
        ->and(strpos($html, 'Données techniques'))->toBeLessThan(strpos($html, 'Immobilisation moteur'))
        ->and(strpos($html, 'Immobilisation moteur'))->toBeLessThan(strpos($html, 'Derniers événements'));

    $adminHtml = $this->actingAs($admin)
        ->getJson(route('vehicles.tracker-details', $device->vehicle))
        ->assertSuccessful()
        ->json('html');

    $simpleUserHtml = $this->actingAs($simpleUser)
        ->getJson(route('vehicles.tracker-details', $device->vehicle))
        ->assertSuccessful()
        ->json('html');

    $delegatedUserHtml = $this->actingAs($delegatedUser)
        ->getJson(route('vehicles.tracker-details', $device->vehicle))
        ->assertSuccessful()
        ->json('html');

    expect($adminHtml)->toContain('Immobilisation moteur')
        ->and($simpleUserHtml)->not->toContain('Immobilisation moteur')
        ->and($delegatedUserHtml)->toContain('Immobilisation moteur');
});

test('the single engine command changes to release after confirmed immobilization', function () {
    $superadmin = User::factory()->superadmin()->create();
    $device = engineControlDevice();

    $device->deviceCommands()->create([
        'uuid' => (string) Str::uuid(),
        'vehicle_id' => $device->vehicle_id,
        'fleet_id' => $device->fleet_id,
        'requested_by' => $superadmin->id,
        'imei' => $device->imei,
        'action' => DeviceCommand::ACTION_IMMOBILIZE,
        'status' => DeviceCommand::STATUS_CONFIRMED,
        'command_text' => 'setigndigout 11 0 0',
        'desired_outputs' => ['1' => 1, '2' => 1],
        'reason' => 'Confirmed immobilization request.',
        'confirmed_at' => now(),
        'expires_at' => now()->addMinutes(10),
    ]);

    $html = $this->actingAs($superadmin)
        ->getJson(route('trackers.details', $device))
        ->assertSuccessful()
        ->json('html');

    expect(substr_count($html, 'data-engine-control-trigger'))->toBe(1)
        ->and($html)->toContain('data-action="release"')
        ->and($html)->toContain('Autoriser le démarrage');
});

test('an immobilization command cannot be claimed while telemetry is unsafe', function () {
    $superadmin = User::factory()->superadmin()->create();
    $device = engineControlDevice();
    $profile = $device->immobilizationProfile()->create([
        'verified_by' => $superadmin->id,
        'verified_at' => now(),
    ]);
    $command = $device->deviceCommands()->create([
        'uuid' => (string) Str::uuid(),
        'vehicle_id' => $device->vehicle_id,
        'fleet_id' => $device->fleet_id,
        'requested_by' => $superadmin->id,
        'imei' => $device->imei,
        'action' => DeviceCommand::ACTION_IMMOBILIZE,
        'command_text' => 'setigndigout 11 0 0',
        'desired_outputs' => $profile->outputsFor(DeviceCommand::ACTION_IMMOBILIZE),
        'reason' => 'Immobilisation de sécurité demandée.',
        'expires_at' => now()->addMinutes(10),
    ]);

    $result = app(ClaimDeviceCommandAction::class)->execute($device);

    expect($result['command'])->toBeNull()
        ->and($command->refresh()->status)->toBe(DeviceCommand::STATUS_PENDING_SAFETY)
        ->and($command->safety_snapshot['safe'])->toBeFalse();
});
