<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('server_monitoring.title') }} - EXAD Tracking</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=20260528-compact-ui">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260604-server-monitoring">
</head>
<body class="app-font-manrope dashboard-body">
    <div class="dashboard-shell">
        @include('partials.sidebar', ['active' => 'server-monitoring'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <p class="eyebrow mb-1">{{ __('server_monitoring.eyebrow') }}</p>
                    <h1>{{ __('server_monitoring.title') }}</h1>
                    <p class="dashboard-breadcrumb">{{ __('server_monitoring.breadcrumb') }}</p>
                </div>

                @include('partials.topbar-actions')
            </header>

            <section
                class="server-monitoring"
                data-server-monitoring
                data-endpoint="{{ route('server-monitoring.metrics') }}"
                data-unavailable="{{ __('server_monitoring.unavailable') }}"
                data-refreshing="{{ __('server_monitoring.refreshing') }}"
            >
                <div class="server-monitoring-status">
                    <span class="server-log-pill is-live">
                        <i class="fa-solid fa-circle"></i>
                        <span>{{ __('server_monitoring.live') }}</span>
                    </span>
                    <span data-monitoring-meta>{{ __('server_monitoring.waiting') }}</span>
                </div>

                <div class="monitoring-grid">
                    <article class="monitor-card">
                        <div class="monitor-card-icon monitor-blue"><i class="fa-solid fa-microchip"></i></div>
                        <small>{{ __('server_monitoring.cpu') }}</small>
                        <strong data-monitoring-value="cpu.usage">--</strong>
                        <span data-monitoring-value="cpu.cores">--</span>
                        <div class="monitor-progress"><span data-monitoring-bar="cpu.usage"></span></div>
                    </article>

                    <article class="monitor-card">
                        <div class="monitor-card-icon monitor-green"><i class="fa-solid fa-memory"></i></div>
                        <small>{{ __('server_monitoring.ram') }}</small>
                        <strong data-monitoring-value="memory.percent">--</strong>
                        <span data-monitoring-value="memory.used_total">--</span>
                        <div class="monitor-progress"><span data-monitoring-bar="memory.percent"></span></div>
                    </article>

                    <article class="monitor-card">
                        <div class="monitor-card-icon monitor-yellow"><i class="fa-solid fa-hard-drive"></i></div>
                        <small>{{ __('server_monitoring.disk') }}</small>
                        <strong data-monitoring-value="disk.percent">--</strong>
                        <span data-monitoring-value="disk.used_total">--</span>
                        <div class="monitor-progress"><span data-monitoring-bar="disk.percent"></span></div>
                    </article>

                    <article class="monitor-card">
                        <div class="monitor-card-icon monitor-red"><i class="fa-solid fa-gauge-high"></i></div>
                        <small>{{ __('server_monitoring.load') }}</small>
                        <strong data-monitoring-value="load.one">--</strong>
                        <span data-monitoring-value="load.five_fifteen">--</span>
                    </article>
                </div>

                <div class="monitoring-panels">
                    <section class="monitor-panel">
                        <div class="panel-heading">
                            <div>
                                <p class="eyebrow mb-1">{{ __('server_monitoring.network_eyebrow') }}</p>
                                <h2>{{ __('server_monitoring.network') }}</h2>
                            </div>
                        </div>

                        <div class="network-rate-grid">
                            <div>
                                <small>{{ __('server_monitoring.download') }}</small>
                                <strong data-monitoring-value="network.total_rx_rate">--</strong>
                            </div>
                            <div>
                                <small>{{ __('server_monitoring.upload') }}</small>
                                <strong data-monitoring-value="network.total_tx_rate">--</strong>
                            </div>
                        </div>

                        <div class="monitor-table-wrap">
                            <table class="table monitor-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('server_monitoring.interface') }}</th>
                                        <th>{{ __('server_monitoring.received') }}</th>
                                        <th>{{ __('server_monitoring.sent') }}</th>
                                        <th>{{ __('server_monitoring.rx_rate') }}</th>
                                        <th>{{ __('server_monitoring.tx_rate') }}</th>
                                    </tr>
                                </thead>
                                <tbody data-network-interfaces>
                                    <tr>
                                        <td colspan="5">{{ __('server_monitoring.loading') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="monitor-panel">
                        <div class="panel-heading">
                            <div>
                                <p class="eyebrow mb-1">{{ __('server_monitoring.system_eyebrow') }}</p>
                                <h2>{{ __('server_monitoring.system') }}</h2>
                            </div>
                        </div>

                        <dl class="system-list">
                            <div><dt>{{ __('server_monitoring.hostname') }}</dt><dd data-monitoring-value="system.hostname">--</dd></div>
                            <div><dt>{{ __('server_monitoring.os') }}</dt><dd data-monitoring-value="system.os">--</dd></div>
                            <div><dt>{{ __('server_monitoring.uptime') }}</dt><dd data-monitoring-value="system.uptime">--</dd></div>
                            <div><dt>{{ __('server_monitoring.php') }}</dt><dd data-monitoring-value="system.php">--</dd></div>
                            <div><dt>{{ __('server_monitoring.laravel') }}</dt><dd data-monitoring-value="system.laravel">--</dd></div>
                            <div><dt>{{ __('server_monitoring.environment') }}</dt><dd data-monitoring-value="system.environment">--</dd></div>
                            <div><dt>{{ __('server_monitoring.swap') }}</dt><dd data-monitoring-value="memory.swap">--</dd></div>
                        </dl>
                    </section>
                </div>
            </section>
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/dashboard-sidebar.js') }}?v=20260528-sidebar-toggle"></script>
    <script src="{{ asset('js/dashboard-controls.js') }}?v=20260529-shared-controls"></script>
    <script src="{{ asset('js/server-monitoring.js') }}?v=20260604-server-monitoring"></script>
    @include('partials.realtime-alerts')
</body>
</html>
