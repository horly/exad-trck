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
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260602-sidebar-version-global">
</head>
<body class="app-font-manrope dashboard-body">
    <div class="dashboard-shell">
        @include('partials.sidebar', ['active' => 'dashboard'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('dashboard.title') }}</h1>
                    <p class="dashboard-breadcrumb">{{ __('dashboard.breadcrumb') }}</p>
                </div>

                @include('partials.topbar-actions')
            </header>

            <section class="period-filter" aria-label="{{ __('dashboard.period_filter') }}">
                <button type="button">{{ __('dashboard.week') }}</button>
                <button type="button" class="active">{{ __('dashboard.month') }}</button>
                <button type="button">{{ __('dashboard.year') }}</button>
            </section>

            <section class="admin-metrics" aria-label="{{ __('dashboard.platform_indicators') }}">
                <article class="admin-metric-card metric-blue-soft">
                    <span class="metric-icon"><i class="fa-solid fa-car-side"></i></span>
                    <span class="metric-trend"><i class="fa-solid fa-arrow-up"></i> +100%</span>
                    <strong>{{ number_format($summary['vehicles_total']) }}</strong>
                    <p>{{ __('dashboard.vehicle') }}</p>
                </article>

                <article class="admin-metric-card metric-purple-soft">
                    <span class="metric-icon"><i class="fa-solid fa-microchip"></i></span>
                    <span class="metric-trend"><i class="fa-solid fa-arrow-up"></i> +100%</span>
                    <strong>{{ number_format($summary['devices_total']) }}</strong>
                    <p>{{ __('dashboard.devices') }}</p>
                </article>

                <article class="admin-metric-card metric-green-soft">
                    <span class="metric-icon"><i class="fa-solid fa-signal"></i></span>
                    <span class="metric-trend"><i class="fa-solid fa-arrow-up"></i> +100%</span>
                    <strong>{{ number_format($summary['devices_online']) }}</strong>
                    <p>{{ __('dashboard.online_devices') }}</p>
                </article>

                <article class="admin-metric-card metric-red-soft">
                    <span class="metric-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <strong>{{ number_format(max($summary['devices_total'] - $summary['devices_online'], 0)) }}</strong>
                    <p>{{ __('dashboard.offline_devices') }}</p>
                </article>

                <article class="admin-metric-card metric-amber-soft">
                    <span class="metric-icon"><i class="fa-solid fa-route"></i></span>
                    <strong>{{ number_format($summary['devices_moving']) }}</strong>
                    <p>{{ __('dashboard.moving_devices') }}</p>
                </article>

                <article class="admin-metric-card metric-blue-soft">
                    <span class="metric-icon"><i class="fa-solid fa-location-crosshairs"></i></span>
                    <span class="metric-trend"><i class="fa-solid fa-arrow-up"></i> +100%</span>
                    <strong>{{ number_format($summary['positions_today']) }}</strong>
                    <p>{{ __('dashboard.positions_today') }}</p>
                </article>
            </section>

            <section class="charts-grid">
                <article class="admin-panel chart-panel chart-panel-wide">
                    <h2>{{ __('dashboard.positions_evolution') }}</h2>
                    {!! $positionsChart->container() !!}
                </article>

                <article class="admin-panel chart-panel">
                    <h2>{{ __('dashboard.device_status_distribution') }}</h2>
                    {!! $deviceStatusChart->container() !!}
                </article>
            </section>

            <section class="admin-panel">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow mb-1">{{ __('dashboard.fleet_tracking') }}</p>
                        <h2>{{ __('dashboard.latest_devices') }}</h2>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle dashboard-table">
                        <thead>
                            <tr>
                                <th>{{ __('dashboard.device') }}</th>
                                <th>{{ __('dashboard.status') }}</th>
                                <th>{{ __('dashboard.speed') }}</th>
                                <th>{{ __('dashboard.last_signal') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($devices as $device)
                                <tr>
                                    <td>
                                        <strong>{{ $device->name ?: __('dashboard.device_fallback', ['imei' => $device->imei]) }}</strong>
                                        <span class="technical-code">{{ $device->imei }}</span>
                                    </td>
                                    <td>
                                        <span class="status-pill status-{{ $device->status }}">
                                            {{ ucfirst($device->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $device->last_speed }} km/h</td>
                                    <td>{{ $device->last_seen_at?->diffForHumans() ?? __('dashboard.no_signal') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="empty-state">
                                        {{ __('dashboard.no_devices') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/dashboard-sidebar.js') }}?v=20260528-sidebar-toggle"></script>
    <script src="{{ asset('js/dashboard-controls.js') }}?v=20260529-shared-controls"></script>
    @include('partials.realtime-alerts')
    <script src="{{ asset('vendor/apexcharts/apexcharts.js') }}"></script>
    {!! $positionsChart->script() !!}
    {!! $deviceStatusChart->script() !!}
</body>
</html>
