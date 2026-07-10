<?php

use App\Models\Alert;
use App\Models\Device;
use App\Models\Position;
use App\Models\Vehicle;
use App\Services\AlertService;
use App\Services\ReverseGeocodingService;
use App\Services\TrackerEventService;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('gps:ingest-position {--payload= : JSON payload sent by the local GPS listener}', function (): int {
    $payload = $this->option('payload') ?: trim(stream_get_contents(STDIN));

    if ($payload === '') {
        $this->error(json_encode([
            'ok' => false,
            'message' => 'Missing GPS payload.',
        ]));

        return 1;
    }

    $data = json_decode($payload, true);

    if (! is_array($data)) {
        $this->error(json_encode([
            'ok' => false,
            'message' => 'Invalid JSON payload.',
        ]));

        return 1;
    }

    $validator = Validator::make($data, [
        'imei' => ['required', 'string', 'max:20'],
        'lat' => ['required', 'numeric', 'between:-90,90'],
        'lng' => ['required', 'numeric', 'between:-180,180'],
        'speed' => ['nullable', 'integer', 'min:0', 'max:300'],
        'angle' => ['nullable', 'integer', 'min:0', 'max:359'],
        'altitude' => ['nullable', 'integer', 'min:-500', 'max:10000'],
        'satellites' => ['nullable', 'integer', 'min:0', 'max:99'],
        'gsm_signal' => ['nullable', 'integer', 'min:0', 'max:100'],
        'battery_level' => ['nullable', 'integer', 'min:0', 'max:100'],
        'external_voltage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        'battery_voltage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        'codec' => ['nullable', 'string', 'max:50'],
        'odometer' => ['nullable', 'numeric', 'min:0'],
        'engine_seconds' => ['nullable', 'integer', 'min:0'],
        'engine_hours' => ['nullable', 'numeric', 'min:0'],
        'sensors' => ['nullable', 'array'],
        'io' => ['nullable', 'array'],
        'raw' => ['nullable', 'array'],
        'obd' => ['nullable', 'array'],
        'can' => ['nullable', 'array'],
        'obd.rpm' => ['nullable', 'integer', 'min:0', 'max:20000'],
        'obd.speed' => ['nullable', 'integer', 'min:0', 'max:300'],
        'obd.throttle_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        'obd.engine_temperature_c' => ['nullable', 'numeric', 'min:-50', 'max:250'],
        'obd.module_voltage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        'obd.engine_load_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        'obd.fault_distance_km' => ['nullable', 'integer', 'min:0'],
        'obd.errors_count' => ['nullable', 'integer', 'min:0', 'max:65535'],
        'obd.distance_since_clear_km' => ['nullable', 'integer', 'min:0'],
        'can.fuel_level_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        'can.total_mileage_km' => ['nullable', 'numeric', 'min:0'],
        'address' => ['nullable', 'string', 'max:255'],
        'ignition' => ['nullable', 'boolean'],
        'movement' => ['nullable', 'boolean'],
        'gps_time' => ['nullable', 'date'],
    ]);

    if ($validator->fails()) {
        $this->error(json_encode([
            'ok' => false,
            'message' => 'Invalid GPS payload.',
            'errors' => $validator->errors()->toArray(),
        ]));

        return 1;
    }

    $validated = $validator->validated();
    $device = Device::query()->where('imei', $validated['imei'])->first();

    if (! $device) {
        $this->error(json_encode([
            'ok' => false,
            'message' => 'Unknown IMEI.',
            'imei' => $validated['imei'],
        ]));

        return 2;
    }

    $serverTime = now();
    $gpsTime = isset($validated['gps_time']) ? Carbon::parse($validated['gps_time']) : $serverTime;
    $speed = (int) ($validated['speed'] ?? 0);
    $angle = (int) ($validated['angle'] ?? 0);
    $previousStatus = (string) $device->status;
    $previousMovement = $device->last_movement;
    $previousIgnition = $device->last_ignition;
    $movement = (bool) ($validated['movement'] ?? ($speed > 0));
    $gsmSignal = $device->last_gsm_signal;
    $engineSeconds = $validated['engine_seconds']
        ?? (isset($validated['engine_hours']) ? (int) round((float) $validated['engine_hours'] * 3600) : null);
    $odometerKm = isset($validated['odometer']) ? round((float) $validated['odometer'], 2) : null;
    $obd = $validated['obd'] ?? [];
    $can = $validated['can'] ?? [];
    $address = $validated['address'] ?? null;
    $rawTelemetry = [
        'source' => $data['source'] ?? 'gps-listener-server-local',
        'payload' => $data,
    ];

    if (array_key_exists('gsm_signal', $validated)) {
        $rawGsmSignal = max(0, (int) $validated['gsm_signal']);
        $gsmSignal = min(100, $rawGsmSignal <= 5 ? $rawGsmSignal * 20 : $rawGsmSignal);
    }

    if ($address === null) {
        $address = app(ReverseGeocodingService::class)->resolveBest(
            (float) $validated['lat'],
            (float) $validated['lng'],
            $device->last_address,
        );
    }

    $position = Position::query()->create([
        'device_id' => $device->id,
        'imei' => $device->imei,
        'gps_time' => $gpsTime,
        'server_time' => $serverTime,
        'latitude' => $validated['lat'],
        'longitude' => $validated['lng'],
        'address' => $address,
        'is_valid' => true,
        'speed' => $speed,
        'angle' => $angle,
        'altitude' => $validated['altitude'] ?? null,
        'satellites' => $validated['satellites'] ?? null,
        'ignition' => $validated['ignition'] ?? null,
        'movement' => $movement,
        'external_voltage' => $validated['external_voltage'] ?? null,
        'battery_voltage' => $validated['battery_voltage'] ?? null,
        'odometer' => $odometerKm,
        'raw_data' => $rawTelemetry,
    ]);

    $device->forceFill([
        'status' => 'online',
        'last_seen_at' => $serverTime,
        'last_position_at' => $gpsTime,
        'last_latitude' => $validated['lat'],
        'last_longitude' => $validated['lng'],
        'last_speed' => $speed,
        'last_angle' => $angle,
        'last_ignition' => $validated['ignition'] ?? $previousIgnition,
        'last_movement' => $movement,
        'last_satellites' => $validated['satellites'] ?? $device->last_satellites,
        'last_gsm_signal' => $gsmSignal,
        'last_battery_level' => $validated['battery_level'] ?? $device->last_battery_level,
        'last_external_voltage' => $validated['external_voltage'] ?? $device->last_external_voltage,
        'last_battery_voltage' => $validated['battery_voltage'] ?? $device->last_battery_voltage,
        'last_odometer_km' => $odometerKm ?? $device->last_odometer_km,
        'last_engine_seconds' => $engineSeconds ?? $device->last_engine_seconds,
        'last_obd_rpm' => $obd['rpm'] ?? $device->last_obd_rpm,
        'last_obd_speed' => $obd['speed'] ?? $device->last_obd_speed,
        'last_obd_throttle_percent' => $obd['throttle_percent'] ?? $device->last_obd_throttle_percent,
        'last_obd_engine_temperature_c' => $obd['engine_temperature_c'] ?? $device->last_obd_engine_temperature_c,
        'last_obd_module_voltage' => $obd['module_voltage'] ?? $device->last_obd_module_voltage,
        'last_obd_engine_load_percent' => $obd['engine_load_percent'] ?? $device->last_obd_engine_load_percent,
        'last_obd_fault_distance_km' => $obd['fault_distance_km'] ?? $device->last_obd_fault_distance_km,
        'last_obd_errors_count' => $obd['errors_count'] ?? $device->last_obd_errors_count,
        'last_obd_distance_since_clear_km' => $obd['distance_since_clear_km'] ?? $device->last_obd_distance_since_clear_km,
        'last_can_fuel_level_percent' => $can['fuel_level_percent'] ?? $device->last_can_fuel_level_percent,
        'last_can_total_mileage_km' => $can['total_mileage_km'] ?? $device->last_can_total_mileage_km,
        'last_obd_updated_at' => ($obd !== [] || $can !== []) ? $serverTime : $device->last_obd_updated_at,
        'last_diagnostic_updated_at' => (array_key_exists('satellites', $validated) || array_key_exists('io', $validated) || array_key_exists('sensors', $validated))
            ? $serverTime
            : $device->last_diagnostic_updated_at,
        'last_sensors' => $validated['sensors'] ?? $device->last_sensors,
        'last_io' => $validated['io'] ?? $device->last_io,
        'last_raw_payload' => $validated['raw'] ?? $rawTelemetry,
        'last_address' => $address ?? $device->last_address,
        'codec' => $validated['codec'] ?? $device->codec,
    ])->save();

    if ($previousStatus !== 'online') {
        app(AlertService::class)->createSignalRecoveredAlert($device, $position, $previousStatus);
    }

    app(TrackerEventService::class)->recordPosition(
        $device,
        $position,
        $previousMovement,
        $previousIgnition,
    );

    $this->line(json_encode([
        'ok' => true,
        'device_id' => $device->id,
        'position_id' => $position->id,
        'status' => $device->status,
        'imei' => $device->imei,
    ]));

    return 0;
})->purpose('Ingest a simulated GPS position for a registered tracker IMEI');

Artisan::command('gps:mark-stale {--minutes=5 : Minutes without signal before a tracker becomes offline}', function (): int {
    $minutes = max(1, (int) $this->option('minutes'));
    $threshold = now()->subMinutes($minutes);
    $alertService = app(AlertService::class);

    $devices = Device::query()
        ->where('status', 'online')
        ->whereNotNull('last_seen_at')
        ->where('last_seen_at', '<', $threshold)
        ->get();

    $devices->each(function (Device $device) use ($alertService): void {
        $device->forceFill(['status' => 'offline'])->save();

        $alreadyAlerted = Alert::query()
            ->where('device_id', $device->id)
            ->where('type', 'no_signal')
            ->where('occurred_at', '>=', now()->subMinutes(30))
            ->exists();

        if (! $alreadyAlerted) {
            $alertService->createNoSignalAlert($device);
        }
    });

    $this->line(json_encode([
        'ok' => true,
        'updated' => $devices->count(),
        'threshold' => $threshold->toISOString(),
    ]));

    return 0;
})->purpose('Mark online trackers as offline when their last signal is stale');

Artisan::command('alerts:demo {vehicle_id? : Optional vehicle ID used as alert context}', function (): int {
    $vehicleId = $this->argument('vehicle_id') ? (int) $this->argument('vehicle_id') : null;

    $vehicle = $vehicleId
        ? Vehicle::query()->with(['fleet:id,name,code', 'device:id,vehicle_id,fleet_id,name,imei,last_latitude,last_longitude'])->find($vehicleId)
        : Vehicle::query()->with(['fleet:id,name,code', 'device:id,vehicle_id,fleet_id,name,imei,last_latitude,last_longitude'])->latest()->first();

    $alert = app(AlertService::class)->demo($vehicle);

    $this->line(json_encode([
        'ok' => true,
        'alert_id' => $alert->id,
        'type' => $alert->type,
        'severity' => $alert->severity,
    ]));

    return 0;
})->purpose('Create and broadcast a demo alert to the superadmin console');
