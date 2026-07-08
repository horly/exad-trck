<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('map.title') }} - EXAD Tracking</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    @if ($mapProvider === 'mapbox')
        <link rel="stylesheet" href="{{ asset('vendor/mapbox/mapbox-gl.css') }}">
    @endif
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=20260528-compact-ui">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260708-dashboard-order-scope">
    <link rel="stylesheet" href="{{ asset('css/map.css') }}?v=20260708-map-marker-size">
</head>
<body class="app-font-manrope dashboard-body">
    <div class="dashboard-shell">
        @include('partials.sidebar', ['active' => 'map'])

        <main class="dashboard-main map-main">
            <header class="dashboard-topbar map-topbar">
                @include('partials.sidebar-toggle')
                <div>
                    <p class="eyebrow mb-1">{{ __('map.eyebrow') }}</p>
                    <h1>{{ __('map.title') }}</h1>
                    <p class="map-subtitle">{{ __('map.subtitle') }}</p>
                </div>

                @include('partials.topbar-actions')
            </header>

            <section class="map-workspace" data-map-shell>
                <div id="trackingMap" class="tracking-map" aria-label="{{ __('map.title') }}"></div>
                <button type="button" class="map-panel-toggle" aria-label="{{ __('map.show_filters') }}" data-map-panel-toggle>
                    <i class="fa-solid fa-sliders"></i>
                </button>

                <aside class="map-panel" aria-label="{{ __('map.filters') }}" data-map-panel>
                    <div class="map-panel-header">
                        <div>
                            <span>{{ __('map.filters') }}</span>
                            <strong>
                                <i class="fa-solid fa-map-location-dot"></i>
                                {{ __('map.title') }}
                            </strong>
                        </div>
                        <div class="map-panel-tools">
                            <button type="button" class="icon-action" aria-label="{{ __('map.refresh') }}" data-map-refresh>
                                <i class="fa-solid fa-rotate"></i>
                            </button>
                            <button type="button" class="icon-action" aria-label="{{ __('map.hide_filters') }}" data-map-panel-close>
                                <i class="fa-solid fa-chevron-left"></i>
                            </button>
                        </div>
                    </div>

                    <div class="map-stats">
                        <div class="map-stat">
                            <span class="map-stat-icon"><i class="fa-solid fa-satellite-dish"></i></span>
                            <span class="map-stat-label">{{ __('map.total') }}</span>
                            <strong data-map-count="total">{{ $summary['total'] }}</strong>
                        </div>
                        <div class="map-stat">
                            <span class="map-stat-icon"><i class="fa-solid fa-location-dot"></i></span>
                            <span class="map-stat-label">{{ __('map.positioned') }}</span>
                            <strong data-map-count="positioned">{{ $summary['positioned'] }}</strong>
                        </div>
                        <div class="map-stat is-online">
                            <span class="map-stat-icon"><i class="fa-solid fa-signal"></i></span>
                            <span class="map-stat-label">{{ __('map.online') }}</span>
                            <strong data-map-count="online">{{ $summary['online'] }}</strong>
                        </div>
                        <div class="map-stat is-inactive">
                            <span class="map-stat-icon"><i class="fa-solid fa-circle-xmark"></i></span>
                            <span class="map-stat-label">{{ __('map.inactive') }}</span>
                            <strong data-map-count="inactive">{{ $summary['inactive'] }}</strong>
                        </div>
                    </div>

                    <label class="map-visibility-toggle">
                        <input type="checkbox" data-map-show-all>
                        <i class="fa-solid fa-car-side"></i>
                        <span>{{ __('map.show_all_vehicles') }}</span>
                    </label>

                    <div class="map-filter-grid">
                        <label class="map-filter">
                            <span><i class="fa-solid fa-tower-broadcast"></i>{{ __('trackers.status') }}</span>
                            <select class="form-select" data-map-status>
                                <option value="">{{ __('map.all_statuses') }}</option>
                                <option value="online">{{ __('trackers.status_online') }}</option>
                                <option value="inactive">{{ __('trackers.status_inactive') }}</option>
                                <option value="offline">{{ __('trackers.status_offline') }}</option>
                                <option value="maintenance">{{ __('trackers.status_maintenance') }}</option>
                            </select>
                        </label>

                        <label class="map-filter">
                            <span><i class="fa-solid fa-layer-group"></i>{{ __('trackers.fleet') }}</span>
                            <select class="form-select" data-map-fleet>
                                <option value="">{{ __('map.all_fleets') }}</option>
                                @foreach ($fleets as $fleet)
                                    <option value="{{ $fleet->id }}">{{ $fleet->name }} · {{ $fleet->code }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="map-filter map-filter-full">
                            <span><i class="fa-solid fa-magnifying-glass-location"></i>{{ __('trackers.search') }}</span>
                            <div class="map-search">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="search" class="form-control" placeholder="{{ __('map.search') }}" data-map-search>
                            </div>
                        </label>
                    </div>

                    <div class="map-search-results" hidden data-map-results>
                        <div class="map-results-header">
                            <span>{{ __('map.search_results') }}</span>
                            <strong data-map-results-count>0</strong>
                        </div>
                        <div class="map-results-list" data-map-results-list></div>
                    </div>

                    <div class="map-actions">
                        <button type="button" class="btn map-button-primary" data-map-fit>
                            <i class="fa-solid fa-crosshairs"></i>
                            <span>{{ __('map.fit_bounds') }}</span>
                        </button>
                        <label class="map-auto-toggle">
                            <input type="checkbox" checked data-map-auto>
                            <i class="fa-solid fa-arrows-rotate"></i>
                            <span>{{ __('map.live_refresh') }}</span>
                        </label>
                    </div>

                    <p class="map-last-update">
                        <i class="fa-regular fa-clock"></i>
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

    @include('trackers.partials.details-modal')
    @include('trackers.partials.trips-modal')

    @php
        $mapConfig = [
            'provider' => $mapProvider,
            'token' => $mapboxToken,
            'googleApiKey' => $googleMapsApiKey,
            'devicesUrl' => route('map.devices'),
            'center' => $defaultCenter,
            'zoom' => $defaultZoom,
            'messages' => [
                'tokenMissing' => __('map.token_missing'),
                'googleKeyMissing' => __('map.google_key_missing'),
                'mapUnavailable' => __('map.map_unavailable'),
                'googleUnavailable' => __('map.google_unavailable'),
                'vehicle' => __('map.popup_vehicle'),
                'tracker' => __('map.popup_tracker'),
                'fleet' => __('map.popup_fleet'),
                'speed' => __('map.popup_speed'),
                'lastSignal' => __('map.popup_last_signal'),
                'registration' => __('map.popup_registration'),
                'noResults' => __('map.no_results'),
                'selectVehicle' => __('map.select_vehicle'),
                'details' => __('trackers.details'),
                'trips' => __('trackers.trips'),
                'kmh' => __('map.kmh'),
            ],
        ];
    @endphp
    <script>
        window.exadMapConfig = {{ Illuminate\Support\Js::from($mapConfig) }};
    </script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    @if ($mapProvider === 'mapbox')
        <script src="{{ asset('vendor/mapbox/mapbox-gl.js') }}"></script>
    @endif
    <script src="{{ asset('js/dashboard-sidebar.js') }}?v=20260626-responsive-sidebar-default"></script>
    <script src="{{ asset('js/dashboard-controls.js') }}?v=20260529-shared-controls"></script>
    @include('partials.realtime-alerts')
    <script src="{{ asset('js/tracker-details.js') }}?v=20260602-details-shared"></script>
    <script src="{{ asset('js/tracker-trips.js') }}?v=20260602-trips-shared"></script>
    @if ($mapProvider === 'google')
        <script src="{{ asset('js/google-map.js') }}?v=20260626-dashboard-city-filter"></script>
        @if ($googleMapsApiKey !== '')
            <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($googleMapsApiKey) }}&callback=initExadGoogleMap"></script>
        @endif
    @else
        <script src="{{ asset('js/map.js') }}?v=20260708-map-marker-size"></script>
    @endif
</body>
</html>
