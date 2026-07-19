<?php

use App\Models\Alert;
use App\Models\Device;
use App\Models\Fleet;
use App\Models\Garage;
use App\Models\MaintenancePlan;
use App\Models\MaintenanceRecord;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\MaintenanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('superadmin can create a garage without assigning fleets', function () {
    $user = User::factory()->superadmin()->create();

    $this->actingAs($user)->post(route('garages.store'), [
        'name' => 'Atelier central EXAD',
        'type' => 'internal',
        'responsible_name' => 'Jean Ilunga',
        'address' => 'Gombe, Kinshasa',
        'latitude' => -4.31,
        'longitude' => 15.29,
        'specialties' => 'Mécanique, pneus, Mécanique',
        'status' => 'active',
    ])->assertRedirect(route('garages.index'));

    $garage = Garage::query()->firstOrFail();
    expect($garage->specialties)->toBe(['Mécanique', 'pneus']);
});

test('garage and maintenance pages are restricted and exposed in the fleet menu', function () {
    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('garages.index'))->assertForbidden();
    $this->actingAs($admin)->get(route('maintenance.index'))->assertForbidden();
    $this->actingAs($superadmin)->get(route('garages.index'))
        ->assertSuccessful()
        ->assertSee(route('maintenance.index'), false)
        ->assertSee('garage-modal-dialog', false)
        ->assertSee('fa-screwdriver-wrench', false);

    $this->actingAs($superadmin)->get(route('maintenance.index'))
        ->assertSuccessful()
        ->assertSee('users-modal-dialog maintenance-modal-dialog', false)
        ->assertSee('fa-clipboard-check', false)
        ->assertSee('form-modal-heading-icon', false)
        ->assertSee('maintenance-stat maintenance-stat-active', false)
        ->assertSee('maintenance-stat maintenance-stat-due', false)
        ->assertSee('maintenance-stat maintenance-stat-scheduled_cost', false)
        ->assertSee('maintenance-stat maintenance-stat-actual_cost', false);
});

test('tracker and maintenance vehicle fields provide searchable selectors', function () {
    $superadmin = User::factory()->superadmin()->create();

    foreach ([route('trackers.index'), route('maintenance.index')] as $url) {
        $this->actingAs($superadmin)
            ->get($url)
            ->assertSuccessful()
            ->assertSee('data-searchable-select', false)
            ->assertSee('data-searchable-select-search', false)
            ->assertSee('js/searchable-select.js', false);
    }
});

test('a preventive plan stores conditions and documents', function () {
    Storage::fake('public');
    $user = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create();
    $vehicle = Vehicle::factory()->create(['fleet_id' => $fleet->id]);
    $garage = Garage::query()->create(['name' => 'Garage Gombe', 'type' => 'external', 'status' => 'active']);
    $this->actingAs($user)->post(route('maintenance.store'), [
        'vehicle_id' => $vehicle->id,
        'garage_id' => $garage->id,
        'name' => 'Vidange moteur',
        'maintenance_type' => 'preventive',
        'estimated_cost' => 150,
        'is_recurring' => '1',
        'next_due_odometer_km' => 25000,
        'reminder_odometer_km' => 500,
        'interval_odometer_km' => 5000,
        'documents' => [UploadedFile::fake()->create('devis.pdf', 120, 'application/pdf')],
    ])->assertRedirect(route('maintenance.index'));

    $plan = MaintenancePlan::query()->with('documents')->firstOrFail();
    expect($plan->name)->toBe('Vidange moteur')
        ->and($plan->is_recurring)->toBeTrue()
        ->and((float) $plan->next_due_odometer_km)->toBe(25000.0)
        ->and($plan->documents)->toHaveCount(1);
    Storage::disk('public')->assertExists($plan->documents->first()->path);
});

test('maintenance evaluator creates one alert when telemetry reaches a threshold', function () {
    $fleet = Fleet::factory()->create();
    $vehicle = Vehicle::factory()->create(['fleet_id' => $fleet->id]);
    Device::factory()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'last_odometer_km' => 19950,
    ]);
    $plan = MaintenancePlan::query()->create([
        'vehicle_id' => $vehicle->id,
        'name' => 'Révision 20 000 km',
        'maintenance_type' => 'preventive',
        'next_due_odometer_km' => 20000,
        'reminder_odometer_km' => 100,
        'status' => 'active',
        'due_status' => 'scheduled',
    ]);

    $service = app(MaintenanceService::class);
    expect($service->evaluate($plan))->toBe('due_soon');
    $service->evaluate($plan->refresh());

    expect(Alert::query()->where('type', 'maintenance_due')->count())->toBe(1)
        ->and($plan->refresh()->due_alert_sent_at)->not->toBeNull();
});

test('completing recurring maintenance preserves history and advances its threshold', function () {
    $user = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create();
    $vehicle = Vehicle::factory()->create(['fleet_id' => $fleet->id]);
    $plan = MaintenancePlan::query()->create([
        'vehicle_id' => $vehicle->id,
        'created_by' => $user->id,
        'name' => 'Vidange récurrente',
        'maintenance_type' => 'preventive',
        'is_recurring' => true,
        'next_due_odometer_km' => 10000,
        'interval_odometer_km' => 5000,
        'status' => 'active',
        'due_status' => 'due',
        'due_alert_sent_at' => now(),
    ]);

    $this->actingAs($user)->patch(route('maintenance.complete', $plan), [
        'performed_at' => '2026-07-19',
        'odometer_km' => 10200,
        'actual_cost' => 90,
        'notes' => 'Huile et filtre remplacés.',
    ])->assertRedirect(route('maintenance.index', ['tab' => 'history']));

    $record = MaintenanceRecord::query()->firstOrFail();
    expect((float) $record->odometer_km)->toBe(10200.0)
        ->and((float) $record->actual_cost)->toBe(90.0)
        ->and((float) $plan->refresh()->next_due_odometer_km)->toBe(15200.0)
        ->and($plan->due_alert_sent_at)->toBeNull()
        ->and($plan->status)->toBe('active');
});
