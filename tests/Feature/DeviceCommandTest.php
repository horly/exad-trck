<?php

use App\Actions\ClaimDeviceCommandAction;
use App\Actions\ConfirmDeviceCommandsAction;
use App\Actions\RequestDeviceCommandAction;
use App\Actions\UpdateDeviceCommandAction;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Fleet;
use App\Models\Position;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
            'output' => 1,
            'confirmation' => true,
        ])
        ->assertAccepted()
        ->assertJsonPath('status', DeviceCommand::STATUS_PENDING_SAFETY);

    $command = DeviceCommand::query()->firstOrFail();
    expect($command->command_text)->toBe('setigndigout 1? 0 ?')
        ->and($command->desired_outputs)->toBe(['1' => 1, '2' => null])
        ->and($command->reason)->toBe(__('trackers.output_control_audit_activate', ['output' => 1]))
        ->and($command->requester->is($superadmin))->toBeTrue()
        ->and($device->immobilizationProfile()->count())->toBe(0);
});

test('a fleet administrator can issue engine commands for their fleet', function () {
    $device = engineControlDevice();
    $admin = User::factory()->admin($device->subscription)->forFleet($device->vehicle->fleet)->create();

    $this->actingAs($admin)
        ->postJson(route('vehicles.engine-commands.store', $device->vehicle), [
            'action' => 'immobilize',
            'output' => 1,
            'confirmation' => true,
        ])
        ->assertAccepted();

    expect(DeviceCommand::query()->firstOrFail()->requested_by)->toBe($admin->id);
});

test('commands target one output and ignore the other output and timeout', function () {
    $superadmin = User::factory()->superadmin()->create();
    $device = engineControlDevice();

    $this->actingAs($superadmin)
        ->postJson(route('trackers.engine-commands.store', $device), [
            'action' => DeviceCommand::ACTION_RELEASE,
            'output' => 2,
            'confirmation' => true,
        ])
        ->assertAccepted()
        ->assertJsonPath('output', 2);

    $command = DeviceCommand::query()->firstOrFail();

    expect($command->command_text)->toBe('setigndigout ?0 ? 0')
        ->and($command->desired_outputs)->toBe(['1' => null, '2' => 0]);
});

test('an explicit tracker output is required', function () {
    $superadmin = User::factory()->superadmin()->create();
    $device = engineControlDevice();

    $this->actingAs($superadmin)
        ->postJson(route('trackers.engine-commands.store', $device), [
            'action' => DeviceCommand::ACTION_IMMOBILIZE,
            'confirmation' => true,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('output');

    expect(DeviceCommand::query()->count())->toBe(0);
});

test('legacy vehicle plan values no longer restrict engine commands', function () {
    $device = engineControlDevice();
    $device->vehicle->forceFill(['subscription_plan' => 'legacy-disabled'])->save();
    $admin = User::factory()->admin($device->subscription)->forFleet($device->vehicle->fleet)->create();

    $this->actingAs($admin)
        ->postJson(route('vehicles.engine-commands.store', $device->vehicle), [
            'action' => 'immobilize',
            'output' => 1,
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
            'output' => 1,
            'confirmation' => true,
        ])
        ->assertForbidden();

    $user->update(['permissions' => [User::PERMISSION_ENGINE_CONTROL]]);

    $this->actingAs($user->fresh())
        ->postJson(route('vehicles.engine-commands.store', $device->vehicle), [
            'action' => 'immobilize',
            'output' => 1,
            'confirmation' => true,
        ])
        ->assertAccepted();

    expect(DeviceCommand::query()->firstOrFail()->requested_by)->toBe($user->id);

    $user->update(['permissions' => []]);

    $this->actingAs($user->fresh())
        ->postJson(route('vehicles.engine-commands.store', $device->vehicle), [
            'action' => 'release',
            'output' => 1,
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
            'output' => 1,
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
                'output' => 1,
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
        1,
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
            'output' => 1,
            'confirmation' => true,
        ])
        ->assertForbidden();

    $html = $this->getJson(route('trackers.details', $device))
        ->assertSuccessful()
        ->json('html');

    expect($superadmin->can('control-engine', $device->fresh()))->toBeFalse()
        ->and($html)->not->toContain(__('trackers.output_control_title'))
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
        1,
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

    expect($html)->toContain(__('trackers.output_control_title'))
        ->and(substr_count($html, 'data-engine-control-trigger'))->toBe(2)
        ->and($html)->toContain('data-output="1"')
        ->and($html)->toContain('data-output="2"')
        ->and($html)->not->toContain('<dialog')
        ->and($html)->not->toContain('name="password"')
        ->and($html)->toContain('data-action="immobilize"')
        ->and(strpos($html, 'Données techniques'))->toBeLessThan(strpos($html, __('trackers.output_control_title')))
        ->and(strpos($html, __('trackers.output_control_title')))->toBeLessThan(strpos($html, 'Derniers événements'));

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

    expect($adminHtml)->toContain(__('trackers.output_control_title'))
        ->and($simpleUserHtml)->not->toContain(__('trackers.output_control_title'))
        ->and($delegatedUserHtml)->toContain(__('trackers.output_control_title'));
});

test('each tracker output exposes its own action', function () {
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
        'command_text' => 'setigndigout 1? 0 ?',
        'desired_outputs' => ['1' => 1, '2' => null],
        'reason' => 'Confirmed immobilization request.',
        'confirmed_at' => now(),
        'expires_at' => now()->addMinutes(10),
    ]);

    $html = $this->actingAs($superadmin)
        ->getJson(route('trackers.details', $device))
        ->assertSuccessful()
        ->json('html');

    expect(substr_count($html, 'data-engine-control-trigger'))->toBe(2)
        ->and($html)->toContain('data-action="release"')
        ->and($html)->toContain('data-action="immobilize"');
});

test('an immobilization command cannot be claimed while telemetry is unsafe', function () {
    $superadmin = User::factory()->superadmin()->create();
    $device = engineControlDevice();
    $command = $device->deviceCommands()->create([
        'uuid' => (string) Str::uuid(),
        'vehicle_id' => $device->vehicle_id,
        'fleet_id' => $device->fleet_id,
        'requested_by' => $superadmin->id,
        'imei' => $device->imei,
        'action' => DeviceCommand::ACTION_IMMOBILIZE,
        'command_text' => 'setigndigout 1? 0 ?',
        'desired_outputs' => ['1' => 1, '2' => null],
        'reason' => 'Immobilisation de sécurité demandée.',
        'expires_at' => now()->addMinutes(10),
    ]);

    $result = app(ClaimDeviceCommandAction::class)->execute($device);

    expect($result['command'])->toBeNull()
        ->and($command->refresh()->status)->toBe(DeviceCommand::STATUS_PENDING_SAFETY)
        ->and($command->safety_snapshot['safe'])->toBeFalse();
});

test('a matching immediate tracker response confirms the command and its attempt', function () {
    $superadmin = User::factory()->superadmin()->create();
    $device = engineControlDevice();
    $command = $device->deviceCommands()->create([
        'uuid' => (string) Str::uuid(),
        'vehicle_id' => $device->vehicle_id,
        'fleet_id' => $device->fleet_id,
        'requested_by' => $superadmin->id,
        'imei' => $device->imei,
        'action' => DeviceCommand::ACTION_RELEASE,
        'status' => DeviceCommand::STATUS_SENT,
        'command_text' => 'setigndigout ?0 ? 0',
        'desired_outputs' => ['1' => null, '2' => 0],
        'reason' => 'Release requested.',
        'claim_token' => (string) Str::uuid(),
        'claimed_at' => now(),
        'sent_at' => now(),
        'attempts' => 1,
        'expires_at' => now()->addMinutes(10),
    ]);
    $attempt = $command->commandAttempts()->create([
        'attempt_number' => 1,
        'status' => DeviceCommand::STATUS_SENT,
        'started_at' => now(),
    ]);

    app(UpdateDeviceCommandAction::class)->execute(
        $command->claim_token,
        'acknowledged',
        'DOUT2:0 Timeout:INFINITY',
        null,
    );

    expect($command->refresh()->status)->toBe(DeviceCommand::STATUS_CONFIRMED)
        ->and($command->confirmed_at)->not->toBeNull()
        ->and($attempt->refresh()->status)->toBe(DeviceCommand::STATUS_CONFIRMED)
        ->and($attempt->finished_at)->not->toBeNull();
});

test('a queued tracker response remains acknowledged until later telemetry confirms it', function () {
    $superadmin = User::factory()->superadmin()->create();
    $device = engineControlDevice();
    $command = $device->deviceCommands()->create([
        'uuid' => (string) Str::uuid(),
        'vehicle_id' => $device->vehicle_id,
        'fleet_id' => $device->fleet_id,
        'requested_by' => $superadmin->id,
        'imei' => $device->imei,
        'action' => DeviceCommand::ACTION_IMMOBILIZE,
        'status' => DeviceCommand::STATUS_SENT,
        'command_text' => 'setigndigout ?1 ? 0',
        'desired_outputs' => ['1' => null, '2' => 1],
        'reason' => 'Immobilization requested.',
        'claim_token' => (string) Str::uuid(),
        'claimed_at' => now(),
        'sent_at' => now(),
        'attempts' => 1,
        'expires_at' => now()->addMinutes(10),
    ]);

    app(UpdateDeviceCommandAction::class)->execute(
        $command->claim_token,
        'acknowledged',
        'DOUT2:1 Timeout:INFINITY IGN ON, QUEUED',
        null,
    );

    expect($command->refresh()->status)->toBe(DeviceCommand::STATUS_ACKNOWLEDGED)
        ->and($command->confirmed_at)->toBeNull();
});

test('output telemetry cannot confirm a command unless it is newer than the send time', function () {
    Carbon::setTestNow('2026-09-05 12:00:00');
    $superadmin = User::factory()->superadmin()->create();
    $device = engineControlDevice();
    Position::factory()->forDevice($device)->create([
        'gps_time' => now(),
        'server_time' => now(),
        'raw_data' => ['payload' => ['io' => ['179' => 1, '180' => 1]]],
    ]);
    $command = $device->deviceCommands()->create([
        'uuid' => (string) Str::uuid(),
        'vehicle_id' => $device->vehicle_id,
        'fleet_id' => $device->fleet_id,
        'requested_by' => $superadmin->id,
        'imei' => $device->imei,
        'action' => DeviceCommand::ACTION_IMMOBILIZE,
        'status' => DeviceCommand::STATUS_ACKNOWLEDGED,
        'command_text' => 'setigndigout 1? 0 ?',
        'desired_outputs' => ['1' => 1, '2' => null],
        'reason' => 'Immobilization requested.',
        'sent_at' => now(),
        'acknowledged_at' => now(),
        'expires_at' => now()->addMinutes(10),
    ]);

    app(ConfirmDeviceCommandsAction::class)->execute($device);
    expect($command->refresh()->status)->toBe(DeviceCommand::STATUS_ACKNOWLEDGED);

    Position::factory()->forDevice($device)->create([
        'gps_time' => now()->addSecond(),
        'server_time' => now()->addSecond(),
        'raw_data' => ['payload' => ['io' => ['179' => 1, '180' => 1]]],
    ]);
    app(ConfirmDeviceCommandsAction::class)->execute($device);

    expect($command->refresh()->status)->toBe(DeviceCommand::STATUS_CONFIRMED);
});

test('an expired command is no longer considered active', function () {
    $superadmin = User::factory()->superadmin()->create();
    $device = engineControlDevice();
    $command = $device->deviceCommands()->create([
        'uuid' => (string) Str::uuid(),
        'vehicle_id' => $device->vehicle_id,
        'fleet_id' => $device->fleet_id,
        'requested_by' => $superadmin->id,
        'imei' => $device->imei,
        'action' => DeviceCommand::ACTION_RELEASE,
        'status' => DeviceCommand::STATUS_ACKNOWLEDGED,
        'command_text' => 'setigndigout 0? 0 ?',
        'desired_outputs' => ['1' => 0, '2' => null],
        'reason' => 'Release requested.',
        'expires_at' => now()->subSecond(),
    ]);

    expect($command->isActive())->toBeFalse();
});
