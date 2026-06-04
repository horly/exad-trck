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
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260604-server-monitoring-dark">
</head>
<body class="app-font-manrope dashboard-body">
    @php
        $monitorUnavailable = __('server_monitoring.unavailable');
        $monitorFormatBytes = function ($value) use ($monitorUnavailable) {
            if ($value === null) {
                return $monitorUnavailable;
            }

            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $size = (float) $value;
            $index = 0;

            while ($size >= 1024 && $index < count($units) - 1) {
                $size /= 1024;
                $index++;
            }

            return rtrim(rtrim(number_format($size, $size >= 10 || $index === 0 ? 0 : 1, '.', ' '), '0'), '.').' '.$units[$index];
        };
        $monitorFormatPercent = fn ($value) => $value === null ? $monitorUnavailable : $value.'%';
        $monitorFormatRate = fn ($value) => $value === null ? $monitorUnavailable : $monitorFormatBytes($value).'/s';
        $monitorMetric = fn (string $path) => data_get($metrics, $path);
        $monitorBar = fn (string $path) => max(0, min(100, (int) ($monitorMetric($path) ?? 0)));
        $monitorCores = $monitorMetric('cpu.cores');
        $monitorLoadFive = $monitorMetric('load.five');
        $monitorLoadFifteen = $monitorMetric('load.fifteen');
        $networkInterfaces = $monitorMetric('network.interfaces') ?? [];
    @endphp

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
                data-label-cpu="{{ __('server_monitoring.cpu') }}"
                data-label-ram="{{ __('server_monitoring.ram') }}"
                data-label-disk="{{ __('server_monitoring.disk') }}"
                data-label-load="{{ __('server_monitoring.load') }}"
                data-label-download="{{ __('server_monitoring.download') }}"
                data-label-upload="{{ __('server_monitoring.upload') }}"
            >
                <div class="server-monitoring-status">
                    <span class="server-log-pill is-live">
                        <i class="fa-solid fa-circle"></i>
                        <span>{{ __('server_monitoring.live') }}</span>
                    </span>
                </div>

                <div class="monitoring-grid">
                    <article class="monitor-card">
                        <div class="monitor-card-icon monitor-blue"><i class="fa-solid fa-microchip"></i></div>
                        <small>{{ __('server_monitoring.cpu') }}</small>
                        <strong data-monitoring-value="cpu.usage">{{ $monitorFormatPercent($monitorMetric('cpu.usage')) }}</strong>
                        <span data-monitoring-value="cpu.cores">{{ $monitorCores === null ? $monitorUnavailable : $monitorCores.' cores' }}</span>
                        <div class="monitor-progress"><span data-monitoring-bar="cpu.usage" style="width: {{ $monitorBar('cpu.usage') }}%"></span></div>
                    </article>

                    <article class="monitor-card">
                        <div class="monitor-card-icon monitor-green"><i class="fa-solid fa-memory"></i></div>
                        <small>{{ __('server_monitoring.ram') }}</small>
                        <strong data-monitoring-value="memory.percent">{{ $monitorFormatPercent($monitorMetric('memory.percent')) }}</strong>
                        <span data-monitoring-value="memory.used_total">{{ $monitorFormatBytes($monitorMetric('memory.used')) }} / {{ $monitorFormatBytes($monitorMetric('memory.total')) }}</span>
                        <div class="monitor-progress"><span data-monitoring-bar="memory.percent" style="width: {{ $monitorBar('memory.percent') }}%"></span></div>
                    </article>

                    <article class="monitor-card">
                        <div class="monitor-card-icon monitor-yellow"><i class="fa-solid fa-hard-drive"></i></div>
                        <small>{{ __('server_monitoring.disk') }}</small>
                        <strong data-monitoring-value="disk.percent">{{ $monitorFormatPercent($monitorMetric('disk.percent')) }}</strong>
                        <span data-monitoring-value="disk.used_total">{{ $monitorFormatBytes($monitorMetric('disk.used')) }} / {{ $monitorFormatBytes($monitorMetric('disk.total')) }}</span>
                        <div class="monitor-progress"><span data-monitoring-bar="disk.percent" style="width: {{ $monitorBar('disk.percent') }}%"></span></div>
                    </article>

                    <article class="monitor-card">
                        <div class="monitor-card-icon monitor-red"><i class="fa-solid fa-gauge-high"></i></div>
                        <small>{{ __('server_monitoring.load') }}</small>
                        <strong data-monitoring-value="load.one">{{ $monitorMetric('load.one') ?? $monitorUnavailable }}</strong>
                        <span data-monitoring-value="load.five_fifteen">{{ $monitorLoadFive ?? $monitorUnavailable }} / {{ $monitorLoadFifteen ?? $monitorUnavailable }}</span>
                    </article>
                </div>

                <div class="monitoring-charts">
                    <section class="monitor-chart-panel monitor-chart-wide">
                        <div class="panel-heading">
                            <div>
                                <p class="eyebrow mb-1">{{ __('server_monitoring.performance_eyebrow') }}</p>
                                <h2>{{ __('server_monitoring.cpu_memory_chart') }}</h2>
                            </div>
                            <span class="monitor-chart-badge">{{ __('server_monitoring.live') }}</span>
                        </div>
                        <div class="monitor-chart" data-monitoring-chart="cpu-memory"></div>
                    </section>

                    <section class="monitor-chart-panel">
                        <div class="panel-heading">
                            <div>
                                <p class="eyebrow mb-1">{{ __('server_monitoring.capacity_eyebrow') }}</p>
                                <h2>{{ __('server_monitoring.disk_chart') }}</h2>
                            </div>
                        </div>
                        <div class="monitor-chart monitor-chart-radial" data-monitoring-chart="disk"></div>
                    </section>

                    <section class="monitor-chart-panel monitor-chart-wide">
                        <div class="panel-heading">
                            <div>
                                <p class="eyebrow mb-1">{{ __('server_monitoring.network_eyebrow') }}</p>
                                <h2>{{ __('server_monitoring.network_chart') }}</h2>
                            </div>
                        </div>
                        <div class="monitor-chart" data-monitoring-chart="network"></div>
                    </section>

                    <section class="monitor-chart-panel">
                        <div class="panel-heading">
                            <div>
                                <p class="eyebrow mb-1">{{ __('server_monitoring.load_eyebrow') }}</p>
                                <h2>{{ __('server_monitoring.load_chart') }}</h2>
                            </div>
                        </div>
                        <div class="monitor-chart" data-monitoring-chart="load"></div>
                    </section>
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
                                <span class="network-rate-icon network-rate-in"><i class="fa-solid fa-arrow-down"></i></span>
                                <span>
                                    <small>{{ __('server_monitoring.download') }}</small>
                                    <strong data-monitoring-value="network.total_rx_rate">{{ $monitorFormatRate($monitorMetric('network.total_rx_rate')) }}</strong>
                                </span>
                            </div>
                            <div>
                                <span class="network-rate-icon network-rate-out"><i class="fa-solid fa-arrow-up"></i></span>
                                <span>
                                    <small>{{ __('server_monitoring.upload') }}</small>
                                    <strong data-monitoring-value="network.total_tx_rate">{{ $monitorFormatRate($monitorMetric('network.total_tx_rate')) }}</strong>
                                </span>
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
                                    @forelse ($networkInterfaces as $interface)
                                        <tr>
                                            <td><strong>{{ $interface['name'] }}</strong></td>
                                            <td>{{ $monitorFormatBytes($interface['rx'] ?? null) }}</td>
                                            <td>{{ $monitorFormatBytes($interface['tx'] ?? null) }}</td>
                                            <td>{{ $monitorFormatRate($interface['rx_rate'] ?? null) }}</td>
                                            <td>{{ $monitorFormatRate($interface['tx_rate'] ?? null) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5">{{ $monitorUnavailable }}</td>
                                        </tr>
                                    @endforelse
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
                            <div><dt>{{ __('server_monitoring.hostname') }}</dt><dd data-monitoring-value="system.hostname">{{ $monitorMetric('system.hostname') ?? $monitorUnavailable }}</dd></div>
                            <div><dt>{{ __('server_monitoring.os') }}</dt><dd data-monitoring-value="system.os">{{ $monitorMetric('system.os') ?? $monitorUnavailable }}</dd></div>
                            <div><dt>{{ __('server_monitoring.uptime') }}</dt><dd data-monitoring-value="system.uptime">{{ $monitorMetric('system.uptime') ?? $monitorUnavailable }}</dd></div>
                            <div><dt>{{ __('server_monitoring.php') }}</dt><dd data-monitoring-value="system.php">{{ $monitorMetric('system.php') ?? $monitorUnavailable }}</dd></div>
                            <div><dt>{{ __('server_monitoring.laravel') }}</dt><dd data-monitoring-value="system.laravel">{{ $monitorMetric('system.laravel') ?? $monitorUnavailable }}</dd></div>
                            <div><dt>{{ __('server_monitoring.environment') }}</dt><dd data-monitoring-value="system.environment">{{ $monitorMetric('system.environment') ?? $monitorUnavailable }}</dd></div>
                            <div><dt>{{ __('server_monitoring.swap') }}</dt><dd data-monitoring-value="memory.swap">{{ $monitorFormatPercent($monitorMetric('memory.swap_percent')) }} - {{ $monitorFormatBytes($monitorMetric('memory.swap_used')) }} / {{ $monitorFormatBytes($monitorMetric('memory.swap_total')) }}</dd></div>
                        </dl>
                    </section>
                </div>
            </section>
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/apexcharts/apexcharts.js') }}"></script>
    <script src="{{ asset('js/dashboard-sidebar.js') }}?v=20260528-sidebar-toggle"></script>
    <script src="{{ asset('js/dashboard-controls.js') }}?v=20260529-shared-controls"></script>
    <script src="{{ asset('js/server-monitoring.js') }}?v=20260604-server-monitoring-dark"></script>
    @include('partials.realtime-alerts')
</body>
</html>
