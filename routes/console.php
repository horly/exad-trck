<?php

use App\Models\Alert;
use App\Models\Device;
use App\Models\Position;
use App\Models\Vehicle;
use App\Services\AlertService;
use App\Services\ReverseGeocodingService;
use App\Services\TrackerEventService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;
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
        'obd.runtime_seconds' => ['nullable', 'integer', 'min:0'],
        'obd.rpm' => ['nullable', 'integer', 'min:0', 'max:20000'],
        'obd.speed' => ['nullable', 'integer', 'min:0', 'max:300'],
        'obd.throttle_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        'obd.engine_temperature_c' => ['nullable', 'numeric', 'min:-50', 'max:250'],
        'obd.module_voltage' => ['nullable', 'numeric', 'min:0', 'max:20000'],
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
    $metricSources = array_filter([
        $validated,
        $validated['io'] ?? null,
        $validated['sensors'] ?? null,
        $validated['raw'] ?? null,
        data_get($validated, 'raw.payload'),
        data_get($validated, 'raw.payload.io'),
        data_get($validated, 'raw.payload.sensors'),
        data_get($validated, 'raw.payload.obd'),
        data_get($validated, 'raw.payload.can'),
        $data,
        data_get($data, 'payload'),
        data_get($data, 'payload.io'),
        data_get($data, 'payload.sensors'),
        data_get($data, 'payload.obd'),
        data_get($data, 'payload.can'),
        data_get($data, 'io'),
        data_get($data, 'sensors'),
        data_get($data, 'obd'),
        data_get($data, 'can'),
    ], 'is_array');
    $normalizeMetricKey = static fn ($key): string => strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', (string) $key));
    $metricValue = static function (array $keys) use ($metricSources, $normalizeMetricKey) {
        foreach ($keys as $key) {
            $normalizedKey = $normalizeMetricKey($key);

            foreach ($metricSources as $source) {
                if (array_key_exists($key, $source) && $source[$key] !== null && $source[$key] !== '') {
                    return $source[$key];
                }

                if (is_numeric($key)) {
                    foreach ([(string) $key, (int) $key] as $numericKey) {
                        if (array_key_exists($numericKey, $source) && $source[$numericKey] !== null && $source[$numericKey] !== '') {
                            return $source[$numericKey];
                        }
                    }
                }

                $value = data_get($source, (string) $key);

                if ($value !== null && $value !== '') {
                    return $value;
                }

                foreach (Arr::dot($source) as $dotKey => $dotValue) {
                    if ($dotValue === null || $dotValue === '') {
                        continue;
                    }

                    $segments = explode('.', (string) $dotKey);
                    $lastSegment = end($segments);

                    if ((string) $dotKey === (string) $key
                        || $normalizeMetricKey($dotKey) === $normalizedKey
                        || $normalizeMetricKey($lastSegment) === $normalizedKey) {
                        return $dotValue;
                    }
                }
            }
        }

        return null;
    };
    $metricNumber = static function (array $keys) use ($metricValue): ?float {
        $value = $metricValue($keys);

        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = trim(str_replace(',', '.', $value));
            $value = preg_replace('/(?<=\d)\s+(?=\d)/', '', $value);

            if (! is_numeric($value) && preg_match('/-?\d+(?:\.\d+)?/', $value, $matches)) {
                $value = $matches[0];
            }
        }

        return is_numeric($value) ? (float) $value : null;
    };
    $percentMetric = static fn ($value) => $value !== null && is_numeric($value) && (float) $value <= 1
        ? round((float) $value * 100, 2)
        : $value;
    $voltageMetric = static fn ($value) => $value !== null && is_numeric($value) && (float) $value > 100
        ? round((float) $value / 1000, 3)
        : $value;
    $compactMetrics = static fn (array $metrics): array => array_filter(
        $metrics,
        static fn ($value): bool => $value !== null && $value !== '',
    );

    $runtimeSeconds = $metricNumber([
        'runtime_seconds',
        'engine_runtime_seconds',
        'execution_time_seconds',
        'execution_moment_seconds',
        'moment_execution_seconds',
        'obd.runtime_seconds',
        'payload.obd.runtime_seconds',
        'time_since_engine_start',
        'engine_seconds',
        'io.42',
        '42',
    ]);
    $engineSeconds = null;
    $engineHours = $validated['engine_hours'] ?? $metricNumber(['engine_hours', 'engine_hours_total', 'engine_time_hours', 'motor_hours', 'obd.engine_hours', 'can.engine_hours', 'io.103', '103']);

    if ($engineSeconds === null && $engineHours !== null) {
        $engineSeconds = (int) round((float) $engineHours * 3600);
    }

    $odometerRaw = $validated['odometer'] ?? $metricNumber(['odometer', 'odometer_km', 'total_odometer', 'mileage', 'total_mileage', 'can.total_mileage_km', 'io.199', '199']);
    $odometerKm = $odometerRaw !== null ? round((float) $odometerRaw, 2) : null;
    $obd = array_replace(
        $compactMetrics([
            'runtime_seconds' => $runtimeSeconds,
            'rpm' => $metricNumber(['rpm', 'engine_rpm', 'tr_min', 'tr/min', 'obd.rpm', 'payload.obd.rpm', 'can.rpm', 'engine.rpm', 'obd_tr_min', 'io.36', '36', 'io.85', '85']),
            'speed' => $metricNumber(['obd_speed', 'obd.speed', 'payload.obd.speed', 'can.speed', 'vehicle.speed', 'speed', 'io.37', '37', 'io.24', '24']),
            'throttle_percent' => $percentMetric($metricNumber(['throttle', 'throttle_percent', 'papillon', 'obd.throttle_percent', 'payload.obd.throttle_percent', 'can.throttle', 'io.41', '41'])),
            'engine_temperature_c' => $metricNumber(['engine_temperature', 'engine_temperature_c', 'coolant_temperature', 'temperature_moteur', 'obd.engine_temperature_c', 'payload.obd.engine_temperature_c', 'obd.coolant_temperature', 'can.engine_temperature', 'temperature.engine', 'io.32', '32']),
            'module_voltage' => $voltageMetric($metricNumber(['module_voltage', 'control_module_voltage', 'tension_commande_module', 'obd.module_voltage', 'payload.obd.module_voltage', 'can.module_voltage', 'io.51', '51', 'io.66', '66'])),
            'engine_load_percent' => $percentMetric($metricNumber(['engine_load', 'engine_load_percent', 'absolute_load', 'absolute_load_value', 'valeur_absolue_de_charge', 'obd.engine_load_percent', 'payload.obd.engine_load_percent', 'can.engine_load', 'io.52', '52', 'io.31', '31'])),
            'fault_distance_km' => $metricNumber(['fault_distance', 'fault_distance_km', 'distance_with_fault', 'distance_with_mil', 'distance_avec_defaut_moteur', 'obd.fault_distance_km', 'payload.obd.fault_distance_km', 'io.43', '43']),
            'errors_count' => $metricNumber(['errors', 'errors_count', 'dtc_count', 'obd.errors_count', 'payload.obd.errors_count', 'io.30', '30']),
            'distance_since_clear_km' => $metricNumber(['distance_since_clear', 'distance_since_clear_km', 'distance_since_codes_cleared', 'mileage_since_reset', 'kilometrage_depuis_reinitialisation', 'obd.distance_since_clear_km', 'payload.obd.distance_since_clear_km', 'io.49', '49', 'io.55', '55']),
        ]),
        $compactMetrics($validated['obd'] ?? []),
    );
    $can = array_replace(
        $compactMetrics([
            'fuel_level_percent' => $percentMetric($metricNumber(['fuel', 'fuel_level', 'fuel_level_percent', 'carburant', 'obd.fuel', 'obd.fuel_level', 'payload.can.fuel_level_percent', 'can.fuel', 'can.fuel_level', 'fuel.level', 'io.48', '48'])),
            'total_mileage_km' => $metricNumber(['total_mileage', 'total_mileage_km', 'can.total_mileage_km', 'payload.can.total_mileage_km', 'io.16', '16']),
        ]),
        $compactMetrics($validated['can'] ?? []),
    );

    if (isset($obd['module_voltage'])) {
        $obd['module_voltage'] = $voltageMetric($obd['module_voltage']);
    }

    foreach (['throttle_percent', 'engine_load_percent'] as $percentKey) {
        if (isset($obd[$percentKey])) {
            $obd[$percentKey] = $percentMetric($obd[$percentKey]);
        }
    }

    if (isset($can['fuel_level_percent'])) {
        $can['fuel_level_percent'] = $percentMetric($can['fuel_level_percent']);
    }

    $storedRuntimeSeconds = isset($obd['runtime_seconds']) ? (int) $obd['runtime_seconds'] : null;
    $shouldClearRuntimeAsEngineHours = $engineSeconds === null
        && $storedRuntimeSeconds !== null
        && $device->last_engine_seconds !== null
        && (int) $device->last_engine_seconds === $storedRuntimeSeconds;

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
        'last_engine_seconds' => $engineSeconds ?? ($shouldClearRuntimeAsEngineHours ? null : $device->last_engine_seconds),
        'last_obd_runtime_seconds' => $obd['runtime_seconds'] ?? $device->last_obd_runtime_seconds,
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
