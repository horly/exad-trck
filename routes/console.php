<?php

use App\Models\Alert;
use App\Models\Device;
use App\Models\Position;
use App\Models\Vehicle;
use App\Services\AlertService;
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

    $position = Position::query()->create([
        'device_id' => $device->id,
        'imei' => $device->imei,
        'gps_time' => $gpsTime,
        'server_time' => $serverTime,
        'latitude' => $validated['lat'],
        'longitude' => $validated['lng'],
        'is_valid' => true,
        'speed' => $speed,
        'angle' => $angle,
        'altitude' => $validated['altitude'] ?? null,
        'satellites' => $validated['satellites'] ?? null,
        'ignition' => $validated['ignition'] ?? null,
        'movement' => $validated['movement'] ?? ($speed > 0),
        'raw_data' => [
            'source' => 'gps-listener-server-local',
            'payload' => $data,
        ],
    ]);

    $device->forceFill([
        'status' => 'online',
        'last_seen_at' => $serverTime,
        'last_position_at' => $gpsTime,
        'last_latitude' => $validated['lat'],
        'last_longitude' => $validated['lng'],
        'last_speed' => $speed,
        'last_angle' => $angle,
    ])->save();

    if ($previousStatus !== 'online') {
        app(AlertService::class)->createSignalRecoveredAlert($device, $position, $previousStatus);
    }

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
