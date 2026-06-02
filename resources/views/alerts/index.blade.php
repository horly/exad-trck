<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('alerts.title') }} - EXAD Tracking</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=20260528-compact-ui">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260602-sidebar-version-global">
</head>
<body class="app-font-manrope dashboard-body">
    <div class="dashboard-shell">
        @include('partials.sidebar', ['active' => 'alerts'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <p class="eyebrow mb-1">{{ __('alerts.eyebrow') }}</p>
                    <h1>{{ __('alerts.title') }}</h1>
                </div>

                @include('partials.topbar-actions')
            </header>

            <section class="alerts-summary-grid" aria-label="{{ __('alerts.title') }}">
                <article class="alert-summary-card">
                    <span class="alert-summary-icon alert-summary-total"><i class="fa-solid fa-bell"></i></span>
                    <strong data-alert-stat="total">{{ $stats['total'] }}</strong>
                    <small>{{ __('alerts.total_count') }}</small>
                </article>
                <article class="alert-summary-card">
                    <span class="alert-summary-icon alert-summary-new"><i class="fa-solid fa-circle-exclamation"></i></span>
                    <strong data-alert-stat="new">{{ $stats['new'] }}</strong>
                    <small>{{ __('alerts.new_count') }}</small>
                </article>
                <article class="alert-summary-card">
                    <span class="alert-summary-icon alert-summary-critical"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <strong data-alert-stat="critical">{{ $stats['critical'] }}</strong>
                    <small>{{ __('alerts.critical_count') }}</small>
                </article>
                <article class="alert-summary-card">
                    <span class="alert-summary-icon alert-summary-high"><i class="fa-solid fa-bolt"></i></span>
                    <strong data-alert-stat="high">{{ $stats['high'] }}</strong>
                    <small>{{ __('alerts.high_count') }}</small>
                </article>
            </section>

            <div data-datatable-container>
                @include('alerts.partials.table')
            </div>
        </main>
    </div>

    @if (session('status'))
        <div class="app-toast app-toast-success" role="status" aria-live="polite" data-app-toast>
            <span class="app-toast-icon" aria-hidden="true">
                <i class="fa-solid fa-check"></i>
            </span>
            <span class="app-toast-message">{{ session('status') }}</span>
            <button type="button" class="app-toast-close" aria-label="{{ __('alerts.close_notification') }}" data-app-toast-close>
                <i class="fa-solid fa-xmark"></i>
            </button>
            <span class="app-toast-progress" aria-hidden="true"></span>
        </div>
    @endif

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/dashboard-sidebar.js') }}?v=20260528-sidebar-toggle"></script>
    <script src="{{ asset('js/dashboard-controls.js') }}?v=20260529-shared-controls"></script>
    <script src="{{ asset('js/datatable-controls.js') }}?v=20260602-alert-stats"></script>
    <script src="{{ asset('js/form-loading.js') }}?v=20260529-form-loading"></script>
    @include('partials.realtime-alerts')
    <script>
        const alertToast = document.querySelector('[data-app-toast]');
        if (alertToast) {
            const hideToast = () => alertToast.classList.add('is-hiding');
            alertToast.querySelector('[data-app-toast-close]')?.addEventListener('click', hideToast);
            setTimeout(hideToast, 5200);
        }
    </script>
</body>
</html>
