<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('dashboard.client_dashboard_title') }} - {{ $applicationSettings->app_name }}</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=20260528-compact-ui">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260721-client-preview-icons">
</head>
<body class="app-font-manrope dashboard-body">
    <div class="dashboard-shell">
        @include('partials.sidebar', ['active' => 'dashboard'])

        <main class="dashboard-main dashboard-home-main client-dashboard-main">
            <header class="dashboard-topbar">
                @include('partials.sidebar-toggle')
                <div>
                    <h1>{{ __('dashboard.client_dashboard_title') }}</h1>
                    <p class="dashboard-breadcrumb">{{ __('dashboard.client_breadcrumb', ['fleet' => $fleet?->name ?? '-']) }}</p>
                </div>
                @include('partials.topbar-actions')
            </header>

            @if ($isSuperadminPreview ?? false)
                <section class="client-preview-bar" aria-label="{{ __('dashboard.client_preview_label') }}">
                    <div>
                        <span class="client-preview-icon"><i class="fa-solid fa-user-shield"></i></span>
                        <p>
                            <span>{{ __('dashboard.client_preview_label') }}</span>
                            <strong>{{ __('dashboard.client_preview_description', ['fleet' => $fleet?->name ?? '-']) }}</strong>
                        </p>
                    </div>
                    <form method="POST" action="{{ route('client-preview.exit') }}" data-client-preview-exit>
                        @csrf
                        <button class="btn client-preview-back" type="submit">
                            <i class="fa-solid fa-arrow-left"></i>
                            <span>{{ __('dashboard.client_preview_back') }}</span>
                        </button>
                    </form>
                </section>
            @endif

            @php
                $clientMetrics = [
                    ['tone' => 'blue', 'icon' => 'fa-car-side', 'value' => $summary['vehicles_total'], 'label' => __('dashboard.client_total_vehicles'), 'detail' => __('dashboard.client_total_vehicles_detail')],
                    ['tone' => 'green', 'icon' => 'fa-signal', 'value' => $summary['vehicles_online'], 'label' => __('dashboard.client_online_vehicles'), 'detail' => __('dashboard.client_online_vehicles_detail')],
                    ['tone' => 'purple', 'icon' => 'fa-route', 'value' => $summary['vehicles_moving'], 'label' => __('dashboard.client_moving_vehicles'), 'detail' => __('dashboard.client_moving_vehicles_detail')],
                    ['tone' => 'red', 'icon' => 'fa-triangle-exclamation', 'value' => $summary['vehicles_attention'], 'label' => __('dashboard.client_attention_vehicles'), 'detail' => __('dashboard.client_attention_vehicles_detail')],
                ];
            @endphp

            <section class="admin-metrics client-dashboard-metrics" aria-label="{{ __('dashboard.client_fleet_indicators') }}">
                @foreach ($clientMetrics as $metric)
                    <article class="admin-metric-card metric-{{ $metric['tone'] }}-soft metric-modern-card">
                        <div class="metric-card-top">
                            <span class="metric-icon"><i class="fa-solid {{ $metric['icon'] }}"></i></span>
                            <span class="metric-kicker">{{ $fleet?->code ?? __('dashboard.client_console') }}</span>
                        </div>
                        <strong>{{ number_format($metric['value']) }}</strong>
                        <p>{{ $metric['label'] }}</p>
                        <div class="metric-foot"><span>{{ $metric['detail'] }}</span></div>
                    </article>
                @endforeach
            </section>

            <section class="admin-panel client-fleet-status-panel">
                <div class="panel-heading latest-trackers-heading">
                    <div class="latest-trackers-title">
                        <span class="latest-trackers-icon"><i class="fa-solid fa-car-side"></i></span>
                        <div>
                            <p class="eyebrow mb-1">{{ __('dashboard.client_fleet_overview') }}</p>
                            <h2>{{ __('dashboard.client_vehicle_status') }}</h2>
                        </div>
                    </div>
                    <a class="btn client-panel-link" href="{{ route('vehicles.index') }}">
                        <i class="fa-solid fa-list"></i>
                        <span>{{ __('dashboard.client_view_all_vehicles') }}</span>
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle dashboard-table latest-trackers-table">
                        <thead>
                            <tr>
                                <th>{{ __('dashboard.client_vehicle') }}</th>
                                <th>{{ __('dashboard.client_registration') }}</th>
                                <th>{{ __('dashboard.client_tracking_status') }}</th>
                                <th>{{ __('dashboard.speed') }}</th>
                                <th>{{ __('dashboard.client_last_update') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vehicles as $vehicle)
                                @php
                                    $device = $vehicle->device;
                                    $isOnline = $device?->status === 'online';
                                @endphp
                                <tr>
                                    <td>
                                        <div class="tracker-cell">
                                            <span class="tracker-avatar"><i class="fa-solid fa-car"></i></span>
                                            <div class="tracker-main">
                                                <strong>{{ $vehicle->name }}</strong>
                                                <span class="tracker-submeta">{{ trim(($vehicle->brand ?: '').' '.($vehicle->model ?: '')) ?: __('vehicles.no_model') }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><strong>{{ $vehicle->registration_number }}</strong></td>
                                    <td>
                                        @if ($device)
                                            <span class="status-pill latest-status status-{{ $isOnline ? 'online' : 'offline' }}">
                                                <span aria-hidden="true"></span>
                                                {{ $isOnline ? __('dashboard.client_status_online') : __('dashboard.client_status_offline') }}
                                            </span>
                                        @else
                                            <span class="status-pill latest-status status-inactive">
                                                <span aria-hidden="true"></span>
                                                {{ __('dashboard.client_status_unavailable') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="metric-chip speed-chip">
                                            <i class="fa-solid fa-gauge-high"></i>
                                            {{ (int) ($device?->last_speed ?? 0) }} km/h
                                        </span>
                                    </td>
                                    <td>
                                        <span class="metric-chip signal-chip">
                                            <i class="fa-regular fa-clock"></i>
                                            {{ $device?->last_seen_at?->diffForHumans() ?? __('dashboard.client_no_update') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="empty-state">{{ __('dashboard.client_no_vehicles') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="client-dashboard-lower-grid">
                <article class="admin-panel client-alerts-panel">
                    <div class="panel-heading">
                        <div>
                            <p class="eyebrow mb-1">{{ __('dashboard.client_monitoring') }}</p>
                            <h2>{{ __('dashboard.client_recent_alerts') }}</h2>
                        </div>
                        <a class="icon-action" href="{{ route('alerts.index') }}" aria-label="{{ __('dashboard.alerts') }}"><i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                    <div class="client-alert-list">
                        @forelse ($recentAlerts as $alert)
                            <a href="{{ route('alerts.index') }}" class="client-alert-item">
                                <span class="client-alert-icon client-alert-{{ $alert->severity }}"><i class="fa-solid fa-bell"></i></span>
                                <span>
                                    <strong>{{ $alert->localizedTitle() }}</strong>
                                    <small>{{ $alert->vehicle?->name ?? __('alerts.unknown_vehicle') }} &middot; {{ $alert->occurred_at?->diffForHumans() }}</small>
                                </span>
                            </a>
                        @empty
                            <div class="client-panel-empty"><i class="fa-solid fa-circle-check"></i><span>{{ __('dashboard.client_no_recent_alerts') }}</span></div>
                        @endforelse
                    </div>
                </article>

                <article class="admin-panel client-actions-panel">
                    <div class="panel-heading">
                        <div>
                            <p class="eyebrow mb-1">{{ __('dashboard.client_workspace') }}</p>
                            <h2>{{ __('dashboard.client_quick_actions') }}</h2>
                        </div>
                    </div>
                    <div class="client-quick-actions">
                        <a href="{{ route('vehicles.index') }}"><span><i class="fa-solid fa-car-side"></i></span><strong>{{ __('dashboard.client_manage_vehicles') }}</strong><i class="fa-solid fa-chevron-right"></i></a>
                        @if ($canViewMap)
                            <a href="{{ route('map.index') }}"><span><i class="fa-solid fa-map-location-dot"></i></span><strong>{{ __('dashboard.client_open_map') }}</strong><i class="fa-solid fa-chevron-right"></i></a>
                        @endif
                        @if ($canGenerateReports)
                            <a href="{{ route('reports.index') }}"><span><i class="fa-solid fa-file-lines"></i></span><strong>{{ __('dashboard.client_generate_report') }}</strong><i class="fa-solid fa-chevron-right"></i></a>
                        @endif
                        @if ($canManageMaintenance)
                            <a href="{{ route('maintenance.index') }}"><span><i class="fa-solid fa-clipboard-check"></i></span><strong>{{ __('dashboard.client_open_maintenance') }}</strong><i class="fa-solid fa-chevron-right"></i></a>
                        @endif
                    </div>
                </article>
            </section>
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/dashboard-sidebar.js') }}?v=20260626-responsive-sidebar-default"></script>
    <script src="{{ asset('js/dashboard-controls.js') }}?v=20260529-shared-controls"></script>
    @include('partials.realtime-alerts')
</body>
</html>
