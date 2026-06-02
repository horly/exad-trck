<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('map.title') }} - EXAD Tracking</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/mapbox/mapbox-gl.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=20260528-compact-ui">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260602-sidebar-version-global">
    <link rel="stylesheet" href="{{ asset('css/map.css') }}?v=20260602-mapbox-page">
</head>
<body class="app-font-manrope dashboard-body">
    <div class="dashboard-shell">
        @include('partials.sidebar', ['active' => 'map'])

        <main class="dashboard-main map-main">
            <header class="dashboard-topbar map-topbar">
                <div>
                    <p class="eyebrow mb-1">{{ __('map.eyebrow') }}</p>
                    <h1>{{ __('map.title') }}</h1>
                    <p class="map-subtitle">{{ __('map.subtitle') }}</p>
                </div>

                @include('partials.topbar-actions')
            </header>

            <section class="map-workspace" data-map-shell>
                <div id="trackingMap" class="tracking-map" aria-label="{{ __('map.title') }}"></div>

                <aside class="map-panel" aria-label="{{ __('map.filters') }}">
                    <div class="map-panel-header">
                        <div>
                            <span>{{ __('map.filters') }}</span>
                            <strong>{{ __('map.title') }}</strong>
                        </div>
                        <button type="button" class="icon-action" aria-label="{{ __('map.refresh') }}" data-map-refresh>
                            <i class="fa-solid fa-rotate"></i>
                        </button>
                    </div>

                    <div class="map-stats">
                        <div class="map-stat">
                            <span>{{ __('map.total') }}</span>
                            <strong data-map-count="total">{{ $summary['total'] }}</strong>
                        </div>
                        <div class="map-stat">
                            <span>{{ __('map.positioned') }}</span>
                            <strong data-map-count="positioned">{{ $summary['positioned'] }}</strong>
                        </div>
                        <div class="map-stat is-online">
                            <span>{{ __('map.online') }}</span>
                            <strong data-map-count="online">{{ $summary['online'] }}</strong>
                        </div>
                        <div class="map-stat is-inactive">
                            <span>{{ __('map.inactive') }}</span>
                            <strong data-map-count="inactive">{{ $summary['inactive'] }}</strong>
                        </div>
                    </div>

                    <div class="map-filter-grid">
                        <label class="map-filter">
                            <span>{{ __('trackers.status') }}</span>
                            <select class="form-select" data-map-status>
                                <option value="">{{ __('map.all_statuses') }}</option>
                                <option value="online">{{ __('trackers.status_online') }}</option>
                                <option value="inactive">{{ __('trackers.status_inactive') }}</option>
                                <option value="offline">{{ __('trackers.status_offline') }}</option>
                                <option value="maintenance">{{ __('trackers.status_maintenance') }}</option>
                            </select>
                        </label>

                        <label class="map-filter">
                            <span>{{ __('trackers.fleet') }}</span>
                            <select class="form-select" data-map-fleet>
                                <option value="">{{ __('map.all_fleets') }}</option>
                                @foreach ($fleets as $fleet)
                                    <option value="{{ $fleet->id }}">{{ $fleet->name }} · {{ $fleet->code }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="map-filter map-filter-full">
                            <span>{{ __('trackers.search') }}</span>
                            <div class="map-search">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="search" class="form-control" placeholder="{{ __('map.search') }}" data-map-search>
                            </div>
                        </label>
                    </div>

                    <div class="map-actions">
                        <button type="button" class="btn map-button-primary" data-map-fit>
                            <i class="fa-solid fa-crosshairs"></i>
                            <span>{{ __('map.fit_bounds') }}</span>
                        </button>
                        <label class="map-auto-toggle">
                            <input type="checkbox" checked data-map-auto>
                            <span>{{ __('map.live_refresh') }}</span>
                        </label>
                    </div>

                    <p class="map-last-update">
                        {{ __('map.last_update') }} :
                        <strong data-map-last-update>{{ __('map.never') }}</strong>
                    </p>
                </aside>

                <div class="map-empty" hidden data-map-empty>
                    <i class="fa-solid fa-location-crosshairs"></i>
                    <strong>{{ __('map.empty_title') }}</strong>
                    <span>{{ __('map.empty_text') }}</span>
                </div>
            </section>
        </main>
    </div>

    @php
        $mapConfig = [
            'token' => $mapboxToken,
            'devicesUrl' => route('map.devices'),
            'center' => $defaultCenter,
            'zoom' => $defaultZoom,
            'messages' => [
                'tokenMissing' => __('map.token_missing'),
                'mapUnavailable' => __('map.map_unavailable'),
                'vehicle' => __('map.popup_vehicle'),
                'tracker' => __('map.popup_tracker'),
                'fleet' => __('map.popup_fleet'),
                'speed' => __('map.popup_speed'),
                'lastSignal' => __('map.popup_last_signal'),
                'registration' => __('map.popup_registration'),
                'kmh' => __('map.kmh'),
            ],
        ];
    @endphp
    <script>
        window.exadMapConfig = {{ Illuminate\Support\Js::from($mapConfig) }};
    </script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/mapbox/mapbox-gl.js') }}"></script>
    <script src="{{ asset('js/dashboard-sidebar.js') }}?v=20260528-sidebar-toggle"></script>
    <script src="{{ asset('js/dashboard-controls.js') }}?v=20260529-shared-controls"></script>
    @include('partials.realtime-alerts')
    <script src="{{ asset('js/map.js') }}?v=20260602-mapbox-page"></script>
</body>
</html>
