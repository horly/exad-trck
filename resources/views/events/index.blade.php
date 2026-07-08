<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('events.title') }} - EXAD Tracking</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=20260528-compact-ui">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260708-dashboard-order-scope">
</head>
<body class="app-font-manrope dashboard-body">
    <div class="dashboard-shell">
        @include('partials.sidebar', ['active' => 'trackers'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                @include('partials.sidebar-toggle')
                <div>
                    <p class="eyebrow mb-1">{{ __('events.eyebrow') }}</p>
                    <h1>{{ __('events.title') }}</h1>
                    <p class="dashboard-subtitle mb-0">
                        {{ __('events.context', [
                            'vehicle' => $selectedDevice->vehicle?->name ?: __('events.unknown_vehicle'),
                            'tracker' => $selectedDevice->name ?: $selectedDevice->imei,
                        ]) }}
                    </p>
                </div>

                @include('partials.topbar-actions')
            </header>

            <section class="alerts-summary-grid" aria-label="{{ __('events.title') }}">
                <article class="alert-summary-card">
                    <span class="alert-summary-icon alert-summary-total"><i class="fa-solid fa-timeline"></i></span>
                    <strong>{{ $stats['total'] }}</strong>
                    <small>{{ __('events.total_count') }}</small>
                </article>
                <article class="alert-summary-card">
                    <span class="alert-summary-icon alert-summary-new"><i class="fa-solid fa-calendar-day"></i></span>
                    <strong>{{ $stats['today'] }}</strong>
                    <small>{{ __('events.today_count') }}</small>
                </article>
                <article class="alert-summary-card">
                    <span class="alert-summary-icon alert-summary-critical"><i class="fa-solid fa-route"></i></span>
                    <strong>{{ $stats['movement'] }}</strong>
                    <small>{{ __('events.movement_count') }}</small>
                </article>
                <article class="alert-summary-card">
                    <span class="alert-summary-icon alert-summary-high"><i class="fa-solid fa-shield-halved"></i></span>
                    <strong>{{ $stats['security'] }}</strong>
                    <small>{{ __('events.security_count') }}</small>
                </article>
            </section>

            <div data-datatable-container>
                @include('events.partials.table')
            </div>
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/dashboard-sidebar.js') }}?v=20260626-responsive-sidebar-default"></script>
    <script src="{{ asset('js/dashboard-controls.js') }}?v=20260529-shared-controls"></script>
    <script src="{{ asset('js/datatable-controls.js') }}?v=20260619-events-page"></script>
    @include('partials.realtime-alerts')
</body>
</html>
