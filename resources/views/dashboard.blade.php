<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('dashboard.title') }} - EXAD Tracking</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=20260528-compact-ui">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260709-dashboard-refinement">
</head>
<body class="app-font-manrope dashboard-body">
    <div class="dashboard-shell">
        @include('partials.sidebar', ['active' => 'dashboard'])

        <main class="dashboard-main dashboard-home-main">
            <header class="dashboard-topbar">
                @include('partials.sidebar-toggle')
                <div>
                    <h1>{{ __('dashboard.title') }}</h1>
                    <p class="dashboard-breadcrumb">{{ __('dashboard.breadcrumb') }}</p>
                </div>

                @include('partials.topbar-actions')
            </header>

            <section class="period-filter" aria-label="{{ __('dashboard.period_filter') }}">
                <a href="{{ route('dashboard', ['period' => 'week']) }}" class="{{ $period === 'week' ? 'active' : '' }}">{{ __('dashboard.week') }}</a>
                <a href="{{ route('dashboard', ['period' => 'month']) }}" class="{{ $period === 'month' ? 'active' : '' }}">{{ __('dashboard.month') }}</a>
                <a href="{{ route('dashboard', ['period' => 'year']) }}" class="{{ $period === 'year' ? 'active' : '' }}">{{ __('dashboard.year') }}</a>
            </section>

            @php
                $metricCards = [
                    [
                        'tone' => 'blue',
                        'icon' => 'fa-car-side',
                        'label' => __('dashboard.vehicle'),
                        'value' => $summary['vehicles_total'],
                        'detail' => __('dashboard.total_fleet'),
                        'progress' => 100,
                    ],
                    [
                        'tone' => 'purple',
                        'icon' => 'fa-microchip',
                        'label' => __('dashboard.devices'),
                        'value' => $summary['devices_total'],
                        'detail' => __('dashboard.registered_assets'),
                        'progress' => 100,
                    ],
                    [
                        'tone' => 'green',
                        'icon' => 'fa-signal',
                        'label' => __('dashboard.online_devices'),
                        'value' => $summary['devices_online'],
                        'detail' => __('dashboard.current_online'),
                        'progress' => $summary['devices_total'] > 0 ? round(($summary['devices_online'] / $summary['devices_total']) * 100) : 0,
                    ],
                    [
                        'tone' => 'red',
                        'icon' => 'fa-triangle-exclamation',
                        'label' => __('dashboard.offline_devices'),
                        'value' => $summary['devices_offline'],
                        'detail' => __('dashboard.offline_attention'),
                        'progress' => $summary['devices_total'] > 0 ? round(($summary['devices_offline'] / $summary['devices_total']) * 100) : 0,
                    ],
                    [
                        'tone' => 'amber',
                        'icon' => 'fa-route',
                        'label' => __('dashboard.moving_devices'),
                        'value' => $summary['devices_moving'],
                        'detail' => __('dashboard.moving_now'),
                        'progress' => $summary['devices_total'] > 0 ? round(($summary['devices_moving'] / $summary['devices_total']) * 100) : 0,
                    ],
                    [
                        'tone' => 'blue',
                        'icon' => 'fa-location-crosshairs',
                        'label' => __('dashboard.positions_period'),
                        'value' => $summary['positions_period'],
                        'detail' => __('dashboard.period_activity', ['period' => $periodWindow['label']]),
                        'progress' => min(100, $summary['positions_period'] > 0 ? 72 : 0),
                    ],
                ];
            @endphp

            <section class="admin-metrics" aria-label="{{ __('dashboard.platform_indicators') }}">
                @foreach ($metricCards as $card)
                    <article class="admin-metric-card metric-{{ $card['tone'] }}-soft metric-modern-card">
                        <div class="metric-card-top">
                            <span class="metric-icon"><i class="fa-solid {{ $card['icon'] }}"></i></span>
                            <span class="metric-kicker">{{ $periodWindow['label'] }}</span>
                        </div>
                        <strong>{{ number_format($card['value']) }}</strong>
                        <p>{{ $card['label'] }}</p>
                        <div class="metric-foot">
                            <span>{{ $card['detail'] }}</span>
                            <span>{{ $card['progress'] }}%</span>
                        </div>
                        <span class="metric-progress" aria-hidden="true">
                            <span style="width: {{ $card['progress'] }}%"></span>
                        </span>
                    </article>
                @endforeach
            </section>

            <section class="admin-panel latest-trackers-panel">
                <div class="panel-heading latest-trackers-heading">
                    <div class="latest-trackers-title">
                        <span class="latest-trackers-icon"><i class="fa-solid fa-satellite-dish"></i></span>
                        <div>
                            <p class="eyebrow mb-1">{{ __('dashboard.fleet_tracking') }}</p>
                            <h2>{{ __('dashboard.latest_devices') }}</h2>
                        </div>
                    </div>
                    <span class="latest-trackers-count">
                        {{ trans_choice('dashboard.registered_trackers', $devices->count(), ['count' => $devices->count()]) }}
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle dashboard-table latest-trackers-table">
                        <thead>
                            <tr>
                                <th>{{ __('dashboard.device') }}</th>
                                <th>{{ __('dashboard.vehicle_fleet') }}</th>
                                <th>{{ __('dashboard.status') }}</th>
                                <th>{{ __('dashboard.speed') }}</th>
                                <th>{{ __('dashboard.last_signal') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($devices as $device)
                                @php
                                    $statusLabel = __('dashboard.status_' . $device->status);
                                    $deviceName = $device->name ?: ($device->model ?: __('dashboard.device_fallback', ['imei' => $device->imei]));
                                    $vehicle = $device->vehicle;
                                    $fleet = $device->fleet;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="tracker-cell">
                                            <span class="tracker-avatar"><i class="fa-solid fa-microchip"></i></span>
                                            <div class="tracker-main">
                                                <strong>{{ $deviceName }}</strong>
                                                <span class="technical-code">{{ $device->imei }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tracker-main">
                                            <strong>{{ $vehicle?->name ?? __('dashboard.no_vehicle') }}</strong>
                                            <span class="tracker-submeta">
                                                {{ $vehicle?->registration_number ?? __('dashboard.not_assigned') }}
                                                @if ($fleet)
                                                    <span aria-hidden="true">·</span> {{ $fleet->name }}
                                                @endif
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-pill latest-status status-{{ $device->status }}">
                                            <span aria-hidden="true"></span>
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="metric-chip speed-chip">
                                            <i class="fa-solid fa-gauge-high"></i>
                                            {{ $device->last_speed }} km/h
                                        </span>
                                    </td>
                                    <td>
                                        <span class="metric-chip signal-chip">
                                            <i class="fa-regular fa-clock"></i>
                                            {{ $device->last_seen_at?->diffForHumans() ?? __('dashboard.no_signal') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        {{ __('dashboard.no_devices') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            @php
                $supervisionCards = [
                    [
                        'tone' => 'danger',
                        'icon' => 'fa-triangle-exclamation',
                        'title' => __('dashboard.supervision_no_signal'),
                        'caption' => __('dashboard.supervision_no_signal_caption'),
                        'items' => $supervisionLists['no_signal'],
                        'metric' => fn ($device) => $device->last_seen_at?->diffForHumans() ?? __('dashboard.no_signal'),
                    ],
                    [
                        'tone' => 'blue',
                        'icon' => 'fa-gauge-high',
                        'title' => __('dashboard.supervision_speed'),
                        'caption' => __('dashboard.supervision_speed_caption'),
                        'items' => $supervisionLists['speed'],
                        'metric' => fn ($device) => __('dashboard.speed_value', ['value' => (int) $device->last_speed]),
                    ],
                    [
                        'tone' => 'amber',
                        'icon' => 'fa-hourglass-half',
                        'title' => __('dashboard.supervision_idle'),
                        'caption' => __('dashboard.supervision_idle_caption'),
                        'items' => $supervisionLists['idle'],
                        'metric' => fn ($device) => $device->last_seen_at?->diffForHumans() ?? __('dashboard.no_signal'),
                    ],
                    [
                        'tone' => 'green',
                        'icon' => 'fa-battery-quarter',
                        'title' => __('dashboard.supervision_battery'),
                        'caption' => __('dashboard.supervision_battery_caption'),
                        'items' => $supervisionLists['battery'],
                        'metric' => fn ($device) => __('dashboard.battery_value', ['value' => (int) $device->last_battery_level]),
                    ],
                ];
            @endphp

            <section class="dashboard-supervision-grid" aria-label="{{ __('dashboard.operational_supervision') }}">
                @foreach ($supervisionCards as $card)
                    <article class="admin-panel supervision-card supervision-card-{{ $card['tone'] }}">
                        <div class="supervision-card-header">
                            <span class="supervision-icon"><i class="fa-solid {{ $card['icon'] }}"></i></span>
                            <div>
                                <p class="eyebrow mb-1">{{ __('dashboard.operational_supervision') }}</p>
                                <h2>{{ $card['title'] }}</h2>
                                <small>{{ $card['caption'] }}</small>
                            </div>
                        </div>

                        <div class="supervision-list">
                            @forelse ($card['items'] as $device)
                                @php
                                    $vehicleLabel = $device->vehicle?->name ?? __('dashboard.no_vehicle');
                                    $fleetLabel = $device->fleet?->name ?? __('dashboard.no_fleet');
                                    $mapSearch = $device->vehicle?->name ?: ($device->name ?: $device->imei);
                                @endphp
                                <a href="{{ route('map.index', ['search' => $mapSearch, 'show' => 1]) }}" class="supervision-item">
                                    <span>
                                        <strong>{{ $vehicleLabel }}</strong>
                                        <small>{{ $device->imei }} · {{ $fleetLabel }}</small>
                                    </span>
                                    <em>{{ $card['metric']($device) }}</em>
                                </a>
                            @empty
                                <p class="supervision-empty">{{ __('dashboard.supervision_empty') }}</p>
                            @endforelse
                        </div>
                    </article>
                @endforeach
            </section>

            <section class="dashboard-map-grid">
                <article class="admin-panel world-map-panel">
                    <div class="chart-panel-header">
                        <div>
                            <p class="eyebrow mb-1">{{ __('dashboard.global_tracking') }}</p>
                            <h2>{{ __('dashboard.tracker_world_map') }}</h2>
                        </div>
                        <span class="chart-chip">{{ __('dashboard.grouped_trackers') }}</span>
                    </div>
                    <div class="dashboard-world-map" data-dashboard-world-map>
                        <div class="world-map-canvas"></div>
                        <div class="world-map-empty">{{ __('dashboard.no_positioned_trackers') }}</div>
                    </div>
                </article>
            </section>

            <section class="charts-grid">
                <article class="admin-panel chart-panel chart-panel-wide">
                    <div class="chart-panel-header">
                        <div>
                            <p class="eyebrow mb-1">{{ __('dashboard.gps_activity') }}</p>
                            <h2>{{ __('dashboard.positions_evolution') }}</h2>
                        </div>
                        <span class="chart-chip">{{ $periodWindow['label'] }}</span>
                    </div>
                    <div class="dashboard-chart dashboard-chart-main" data-dashboard-chart="trend"></div>
                </article>

                <article class="admin-panel chart-panel">
                    <div class="chart-panel-header">
                        <div>
                            <p class="eyebrow mb-1">{{ __('dashboard.realtime_health') }}</p>
                            <h2>{{ __('dashboard.device_status_distribution') }}</h2>
                        </div>
                    </div>
                    <div class="dashboard-chart dashboard-chart-donut" data-dashboard-chart="status"></div>
                </article>

                <article class="admin-panel chart-panel">
                    <div class="chart-panel-header">
                        <div>
                            <p class="eyebrow mb-1">{{ __('dashboard.signal_quality') }}</p>
                            <h2>{{ __('dashboard.signal_health') }}</h2>
                        </div>
                    </div>
                    <div class="dashboard-chart dashboard-chart-radial" data-dashboard-chart="health"></div>
                </article>

                <article class="admin-panel chart-panel chart-panel-wide">
                    <div class="chart-panel-header">
                        <div>
                            <p class="eyebrow mb-1">{{ __('dashboard.fleet_tracking') }}</p>
                            <h2>{{ __('dashboard.fleet_distribution') }}</h2>
                        </div>
                    </div>
                    <div class="dashboard-chart dashboard-chart-bar" data-dashboard-chart="fleet"></div>
                </article>
            </section>
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/dashboard-sidebar.js') }}?v=20260626-responsive-sidebar-default"></script>
    <script src="{{ asset('js/dashboard-controls.js') }}?v=20260529-shared-controls"></script>
    @include('partials.realtime-alerts')
    <script src="{{ asset('vendor/apexcharts/apexcharts.js') }}"></script>
    <script src="{{ asset('vendor/d3/d3.min.js') }}"></script>
    <script src="{{ asset('vendor/topojson/topojson.min.js') }}"></script>
    <script src="{{ asset('vendor/datamaps/datamaps.world.min.js') }}"></script>
    <script id="dashboardChartData" type="application/json">@json($dashboardCharts)</script>
    <script src="{{ asset('js/dashboard-charts.js') }}?v=20260709-dashboard-refinement"></script>
</body>
</html>
