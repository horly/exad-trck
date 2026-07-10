<?php

use App\Models\Alert;
use App\Models\Device;
use App\Models\Fleet;
use App\Models\Position;
use App\Models\ScheduledReport;
use App\Models\Subscription;
use App\Models\TrackerEvent;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('superadmin can browse generated reports with ajax datatable', function () {
    $superadmin = User::factory()->superadmin()->create();
    $subscription = Subscription::factory()->create();
    $fleet = Fleet::factory()->create([
        'subscription_id' => $subscription->id,
        'name' => 'EXAD CARS',
        'code' => 'EXAD1505',
    ]);
    $vehicle = Vehicle::factory()->create([
        'fleet_id' => $fleet->id,
        'name' => 'Toyota Land Cruiser',
        'registration_number' => '2058AG10',
    ]);
    $device = Device::factory()->online()->create([
        'subscription_id' => $subscription->id,
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'name' => 'FMB003',
        'imei' => '353201355304905',
    ]);

    Position::factory()->forDevice($device)->create([
        'address' => 'Avenue Du Kwango, Kinshasa',
        'server_time' => now(),
    ]);

    $this->actingAs($superadmin)
        ->get(route('reports.index'))
        ->assertSuccessful()
        ->assertSee(__('reports.title'))
        ->assertSee('Toyota Land Cruiser')
        ->assertSee('Avenue Du Kwango, Kinshasa');

    $response = $this->actingAs($superadmin)
        ->getJson(route('reports.index', [
            'type' => 'positions',
            'period' => 'week',
            'search' => 'Kwango',
        ]), ['X-Requested-With' => 'XMLHttpRequest']);

    $response
        ->assertSuccessful()
        ->assertJsonPath('html', fn (string $html): bool => str_contains($html, 'Avenue Du Kwango'));
});

test('superadmin can export alerts report and schedule recurring reports', function () {
    $superadmin = User::factory()->superadmin()->create();
    $fleet = Fleet::factory()->create(['name' => 'EXAD CARS']);
    $vehicle = Vehicle::factory()->create(['fleet_id' => $fleet->id, 'name' => 'Suzuki Horly']);
    $device = Device::factory()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'name' => 'FMB920',
        'imei' => '865456047193582',
    ]);

    Alert::query()->create([
        'fleet_id' => $fleet->id,
        'vehicle_id' => $vehicle->id,
        'device_id' => $device->id,
        'type' => 'no_signal',
        'severity' => 'high',
        'status' => 'new',
        'title' => 'No signal',
        'message' => 'Tracker FMB920 is no longer transmitting signal.',
        'occurred_at' => now(),
    ]);

    $this->actingAs($superadmin)
        ->get(route('reports.export', [
            'type' => 'alerts',
            'period' => 'week',
        ]))
        ->assertSuccessful()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $pdfResponse = $this->actingAs($superadmin)
        ->get(route('reports.export', [
            'type' => 'alerts',
            'period' => 'week',
            'format' => 'print',
        ]));

        $pdfResponse->assertSuccessful();
        expect($pdfResponse->headers->get('content-type'))->toContain('application/pdf');
        expect($pdfResponse->headers->get('content-disposition'))->toContain('attachment');

    $this->actingAs($superadmin)
        ->post(route('reports.schedules.store'), [
            'name' => 'Rapport alertes hebdomadaire',
            'type' => 'alerts',
            'frequency' => 'weekly',
            'format' => 'csv',
            'period' => 'week',
            'recipients' => 'ops@example.com, admin@example.com',
            'fleet_id' => $fleet->id,
        ])
        ->assertRedirect(route('reports.index', ['type' => 'alerts', 'period' => 'week']));

    $this->assertDatabaseHas('scheduled_reports', [
        'user_id' => $superadmin->id,
        'name' => 'Rapport alertes hebdomadaire',
        'type' => 'alerts',
        'frequency' => 'weekly',
        'format' => 'csv',
        'is_active' => true,
    ]);

    expect(ScheduledReport::query()->first()?->recipients)->toBe(['ops@example.com', 'admin@example.com']);
});

test('report pages remain reserved to superadmin users', function () {
    $subscription = Subscription::factory()->create();
    $admin = User::factory()->admin($subscription)->create();

    $this->actingAs($admin)
        ->get(route('reports.index'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('reports.export'))
        ->assertForbidden();
});

test('scheduled reports can only be deleted by their owner', function () {
    $superadmin = User::factory()->superadmin()->create();
    $otherSuperadmin = User::factory()->superadmin()->create();
    $scheduledReport = ScheduledReport::query()->create([
        'user_id' => $otherSuperadmin->id,
        'name' => 'Rapport protege',
        'type' => 'positions',
        'frequency' => 'daily',
        'format' => 'csv',
        'filters' => ['period' => 'week'],
        'recipients' => [],
        'is_active' => true,
        'next_run_at' => now()->addDay(),
    ]);

    $this->actingAs($superadmin)
        ->delete(route('reports.schedules.destroy', $scheduledReport))
        ->assertForbidden();

    $this->assertDatabaseHas('scheduled_reports', [
        'id' => $scheduledReport->id,
    ]);
});
