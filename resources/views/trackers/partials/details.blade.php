@php
    $vehicleLabel = $device->vehicle?->name ?: __('trackers.no_vehicle');
    $registration = $device->vehicle?->registration_number;
    $fleetLabel = $device->fleet?->name ?: __('trackers.no_fleet');
    $updatedAt = $device->last_seen_at ?: $device->last_position_at;
    $modelLabel = trim(($device->brand ? __('trackers.brand_' . $device->brand) : '') . ' ' . (string) $device->model);
    $formatVoltage = fn ($value) => $value !== null ? rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.') : null;
    $parkingDuration = $parkingDuration ?? ($device->last_position_at ? $device->last_position_at->diffForHumans(null, true) : null);
    $locationUpdatedAt = $locationUpdatedAt ?? $updatedAt;
    $locationAddress = $latestPosition?->address ?: $device->last_address;
    $locationLatitude = $latestPosition?->latitude ?? $device->last_latitude;
    $locationLongitude = $latestPosition?->longitude ?? $device->last_longitude;
    $locationAltitude = $latestPosition?->altitude;
    $gsmSignal = $device->last_gsm_signal;
    $gsmSignal = $gsmSignal !== null && $gsmSignal <= 5 ? min(100, $gsmSignal * 20) : $gsmSignal;
    $engineSeconds = $device->last_engine_seconds;
    $engineLabel = null;

    if ($engineSeconds !== null) {
        $engineHours = intdiv((int) $engineSeconds, 3600);
        $engineMinutes = intdiv(((int) $engineSeconds) % 3600, 60);
        $engineLabel = $engineHours > 0
            ? __('trackers.engine_hours_minutes_value', ['hours' => $engineHours, 'minutes' => $engineMinutes])
            : __('trackers.engine_minutes_value', ['minutes' => $engineMinutes]);
    }

    $sensorCount = is_array($device->last_sensors) ? count($device->last_sensors) : 0;
    $ioCount = is_array($device->last_io) ? count($device->last_io) : 0;
    $ioData = is_array($device->last_io) ? $device->last_io : [];
    $sensorData = is_array($device->last_sensors) ? $device->last_sensors : [];
    $payloadData = is_array($device->last_raw_payload) ? $device->last_raw_payload : [];
    $metricValue = function (array $keys) use ($ioData, $sensorData, $payloadData) {
        foreach ($keys as $key) {
            foreach ([$ioData, $sensorData, $payloadData] as $source) {
                $value = data_get($source, $key);

                if ($value !== null && $value !== '') {
                    return $value;
                }
            }
        }

        return null;
    };
    $obdRpm = $metricValue(['rpm', 'engine_rpm', 'obd.rpm', 'can.rpm', 'engine.rpm']);
    $obdFuel = $metricValue(['fuel', 'fuel_level', 'obd.fuel', 'obd.fuel_level', 'can.fuel', 'can.fuel_level', 'fuel.level']);
    $obdTemperature = $metricValue(['engine_temperature', 'coolant_temperature', 'obd.coolant_temperature', 'can.engine_temperature', 'temperature.engine']);

    if ($obdFuel !== null && is_numeric($obdFuel) && (float) $obdFuel <= 1) {
        $obdFuel = (float) $obdFuel * 100;
    }

    $obdHasData = $device->last_odometer_km !== null
        || $engineLabel
        || $obdRpm !== null
        || $obdFuel !== null
        || $obdTemperature !== null
        || $ioCount > 0
        || $sensorCount > 0;
@endphp

<div class="tracker-details-grid">
    <article class="tracker-details-card">
        <div class="tracker-details-card-header">
            <h3>{{ $vehicleLabel }} @if ($registration)<span>({{ $registration }})</span>@endif</h3>
            <i class="fa-solid fa-ellipsis-vertical"></i>
        </div>

        <dl class="tracker-details-list">
            <div>
                <dt><i class="fa-solid fa-microchip"></i></dt>
                <dd>{{ __('trackers.detail_model', ['model' => $modelLabel !== '' ? $modelLabel : __('trackers.unknown_value')]) }}</dd>
            </div>
            <div>
                <dt><i class="fa-solid fa-hashtag"></i></dt>
                <dd>{{ __('trackers.detail_imei', ['imei' => $device->imei]) }}</dd>
            </div>
            <div>
                <dt><i class="fa-solid fa-users"></i></dt>
                <dd>{{ __('trackers.detail_fleet', ['fleet' => $fleetLabel]) }}</dd>
            </div>
            <div>
                <dt><span class="tracker-status-dot status-{{ $device->status }}"></span></dt>
                <dd class="tracker-status-text status-{{ $device->status }}">{{ __('trackers.status_' . $device->status) }}</dd>
            </div>
        </dl>
    </article>

    <article class="tracker-details-card">
        <div class="tracker-details-card-header">
            <h3>{{ __('trackers.location_title') }}</h3>
            <i class="fa-solid fa-ellipsis-vertical"></i>
        </div>

        <dl class="tracker-details-list">
            <div>
                <dt><i class="fa-solid fa-signal"></i></dt>
                <dd>{{ $gpsQuality !== null ? __('trackers.percent_value', ['value' => $gpsQuality]) : __('trackers.unknown_value') }}</dd>
            </div>
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
                <dt><i class="fa-solid fa-square-parking"></i></dt>
                <dd>
                    @if ($device->last_ignition === false && $parkingDuration)
                        {{ __('trackers.parking_value', ['duration' => $parkingDuration]) }}
                    @elseif ($device->last_ignition === false)
                        {{ __('trackers.parking_unknown') }}
                    @elseif ($device->last_movement)
                        {{ __('trackers.moving_now') }}
                    @elseif ($parkingDuration)
                        {{ __('trackers.parking_value', ['duration' => $parkingDuration]) }}
                    @else
                        {{ __('trackers.parking_unknown') }}
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
                    @if ($locationLatitude && $locationLongitude)
                        <small class="tracker-location-meta">
                            {{ __('trackers.coordinates_value', ['latitude' => $locationLatitude, 'longitude' => $locationLongitude]) }}
                            @if ($locationAltitude !== null)
                                {{ __('trackers.altitude_value', ['altitude' => $locationAltitude]) }}
                            @endif
                        </small>
                    @endif
                </dd>
            </div>
        </dl>

        <p class="tracker-details-time">{{ $locationUpdatedAt ? $locationUpdatedAt->diffForHumans() : __('trackers.no_signal') }}</p>
    </article>

    <article class="tracker-details-card">
        <div class="tracker-details-card-header">
            <h3>{{ __('trackers.power_title') }}</h3>
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

    <article class="tracker-details-card">
        <div class="tracker-details-card-header">
            <h3>{{ __('trackers.gsm_title') }}</h3>
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
                <dt><i class="fa-solid fa-network-wired"></i></dt>
                <dd>{{ __('trackers.protocol_value', ['protocol' => $device->protocol ?: __('trackers.unknown_value')]) }}</dd>
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
            <i class="fa-solid fa-sliders"></i>
        </div>

        <dl class="tracker-details-list">
            <div>
                <dt><i class="fa-solid fa-satellite-dish"></i></dt>
                <dd>{{ __('trackers.satellites_value', ['value' => $device->last_satellites ?? __('trackers.unknown_value')]) }}</dd>
            </div>
            <div>
                <dt><i class="fa-solid fa-road"></i></dt>
                <dd>{{ $device->last_odometer_km !== null ? __('trackers.odometer_value', ['value' => $device->last_odometer_km]) : __('trackers.odometer_unavailable') }}</dd>
            </div>
            <div>
                <dt><i class="fa-solid fa-stopwatch"></i></dt>
                <dd>{{ $engineLabel ? __('trackers.engine_hours_value', ['value' => $engineLabel]) : __('trackers.engine_hours_unavailable') }}</dd>
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

    </article>

    <article class="tracker-details-card tracker-details-obd-card">
        <div class="tracker-details-card-header">
            <h3>{{ __('trackers.obd_can_title') }}</h3>
            <i class="fa-solid fa-car-battery"></i>
        </div>

        @if ($obdHasData)
            <dl class="tracker-details-list">
                <div>
                    <dt><i class="fa-solid fa-road"></i></dt>
                    <dd>
                        {{ $device->last_odometer_km !== null
                            ? __('trackers.odometer_value', ['value' => number_format((float) $device->last_odometer_km, 2, ',', ' ')])
                            : __('trackers.obd_metric_unavailable', ['metric' => __('trackers.obd_odometer_label')]) }}
                    </dd>
                </div>
                <div>
                    <dt><i class="fa-solid fa-clock-rotate-left"></i></dt>
                    <dd>
                        {{ $engineLabel
                            ? __('trackers.engine_hours_value', ['value' => $engineLabel])
                            : __('trackers.obd_metric_unavailable', ['metric' => __('trackers.obd_engine_hours_label')]) }}
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
                    <dt><i class="fa-solid fa-gas-pump"></i></dt>
                    <dd>
                        {{ $obdFuel !== null && is_numeric($obdFuel)
                            ? __('trackers.obd_fuel_value', ['value' => number_format((float) $obdFuel, 0, ',', ' ')])
                            : __('trackers.obd_metric_unavailable', ['metric' => __('trackers.obd_fuel_label')]) }}
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
                    <dt><i class="fa-solid fa-diagram-project"></i></dt>
                    <dd>{{ __('trackers.obd_protocol_value', ['value' => $device->protocol ? strtoupper($device->protocol) : __('trackers.unknown_value')]) }}</dd>
                </div>
            </dl>
        @else
            <div class="tracker-obd-empty">
                <i class="fa-solid fa-circle-info"></i>
                <span>{{ __('trackers.obd_no_data') }}</span>
            </div>
        @endif

        <p class="tracker-details-time">{{ $updatedAt ? $updatedAt->diffForHumans() : __('trackers.no_signal') }}</p>
    </article>

    <article class="tracker-details-card tracker-details-card-wide">
        <div class="tracker-details-card-header">
            <h3>{{ __('trackers.latest_events_title') }}</h3>
            <a href="{{ route('events.index', ['device' => $device->id]) }}" class="tracker-details-link">
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
