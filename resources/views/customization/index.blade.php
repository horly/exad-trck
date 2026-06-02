<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('customization.title') }} - EXAD Tracking</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=20260528-compact-ui">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260602-sidebar-version-global">
</head>
<body class="app-font-manrope dashboard-body">
    <div class="dashboard-shell">
        @include('partials.sidebar', ['active' => 'customization'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <p class="eyebrow mb-1">{{ __('customization.eyebrow') }}</p>
                    <h1>{{ __('customization.title') }}</h1>
                    <p class="dashboard-breadcrumb">{{ __('customization.breadcrumb') }}</p>
                </div>

                @include('partials.topbar-actions')
            </header>

            <section class="admin-panel">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow mb-1">{{ __('customization.panel_eyebrow') }}</p>
                        <h2>{{ __('customization.panel_title') }}</h2>
                    </div>
                </div>

                <div class="empty-state">
                    <strong>{{ __('customization.empty_title') }}</strong>
                    <span>{{ __('customization.empty_text') }}</span>
                </div>
            </section>
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/dashboard-sidebar.js') }}?v=20260528-sidebar-toggle"></script>
    <script src="{{ asset('js/dashboard-controls.js') }}?v=20260529-shared-controls"></script>
    @include('partials.realtime-alerts')
</body>
</html>
