@php
    $vehicleLabel = $device->vehicle?->name ?: __('trackers.no_vehicle');
    $showTechnicalDetails = $showTechnicalDetails ?? true;
    $showDriverIdentifier = $showDriverIdentifier ?? $showTechnicalDetails;
    $registration = $device->vehicle?->registration_number;
    $fleetLabel = $device->vehicle?->fleet?->name ?: ($device->fleet?->name ?: __('trackers.no_fleet'));
    $updatedAt = $device->last_seen_at ?: $device->last_position_at;
    $modelLabel = trim(($device->brand ? __('trackers.brand_' . $device->brand) : '') . ' ' . (string) $device->model);
    $formatVoltage = fn ($value) => $value !== null ? rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.') : null;
    $parkingDuration = $parkingDuration ?? ($device->last_position_at ? $device->last_position_at->diffForHumans(null, true) : null);
    $locationUpdatedAt = $locationUpdatedAt ?? $updatedAt;
    $locationAddress = $locationAddress ?? ($latestPosition?->address ?: $device->last_address);
    $locationLatitude = $latestPosition?->latitude ?? $device->last_latitude;
    $locationLongitude = $latestPosition?->longitude ?? $device->last_longitude;
    $locationAltitude = $latestPosition?->altitude;
    $gsmSignal = $device->last_gsm_signal;
    $gsmSignal = $gsmSignal !== null && $gsmSignal <= 5 ? min(100, $gsmSignal * 20) : $gsmSignal;
    $engineSeconds = $device->last_engine_seconds;
    $engineLabel = null;
    $runtimeSeconds = $device->last_obd_runtime_seconds;
    $runtimeLabel = null;

    $formatDurationLabel = function (?int $seconds) {
        if ($seconds === null) {
            return null;
        }

        $seconds = max(0, $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return __('trackers.duration_hours_minutes_seconds_value', [
                'hours' => $hours,
                'minutes' => $minutes,
                'seconds' => $remainingSeconds,
            ]);
        }

        if ($minutes > 0) {
            return __('trackers.duration_minutes_seconds_value', [
                'minutes' => $minutes,
                'seconds' => $remainingSeconds,
            ]);
        }

        return __('trackers.duration_seconds_value', ['seconds' => $remainingSeconds]);
    };

    if ($engineSeconds !== null) {
        $engineLabel = $formatDurationLabel((int) $engineSeconds);
    }

    $sensorCount = is_array($device->last_sensors) ? count($device->last_sensors) : 0;
    $ioCount = is_array($device->last_io) ? count($device->last_io) : 0;
    $ioData = is_array($device->last_io) ? $device->last_io : [];
    $sensorData = is_array($device->last_sensors) ? $device->last_sensors : [];
    $payloadData = is_array($device->last_raw_payload) ? $device->last_raw_payload : [];
    $metricSources = array_filter([
        $ioData,
        $sensorData,
        $payloadData,
        data_get($payloadData, 'payload'),
        data_get($payloadData, 'payload.io'),
        data_get($payloadData, 'payload.sensors'),
        data_get($payloadData, 'payload.obd'),
        data_get($payloadData, 'payload.can'),
        data_get($payloadData, 'io'),
        data_get($payloadData, 'sensors'),
        data_get($payloadData, 'obd'),
        data_get($payloadData, 'can'),
        data_get($payloadData, 'data'),
    ], 'is_array');
    $normalizeMetricKey = fn ($key) => strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', (string) $key));
    $metricValue = function (array $keys) use ($metricSources, $normalizeMetricKey) {
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

                foreach (\Illuminate\Support\Arr::dot($source) as $dotKey => $dotValue) {
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
    $metricNumber = function ($value) {
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
    $metricFirst = fn ($deviceValue, array $keys) => $deviceValue !== null ? $deviceValue : $metricValue($keys);
    $metricFirstNumber = fn ($deviceValue, array $keys) => $metricNumber($metricFirst($deviceValue, $keys));
    $formatMetric = function ($value, int $decimals = 0) {
        if ($value === null || ! is_numeric($value)) {
            return null;
        }

        $formatted = number_format((float) $value, $decimals, ',', ' ');

        if (str_contains($formatted, ',')) {
            $formatted = rtrim(rtrim($formatted, '0'), ',');
        }

        return $formatted === '' ? '0' : $formatted;
    };

    if ($engineLabel === null) {
        $engineHoursFallback = $metricNumber($metricValue(['engine_hours', 'engine_hours_total', 'engine_time_hours', 'motor_hours', 'obd.engine_hours', 'can.engine_hours', 'io.103', '103']));

        if ($engineHoursFallback !== null) {
            $fallbackSeconds = (int) round((float) $engineHoursFallback * 3600);
            $engineLabel = $formatDurationLabel($fallbackSeconds);
        }
    }
    $formatPercentMetric = function ($value) use ($formatMetric) {
        if ($value !== null && is_numeric($value) && (float) $value <= 1) {
            $value = (float) $value * 100;
        }

        return $formatMetric($value);
    };
    $obdOdometer = $metricFirstNumber($device->last_odometer_km, ['odometer', 'odometer_km', 'total_odometer', 'mileage', 'total_mileage', 'can.total_mileage_km', 'io.199', '199']);
    $runtimeMetric = $metricFirstNumber($runtimeSeconds, ['runtime_seconds', 'engine_runtime_seconds', 'execution_time_seconds', 'execution_moment_seconds', 'moment_execution_seconds', 'obd.runtime_seconds', 'payload.obd.runtime_seconds', 'time_since_engine_start', 'engine_seconds', 'io.42', '42']);
    $runtimeSeconds = $runtimeMetric !== null ? (int) $runtimeMetric : null;
    $runtimeLabel = $formatDurationLabel($runtimeSeconds);
    $obdRpm = $metricFirstNumber($device->last_obd_rpm, ['rpm', 'engine_rpm', 'tr_min', 'tr/min', 'obd.rpm', 'payload.obd.rpm', 'can.rpm', 'engine.rpm', 'obd_tr_min', 'io.36', '36', 'io.85', '85']);
    $obdSpeed = $metricFirstNumber($device->last_obd_speed, ['obd_speed', 'obd.speed', 'payload.obd.speed', 'can.speed', 'vehicle.speed', 'speed', 'io.37', '37', 'io.24', '24']);
    $obdThrottle = $metricFirstNumber($device->last_obd_throttle_percent, ['throttle', 'throttle_percent', 'papillon', 'obd.throttle_percent', 'payload.obd.throttle_percent', 'can.throttle', 'io.41', '41']);
    $obdTemperature = $metricFirstNumber($device->last_obd_engine_temperature_c, ['engine_temperature', 'engine_temperature_c', 'coolant_temperature', 'temperature_moteur', 'obd.engine_temperature_c', 'payload.obd.engine_temperature_c', 'obd.coolant_temperature', 'can.engine_temperature', 'temperature.engine', 'io.32', '32']);
    $obdModuleVoltage = $metricFirstNumber($device->last_obd_module_voltage, ['module_voltage', 'control_module_voltage', 'tension_commande_module', 'obd.module_voltage', 'payload.obd.module_voltage', 'can.module_voltage', 'io.51', '51', 'io.66', '66']);
    $obdEngineLoad = $metricFirstNumber($device->last_obd_engine_load_percent, ['engine_load', 'engine_load_percent', 'absolute_load', 'absolute_load_value', 'valeur_absolue_de_charge', 'obd.engine_load_percent', 'payload.obd.engine_load_percent', 'can.engine_load', 'io.52', '52', 'io.31', '31']);
    $obdFaultDistance = $metricFirstNumber($device->last_obd_fault_distance_km, ['fault_distance', 'fault_distance_km', 'distance_with_fault', 'distance_with_mil', 'distance_avec_defaut_moteur', 'obd.fault_distance_km', 'payload.obd.fault_distance_km', 'io.43', '43']);
    $obdErrorsCount = $metricFirstNumber($device->last_obd_errors_count, ['errors', 'errors_count', 'dtc_count', 'obd.errors_count', 'payload.obd.errors_count', 'io.30', '30']);
    $obdDistanceSinceClear = $metricFirstNumber($device->last_obd_distance_since_clear_km, ['distance_since_clear', 'distance_since_clear_km', 'distance_since_codes_cleared', 'mileage_since_reset', 'kilometrage_depuis_reinitialisation', 'obd.distance_since_clear_km', 'payload.obd.distance_since_clear_km', 'io.49', '49', 'io.55', '55']);
    $obdFuel = $metricFirstNumber($device->last_can_fuel_level_percent, ['fuel', 'fuel_level', 'fuel_level_percent', 'carburant', 'obd.fuel', 'obd.fuel_level', 'payload.can.fuel_level_percent', 'can.fuel', 'can.fuel_level', 'fuel.level', 'io.48', '48']);
    if ($obdModuleVoltage !== null && is_numeric($obdModuleVoltage) && (float) $obdModuleVoltage > 100) {
        $obdModuleVoltage = (float) $obdModuleVoltage / 1000;
    }

    if ($obdFuel !== null && is_numeric($obdFuel) && (float) $obdFuel <= 1) {
        $obdFuel = (float) $obdFuel * 100;
    }

    $canBusStates = is_array($canBusStates ?? null) ? $canBusStates : [];
    $canStateDefinitions = [
        'rear_right_door_open' => ['label' => 'can_rear_right_door', 'icon' => 'fa-door-open', 'true' => 'can_state_open', 'false' => 'can_state_closed'],
        'rear_left_door_open' => ['label' => 'can_rear_left_door', 'icon' => 'fa-door-open', 'true' => 'can_state_open', 'false' => 'can_state_closed'],
        'front_right_door_open' => ['label' => 'can_front_right_door', 'icon' => 'fa-door-open', 'true' => 'can_state_open', 'false' => 'can_state_closed'],
        'front_left_door_open' => ['label' => 'can_front_left_door', 'icon' => 'fa-door-open', 'true' => 'can_state_open', 'false' => 'can_state_closed'],
        'roof_open' => ['label' => 'can_roof', 'icon' => 'fa-car-side', 'true' => 'can_state_open', 'false' => 'can_state_closed'],
        'webasto_on' => ['label' => 'can_webasto', 'icon' => 'fa-temperature-arrow-up', 'true' => 'can_state_on', 'false' => 'can_state_off'],
        'clutch_pressed' => ['label' => 'can_clutch', 'icon' => 'fa-shoe-prints', 'true' => 'can_state_pressed', 'false' => 'can_state_released'],
        'ignition_on' => ['label' => 'can_ignition', 'icon' => 'fa-key', 'true' => 'can_state_on', 'false' => 'can_state_off'],
        'key_in_ignition' => ['label' => 'can_key_in_ignition', 'icon' => 'fa-key', 'true' => 'can_state_present', 'false' => 'can_state_absent'],
        'footbrake_active' => ['label' => 'can_footbrake', 'icon' => 'fa-circle-stop', 'true' => 'can_state_active', 'false' => 'can_state_inactive'],
        'engine_running' => ['label' => 'can_engine', 'icon' => 'fa-gears', 'true' => 'can_state_running', 'false' => 'can_state_stopped'],
        'hood_open' => ['label' => 'can_hood', 'icon' => 'fa-car', 'true' => 'can_state_open', 'false' => 'can_state_closed'],
        'trunk_open' => ['label' => 'can_trunk', 'icon' => 'fa-box-open', 'true' => 'can_state_open', 'false' => 'can_state_closed'],
        'handbrake_active' => ['label' => 'can_handbrake', 'icon' => 'fa-circle-pause', 'true' => 'can_state_active', 'false' => 'can_state_inactive'],
        'doors_open' => ['label' => 'can_doors', 'icon' => 'fa-car-side', 'true' => 'can_state_open_plural', 'false' => 'can_state_closed_plural'],
    ];
    $availableCanStates = collect($canStateDefinitions)
        ->filter(fn ($definition, $key) => array_key_exists($key, $canBusStates) && $canBusStates[$key] !== null);
    $canHasData = $availableCanStates->isNotEmpty();
    $obdMetricsHaveData = $obdRpm !== null
        || $obdSpeed !== null
        || $runtimeLabel !== null
        || $obdThrottle !== null
        || $obdTemperature !== null
        || $obdModuleVoltage !== null
        || $obdEngineLoad !== null
        || $obdFaultDistance !== null
        || $obdErrorsCount !== null
        || $obdDistanceSinceClear !== null
        || $obdFuel !== null;
    $obdHasData = $obdMetricsHaveData || $canHasData;
    $obdUpdatedAt = $device->last_obd_updated_at ?: $updatedAt;
    $diagnosticUpdatedAt = $device->last_diagnostic_updated_at ?: $updatedAt;
    $movementSummary = match (true) {
        $device->last_ignition === false && $parkingDuration !== null => __('trackers.parking_value', ['duration' => $parkingDuration]),
        $device->last_ignition === false => __('trackers.parking_unknown'),
        (bool) $device->last_movement => __('trackers.moving_now'),
        $parkingDuration !== null => __('trackers.parking_value', ['duration' => $parkingDuration]),
        default => __('trackers.parking_unknown'),
    };
    $canControlEngine = $canControlEngine ?? false;
    $engineOutputs = $engineControl['outputs'] ?? [];
@endphp

<section class="tracker-details-overview" aria-label="{{ __('trackers.details_overview_label') }}">
    <div class="tracker-details-overview-identity">
        <span class="tracker-details-overview-icon" aria-hidden="true">
            <i class="fa-solid fa-car-side"></i>
        </span>
        <div class="tracker-details-overview-copy">
            <span>{{ __('trackers.details_overview_label') }}</span>
            <h3>{{ $vehicleLabel }}</h3>
            <p>
                @if ($registration)
                    <strong>{{ $registration }}</strong>
                    <span aria-hidden="true">&bull;</span>
                @endif
                {{ $fleetLabel }}
            </p>
        </div>
    </div>
    <div class="tracker-details-overview-statuses">
        <span class="tracker-details-overview-chip tracker-details-overview-chip--status status-{{ $device->status }}">
            <span class="tracker-status-dot status-{{ $device->status }}" aria-hidden="true"></span>
            {{ __('trackers.status_' . $device->status) }}
        </span>
        <span class="tracker-details-overview-chip">
            <i class="fa-solid {{ $device->last_movement ? 'fa-location-arrow' : 'fa-square-parking' }}" aria-hidden="true"></i>
            {{ $movementSummary }}
        </span>
        <span class="tracker-details-overview-chip">
            <i class="fa-solid fa-satellite" aria-hidden="true"></i>
            {{ $gpsQuality !== null ? __('trackers.percent_value', ['value' => $gpsQuality]) : __('trackers.unknown_value') }}
        </span>
        <span class="tracker-details-overview-update">
            <i class="fa-regular fa-clock" aria-hidden="true"></i>
            {{ __('trackers.details_updated_value', ['time' => $updatedAt ? $updatedAt->diffForHumans() : __('trackers.no_signal')]) }}
        </span>
    </div>
</section>

<div class="tracker-details-grid">
    <article class="tracker-details-card tracker-details-card--identity">
        <div class="tracker-details-card-header">
            <h3>{{ __('trackers.details_asset_title') }}</h3>
            <span class="tracker-details-card-icon" aria-hidden="true"><i class="fa-solid fa-microchip"></i></span>
        </div>

        <dl class="tracker-details-list">
            @if ($showTechnicalDetails)
                <div>
                    <dt><i class="fa-solid fa-microchip"></i></dt>
                    <dd>{{ __('trackers.detail_model', ['model' => $modelLabel !== '' ? $modelLabel : __('trackers.unknown_value')]) }}</dd>
                </div>
                <div>
                    <dt><i class="fa-solid fa-hashtag"></i></dt>
                    <dd>{{ __('trackers.detail_imei', ['imei' => $device->imei]) }}</dd>
                </div>
            @endif
            <div>
                <dt><i class="fa-solid fa-users"></i></dt>
                <dd>{{ __('trackers.detail_fleet', ['fleet' => $fleetLabel]) }}</dd>
            </div>
        </dl>
    </article>

    <article class="tracker-details-card tracker-details-card--location">
        <div class="tracker-details-card-header">
            <h3>{{ __('trackers.location_title') }}</h3>
            <span class="tracker-details-card-icon" aria-hidden="true"><i class="fa-solid fa-location-dot"></i></span>
        </div>

        <dl class="tracker-details-list">
            <div>
                <dt><i class="fa-solid fa-location-crosshairs"></i></dt>
                <dd>
                    @if ($locationLatitude && $locationLongitude)
                        {{ __('trackers.coordinates_value', ['latitude' => $locationLatitude, 'longitude' => $locationLongitude]) }}
                    @else
                        {{ __('trackers.coordinates_unavailable') }}
                    @endif
                </dd>
            </div>
            <div>
                <dt><i class="fa-regular fa-compass"></i></dt>
                <dd>{{ __('trackers.direction_value', ['direction' => $direction]) }}</dd>
            </div>
            <div>
                <dt><i class="fa-solid fa-house-chimney"></i></dt>
                <dd>
                    <span class="tracker-location-address">{{ $locationAddress ?: __('trackers.address_unavailable') }}</span>
                    @if ($locationAltitude !== null)
                        <small class="tracker-location-meta">
                            {{ __('trackers.altitude_value', ['altitude' => $locationAltitude]) }}
                        </small>
                    @endif
                </dd>
            </div>
        </dl>

        <p class="tracker-details-time">{{ $locationUpdatedAt ? $locationUpdatedAt->diffForHumans() : __('trackers.no_signal') }}</p>
    </article>

    <article class="tracker-details-card tracker-details-card--driver">
        <div class="tracker-details-card-header">
            <h3>{{ __('trackers.driver_title') }}</h3>
            <span class="tracker-details-card-icon" aria-hidden="true"><i class="fa-solid fa-id-card-clip"></i></span>
        </div>

        @if ($currentDriver)
            <dl class="tracker-details-list">
                <div>
                    <dt><i class="fa-solid fa-user-tie"></i></dt>
                    <dd>{{ __('trackers.driver_name_value', ['name' => $currentDriver->full_name]) }}</dd>
                </div>
                <div>
                    <dt><i class="fa-solid fa-address-card"></i></dt>
                    <dd>{{ __('trackers.driver_employee_value', ['employee' => $currentDriver->employee_id ?: __('trackers.unknown_value')]) }}</dd>
                </div>
                <div>
                    <dt><i class="fa-solid fa-building-user"></i></dt>
                    <dd>{{ __('trackers.driver_department_value', ['department' => $currentDriver->department?->name ?: __('trackers.unknown_value')]) }}</dd>
                </div>
                @if ($showDriverIdentifier)
                    <div>
                        <dt><i class="fa-solid fa-key"></i></dt>
                        <dd>{{ __('trackers.driver_identifier_uid_value', ['uid' => $currentDriver->primaryIdentifier?->uid ?: $device->last_driver_identifier_uid]) }}</dd>
                    </div>
                @endif
                <div>
                    <dt><i class="fa-solid fa-phone"></i></dt>
                    <dd>{{ __('trackers.driver_phone_value', ['phone' => $currentDriver->phone ?: __('trackers.unknown_value')]) }}</dd>
                </div>
                <div>
                    <dt><i class="fa-solid fa-circle-check"></i></dt>
                    <dd>{{ __('trackers.driver_status_value', ['status' => __('drivers.status_' . $currentDriver->status)]) }}</dd>
                </div>
            </dl>
        @else
            <div class="tracker-obd-empty">
                <i class="fa-solid fa-circle-info"></i>
                <span>{{ __('trackers.driver_none') }}</span>
            </div>
        @endif

        <p class="tracker-details-time">{{ $updatedAt ? $updatedAt->diffForHumans() : __('trackers.no_signal') }}</p>
    </article>

    <article class="tracker-details-card tracker-details-card--power">
        <div class="tracker-details-card-header">
            <h3>{{ __('trackers.power_title') }}</h3>
            <span class="tracker-details-card-icon" aria-hidden="true"><i class="fa-solid fa-bolt"></i></span>
        </div>

        <dl class="tracker-details-list">
            <div>
                <dt><i class="fa-solid fa-car-battery"></i></dt>
                <dd>
                    {{ $device->last_external_voltage !== null
                        ? __('trackers.external_voltage_value', ['value' => $formatVoltage($device->last_external_voltage)])
                        : __('trackers.external_voltage_unavailable') }}
                </dd>
            </div>
            <div>
                <dt><i class="fa-solid fa-battery-three-quarters"></i></dt>
                <dd>
                    {{ $device->last_battery_voltage !== null
                        ? __('trackers.battery_voltage_value', ['value' => $formatVoltage($device->last_battery_voltage)])
                        : __('trackers.battery_voltage_unavailable') }}
                </dd>
            </div>
            <div>
                <dt><i class="fa-solid fa-gauge-simple-high"></i></dt>
                <dd>{{ $device->last_battery_level !== null ? __('trackers.battery_level_value', ['value' => $device->last_battery_level]) : __('trackers.unknown_value') }}</dd>
            </div>
            <div>
                <dt><i class="fa-solid fa-power-off"></i></dt>
                <dd>
                    @if ($device->last_ignition === null)
                        {{ __('trackers.ignition_unknown') }}
                    @else
                        {{ __('trackers.ignition_value', ['state' => $device->last_ignition ? __('trackers.ignition_on') : __('trackers.ignition_off')]) }}
                    @endif
                </dd>
            </div>
        </dl>

        <p class="tracker-details-time">{{ $updatedAt ? $updatedAt->diffForHumans() : __('trackers.no_signal') }}</p>
    </article>

    <details class="tracker-details-technical tracker-details-card-wide">
        <summary>
            <span class="tracker-details-technical-icon" aria-hidden="true"><i class="fa-solid fa-sliders"></i></span>
            <span class="tracker-details-technical-copy">
                <strong>{{ __('trackers.technical_data_title') }}</strong>
                <small>{{ __('trackers.technical_data_hint') }}</small>
            </span>
            <i class="fa-solid fa-chevron-down tracker-details-technical-chevron" aria-hidden="true"></i>
        </summary>

        <div class="tracker-details-technical-grid">
    <article class="tracker-details-card tracker-details-card--network">
        <div class="tracker-details-card-header">
            <h3>{{ __('trackers.gsm_title') }}</h3>
            <span class="tracker-details-card-icon" aria-hidden="true"><i class="fa-solid fa-tower-cell"></i></span>
        </div>

        <dl class="tracker-details-list">
            <div>
                <dt><i class="fa-solid fa-signal"></i></dt>
                <dd>{{ $gsmSignal !== null ? __('trackers.percent_value', ['value' => $gsmSignal]) : __('trackers.unknown_value') }}</dd>
            </div>
            <div>
                <dt><i class="fa-solid fa-tower-cell"></i></dt>
                <dd>{{ $device->operator_name ?: __('trackers.unknown_value') }}</dd>
            </div>
            <div>
                <dt><i class="fa-solid fa-sim-card"></i></dt>
                <dd>{{ __('trackers.sim_value', ['sim' => $device->sim_number ?: __('trackers.unknown_value')]) }}</dd>
            </div>
            <div>
                <dt><i class="fa-solid fa-code"></i></dt>
                <dd>{{ __('trackers.codec_value', ['codec' => $device->codec ?: __('trackers.unknown_value')]) }}</dd>
            </div>
        </dl>

        <p class="tracker-details-time">{{ $updatedAt ? $updatedAt->diffForHumans() : __('trackers.no_signal') }}</p>
    </article>

    <article class="tracker-details-card tracker-details-advanced-card">
        <div class="tracker-details-card-header">
            <h3>{{ __('trackers.advanced_telemetry_title') }}</h3>
            <span class="tracker-details-card-icon" aria-hidden="true"><i class="fa-solid fa-stethoscope"></i></span>
        </div>

        <dl class="tracker-details-list">
            <div>
                <dt><i class="fa-solid fa-satellite-dish"></i></dt>
                <dd>{{ __('trackers.satellites_value', ['value' => $device->last_satellites ?? __('trackers.unknown_value')]) }}</dd>
            </div>
            <div>
                <dt><i class="fa-solid fa-network-wired"></i></dt>
                <dd>{{ __('trackers.protocol_value', ['protocol' => $device->protocol ? strtoupper($device->protocol) : __('trackers.unknown_value')]) }}</dd>
            </div>
            @if ($showDriverIdentifier)
                <div>
                    <dt><i class="fa-solid fa-id-card-clip"></i></dt>
                    <dd>{{ __('trackers.driver_identifier_uid_value', ['uid' => $currentDriverIdentifierUid ?: __('trackers.unknown_value')]) }}</dd>
                </div>
            @endif
            <div>
                <dt><i class="fa-solid fa-road"></i></dt>
                <dd>
                    {{ $obdOdometer !== null
                        ? __('trackers.odometer_value', ['value' => number_format((float) $obdOdometer, 2, ',', ' ')])
                        : __('trackers.odometer_unavailable') }}
                </dd>
            </div>
            <div>
                <dt><i class="fa-solid fa-clock-rotate-left"></i></dt>
                <dd>
                    {{ $engineLabel
                        ? __('trackers.engine_hours_value', ['value' => $engineLabel])
                        : __('trackers.engine_hours_unavailable') }}
                </dd>
            </div>
            <div>
                <dt><i class="fa-solid fa-toggle-on"></i></dt>
                <dd>{{ __('trackers.io_count_value', ['count' => $ioCount]) }}</dd>
            </div>
            <div>
                <dt><i class="fa-solid fa-wave-square"></i></dt>
                <dd>{{ __('trackers.sensor_count_value', ['count' => $sensorCount]) }}</dd>
            </div>
        </dl>

        <p class="tracker-details-time">{{ $diagnosticUpdatedAt ? $diagnosticUpdatedAt->diffForHumans() : __('trackers.no_signal') }}</p>
    </article>

    <article class="tracker-details-card tracker-details-obd-card">
        <div class="tracker-details-card-header">
            <h3>{{ __('trackers.obd_can_title') }}</h3>
            <span class="tracker-details-card-icon" aria-hidden="true"><i class="fa-solid fa-gauge-high"></i></span>
        </div>

        @if ($obdHasData)
            @if ($obdMetricsHaveData)
                <section class="tracker-can-section" aria-labelledby="tracker-obd-measures-title">
                    <h4 id="tracker-obd-measures-title">{{ __('trackers.can_measurements_title') }}</h4>
                    <dl class="tracker-details-list tracker-obd-measurements-grid">
                <div>
                    <dt><i class="fa-solid fa-wave-square"></i></dt>
                    <dd>
                        {{ $runtimeLabel
                            ? __('trackers.obd_runtime_value', ['value' => $runtimeLabel])
                            : __('trackers.obd_metric_unavailable', ['metric' => __('trackers.obd_runtime_label')]) }}
                    </dd>
                </div>
                <div>
                    <dt><i class="fa-solid fa-gauge-high"></i></dt>
                    <dd>
                        {{ $obdRpm !== null && is_numeric($obdRpm)
                            ? __('trackers.obd_rpm_value', ['value' => number_format((float) $obdRpm, 0, ',', ' ')])
                            : __('trackers.obd_metric_unavailable', ['metric' => __('trackers.obd_rpm_label')]) }}
                    </dd>
                </div>
                <div>
                    <dt><i class="fa-solid fa-gauge-high"></i></dt>
                    <dd>
                        {{ $obdSpeed !== null && is_numeric($obdSpeed)
                            ? __('trackers.obd_speed_value', ['value' => number_format((float) $obdSpeed, 0, ',', ' ')])
                            : __('trackers.obd_metric_unavailable', ['metric' => __('trackers.obd_speed_label')]) }}
                    </dd>
                </div>
                <div>
                    <dt><i class="fa-solid fa-sliders"></i></dt>
                    <dd>
                        {{ $obdThrottle !== null && is_numeric($obdThrottle)
                            ? __('trackers.obd_throttle_value', ['value' => $formatPercentMetric($obdThrottle)])
                            : __('trackers.obd_metric_unavailable', ['metric' => __('trackers.obd_throttle_label')]) }}
                    </dd>
                </div>
                <div>
                    <dt><i class="fa-solid fa-temperature-half"></i></dt>
                    <dd>
                        {{ $obdTemperature !== null && is_numeric($obdTemperature)
                            ? __('trackers.obd_temperature_value', ['value' => number_format((float) $obdTemperature, 0, ',', ' ')])
                            : __('trackers.obd_metric_unavailable', ['metric' => __('trackers.obd_temperature_label')]) }}
                    </dd>
                </div>
                <div>
                    <dt><i class="fa-solid fa-car-battery"></i></dt>
                    <dd>
                        {{ $obdModuleVoltage !== null && is_numeric($obdModuleVoltage)
                            ? __('trackers.obd_module_voltage_value', ['value' => $formatMetric($obdModuleVoltage, 2)])
                            : __('trackers.obd_metric_unavailable', ['metric' => __('trackers.obd_module_voltage_label')]) }}
                    </dd>
                </div>
                <div>
                    <dt><i class="fa-solid fa-weight-hanging"></i></dt>
                    <dd>
                        {{ $obdEngineLoad !== null && is_numeric($obdEngineLoad)
                            ? __('trackers.obd_engine_load_value', ['value' => $formatPercentMetric($obdEngineLoad)])
                            : __('trackers.obd_metric_unavailable', ['metric' => __('trackers.obd_engine_load_label')]) }}
                    </dd>
                </div>
                <div>
                    <dt><i class="fa-solid fa-gas-pump"></i></dt>
                    <dd>
                        {{ $obdFuel !== null && is_numeric($obdFuel)
                            ? __('trackers.obd_fuel_value', ['value' => number_format((float) $obdFuel, 0, ',', ' ')])
                            : __('trackers.obd_metric_unavailable', ['metric' => __('trackers.obd_fuel_label')]) }}
                    </dd>
                </div>
                <div>
                    <dt><i class="fa-solid fa-triangle-exclamation"></i></dt>
                    <dd>
                        {{ $obdFaultDistance !== null && is_numeric($obdFaultDistance)
                            ? __('trackers.obd_fault_distance_value', ['value' => number_format((float) $obdFaultDistance, 0, ',', ' ')])
                            : __('trackers.obd_metric_unavailable', ['metric' => __('trackers.obd_fault_distance_label')]) }}
                    </dd>
                </div>
                <div>
                    <dt><i class="fa-solid fa-bug"></i></dt>
                    <dd>
                        {{ $obdErrorsCount !== null && is_numeric($obdErrorsCount)
                            ? __('trackers.obd_errors_value', ['value' => number_format((float) $obdErrorsCount, 0, ',', ' ')])
                            : __('trackers.obd_metric_unavailable', ['metric' => __('trackers.obd_errors_label')]) }}
                    </dd>
                </div>
                <div>
                    <dt><i class="fa-solid fa-rotate-left"></i></dt>
                    <dd>
                        {{ $obdDistanceSinceClear !== null && is_numeric($obdDistanceSinceClear)
                            ? __('trackers.obd_distance_since_clear_value', ['value' => number_format((float) $obdDistanceSinceClear, 0, ',', ' ')])
                            : __('trackers.obd_metric_unavailable', ['metric' => __('trackers.obd_distance_since_clear_label')]) }}
                    </dd>
                </div>
                    </dl>
                </section>
            @endif

            @if ($canHasData)
                <section class="tracker-can-section" aria-labelledby="tracker-can-states-title">
                    <div class="tracker-can-section-heading">
                        <div>
                            <h4 id="tracker-can-states-title">{{ __('trackers.can_states_title') }}</h4>
                            <p>{{ __('trackers.can_states_hint') }}</p>
                        </div>
                        <span class="tracker-can-live-badge"><i class="fa-solid fa-circle"></i> {{ __('trackers.can_live_data') }}</span>
                    </div>

                    <dl class="tracker-can-state-grid">
                        @foreach ($availableCanStates as $key => $definition)
                            @php
                                $isActive = (bool) $canBusStates[$key];
                            @endphp
                            <div class="tracker-can-state-item tracker-can-state-item--{{ $isActive ? 'active' : 'inactive' }}">
                                <dt>
                                    <span class="tracker-can-state-icon" aria-hidden="true"><i class="fa-solid {{ $definition['icon'] }}"></i></span>
                                    {{ __('trackers.' . $definition['label']) }}
                                </dt>
                                <dd>
                                    <span class="tracker-can-state-value">
                                        <i class="fa-solid {{ $isActive ? 'fa-circle-exclamation' : 'fa-circle-check' }}" aria-hidden="true"></i>
                                        {{ __('trackers.' . $definition[$isActive ? 'true' : 'false']) }}
                                    </span>
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endif
        @else
            <div class="tracker-obd-empty">
                <i class="fa-solid fa-circle-info"></i>
                <span>{{ __('trackers.obd_no_data') }}</span>
            </div>
        @endif

        <p class="tracker-details-time">{{ $obdUpdatedAt ? $obdUpdatedAt->diffForHumans() : __('trackers.no_signal') }}</p>
    </article>
        </div>
    </details>

    @if ($canControlEngine)
        <article class="tracker-details-card tracker-engine-control tracker-details-card-wide" data-engine-control>
            <div class="tracker-details-card-header tracker-engine-control-header">
                <h3>{{ __('trackers.output_control_title') }}</h3>
            </div>

            <div class="tracker-engine-control-actions">
                @foreach ([1, 2] as $outputNumber)
                    @php
                        $outputState = $engineOutputs[(string) $outputNumber] ?? $engineOutputs[$outputNumber] ?? [];
                        $outputActive = (bool) ($outputState['active'] ?? false);
                        $outputBusy = (bool) ($outputState['busy'] ?? false);
                        $outputAction = $outputState['next_action'] ?? ($outputActive ? 'release' : 'immobilize');
                    @endphp
                    <button
                        type="button"
                        class="tracker-engine-button tracker-engine-button--{{ $outputAction === 'immobilize' ? 'danger' : 'release' }}"
                        data-engine-control-trigger
                        data-action="{{ $outputAction }}"
                        data-output="{{ $outputNumber }}"
                        data-url="{{ $engineCommandUrl }}"
                        data-refresh-url="{{ $engineDetailsUrl }}"
                        data-csrf="{{ csrf_token() }}"
                        data-confirm-title="{{ $outputAction === 'immobilize' ? __('trackers.output_control_activate_confirm', ['output' => $outputNumber]) : __('trackers.output_control_release_confirm', ['output' => $outputNumber]) }}"
                        data-confirm-text="{{ $outputAction === 'immobilize' ? __('trackers.output_control_activate_description') : __('trackers.output_control_release_description') }}"
                        data-confirm-button="{{ $outputAction === 'immobilize' ? __('trackers.output_control_activate') : __('trackers.output_control_release') }}"
                        data-cancel-button="{{ __('trackers.cancel') }}"
                        data-success-title="{{ __('trackers.output_control_success_title') }}"
                        data-error-title="{{ __('trackers.output_control_error_title') }}"
                        aria-pressed="{{ $outputActive ? 'true' : 'false' }}"
                        @disabled($outputBusy)
                    >
                        <span class="tracker-engine-button-icon" aria-hidden="true">
                            <i class="fa-solid fa-toggle-{{ $outputActive ? 'on' : 'off' }}"></i>
                        </span>
                        <span>{{ __('trackers.output_control_label', ['output' => $outputNumber]) }}</span>
                        <span class="tracker-engine-switch {{ $outputActive ? 'is-active' : '' }}" aria-hidden="true"></span>
                    </button>
                @endforeach
            </div>
        </article>
    @endif

    <article class="tracker-details-card tracker-details-card--events tracker-details-card-wide">
        <div class="tracker-details-card-header">
            <h3>{{ __('trackers.latest_events_title') }}</h3>
            <a href="{{ $showTechnicalDetails ? route('events.index', ['device' => $device->id]) : route('events.index') }}" class="tracker-details-link">
                {{ __('trackers.view_all_events') }}
            </a>
        </div>

        <div class="tracker-events-list">
            @forelse ($device->trackerEvents as $event)
                <div class="tracker-event-item">
                    <span class="tracker-event-icon"><i class="fa-solid fa-route"></i></span>
                    <p>
                        <strong>{{ $event->localizedTitle() }}</strong>
                        {{ $event->localizedMessage() }}
                    </p>
                    <time>{{ $event->started_at?->diffForHumans() }}</time>
                </div>
            @empty
                <p class="tracker-events-empty">{{ __('trackers.no_events') }}</p>
            @endforelse
        </div>

        <p class="tracker-details-time">{{ $updatedAt ? $updatedAt->diffForHumans() : __('trackers.no_signal') }}</p>
    </article>
</div>
