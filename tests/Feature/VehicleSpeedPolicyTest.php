<?php

use App\Models\Alert;
use App\Models\AlertRule;
use App\Models\AlertRuleState;
use App\Models\Device;
use App\Models\Fleet;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('vehicle form creates updates and removes its speed policy', function () {
    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create();
    $vehicleData = [
        'fleet_id' => $fleet->id,
        'name' => 'Toyota Hilux Police',
        'registration_number' => 'EX-8080',
        'vehicle_type' => 'pickup',
        'status' => 'active',
        'speed_limit_kmh' => 80,
    ];

    $this->actingAs($superadmin)
        ->post(route('vehicles.store'), $vehicleData)
        ->assertRedirect(route('vehicles.index'));

    $vehicle = Vehicle::query()->where('registration_number', 'EX-8080')->firstOrFail();
    $policy = $vehicle->speedPolicy()->firstOrFail();

    expect($policy->type)->toBe('overspeed')
        ->and($policy->scope_type)->toBe(AlertRule::SCOPE_VEHICLE)
        ->and($policy->vehicle_id)->toBe($vehicle->id)
        ->and((int) $policy->threshold_value)->toBe(80)
        ->and($policy->is_active)->toBeTrue();

    $this->actingAs($superadmin)
        ->get(route('vehicles.index'))
        ->assertSuccessful()
        ->assertSee('Politique de vitesse')
        ->assertSee('data-speed-limit-kmh="80"', false);

    $this->actingAs($superadmin)
        ->put(route('vehicles.update', $vehicle), array_merge($vehicleData, ['speed_limit_kmh' => 70]))
        ->assertRedirect(route('vehicles.index'));

    expect((int) $policy->refresh()->threshold_value)->toBe(70);

    $this->actingAs($superadmin)
        ->put(route('vehicles.update', $vehicle), array_merge($vehicleData, ['speed_limit_kmh' => null]))
        ->assertRedirect(route('vehicles.index'));

    expect($vehicle->refresh()->speed_policy_rule_id)->toBeNull();
    $this->assertModelMissing($policy);
});

test('moving a vehicle to another fleet synchronizes its tracker fleet', function () {
    $superadmin = User::factory()->superadmin()->create();
    $originalFleet = Fleet::factory()->create();
    $newFleet = Fleet::factory()->create();
    $vehicle = Vehicle::factory()->for($originalFleet)->create([
        'name' => 'Vehicule a transferer',
        'registration_number' => 'TR-2026',
    ]);
    $device = Device::factory()->create([
        'fleet_id' => $originalFleet->id,
        'vehicle_id' => $vehicle->id,
    ]);

    $this->actingAs($superadmin)
        ->put(route('vehicles.update', $vehicle), [
            'fleet_id' => $newFleet->id,
            'name' => $vehicle->name,
            'registration_number' => $vehicle->registration_number,
            'vehicle_type' => $vehicle->vehicle_type,
            'status' => $vehicle->status,
        ])
        ->assertRedirect(route('vehicles.index'));

    expect($vehicle->refresh()->fleet_id)->toBe($newFleet->id)
        ->and($device->refresh()->fleet_id)->toBe($newFleet->id);
});

test('gps ingestion alerts immediately once per overspeed episode and rearms at the limit', function () {
    $fleet = Fleet::factory()->create();
    $vehicle = Vehicle::factory()->create(['fleet_id' => $fleet->id]);
    $device = Device::factory()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'imei' => '356307042449999',
        'status' => 'online',
    ]);
    $policy = AlertRule::query()->create([
        'vehicle_id' => $vehicle->id,
        'name' => 'Limite de vitesse - '.$vehicle->name,
        'type' => 'overspeed',
        'category' => AlertRule::CATEGORY_VEHICLE,
        'severity' => 'high',
        'scope_type' => AlertRule::SCOPE_VEHICLE,
        'threshold_value' => 80,
        'threshold_unit' => 'km/h',
        'channels' => ['platform'],
        'schedule_days' => [],
        'is_active' => true,
    ]);
    $vehicle->forceFill(['speed_policy_rule_id' => $policy->id])->save();

    expect($device->fresh()->vehicle?->speedPolicy?->id)->toBe($policy->id)
        ->and((int) $device->fresh()->vehicle?->speedPolicy?->threshold_value)->toBe(80);

    $ingest = function (int $speed, string $gpsTime) use ($device): int {
        return Artisan::call('gps:ingest-position', [
            '--payload' => json_encode([
                'imei' => $device->imei,
                'lat' => -4.325,
                'lng' => 15.312,
                'speed' => $speed,
                'address' => 'Boulevard du 30 Juin, Kinshasa',
                'gps_time' => $gpsTime,
            ]),
        ]);
    };

    expect($ingest(81, now()->subSeconds(30)->toIso8601String()))->toBe(0)
        ->and((int) $device->positions()->latest('id')->firstOrFail()->speed)->toBe(81)
        ->and(AlertRuleState::query()->count())->toBe(1)
        ->and(Alert::query()->where('type', 'overspeed')->count())->toBe(1);

    $alert = Alert::query()->where('type', 'overspeed')->firstOrFail();
    expect((int) $alert->speed)->toBe(81)
        ->and($alert->metadata['speed_limit'])->toBe(80);

    expect($ingest(95, now()->subSeconds(20)->toIso8601String()))->toBe(0)
        ->and(Alert::query()->where('type', 'overspeed')->count())->toBe(1);

    expect($ingest(80, now()->subSeconds(10)->toIso8601String()))->toBe(0)
        ->and(AlertRuleState::query()->firstOrFail()->is_triggered)->toBeFalse();

    expect($ingest(81, now()->toIso8601String()))->toBe(0)
        ->and(Alert::query()->where('type', 'overspeed')->count())->toBe(2);
});
