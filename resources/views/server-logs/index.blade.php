<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('server_logs.title') }} - EXAD Tracking</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=20260528-compact-ui">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260604-server-logs">
</head>
<body class="app-font-manrope dashboard-body">
    <div class="dashboard-shell">
        @include('partials.sidebar', ['active' => 'server-logs'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <p class="eyebrow mb-1">{{ __('server_logs.eyebrow') }}</p>
                    <h1>{{ __('server_logs.title') }}</h1>
                    <p class="dashboard-breadcrumb">{{ __('server_logs.breadcrumb') }}</p>
                </div>

                @include('partials.topbar-actions')
            </header>

            <section
                class="server-logs-panel"
                data-server-logs
                data-endpoint="{{ route('server-logs.content') }}"
                data-selected="{{ $selected }}"
                data-lines="{{ $defaultLines }}"
                data-live-label="{{ __('server_logs.live') }}"
                data-paused-label="{{ __('server_logs.paused') }}"
                data-error-label="{{ __('server_logs.loading_error') }}"
            >
                <div class="server-logs-toolbar">
                    <div class="server-logs-tabs" role="tablist" aria-label="{{ __('server_logs.tabs_label') }}">
                        @foreach ($logs as $key => $log)
                            <button
                                type="button"
                                class="server-log-tab {{ $selected === $key ? 'active' : '' }}"
                                data-log-key="{{ $key }}"
                                aria-pressed="{{ $selected === $key ? 'true' : 'false' }}"
                            >
                                <i class="fa-solid {{ $log['icon'] }}"></i>
                                <span>{{ $log['label'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="server-logs-actions">
                        <label class="server-logs-lines">
                            <span>{{ __('server_logs.lines') }}</span>
                            <select class="form-select" data-log-lines>
                                @foreach ([100, 300, 600, 1000, 1500] as $lines)
                                    <option value="{{ $lines }}" @selected($lines === $defaultLines)>{{ $lines }}</option>
                                @endforeach
                            </select>
                        </label>

                        <button type="button" class="server-log-button" data-log-refresh>
                            <i class="fa-solid fa-rotate-right"></i>
                            <span>{{ __('server_logs.refresh') }}</span>
                        </button>

                        <button type="button" class="server-log-button" data-log-pause data-pause-label="{{ __('server_logs.pause') }}" data-resume-label="{{ __('server_logs.resume') }}">
                            <i class="fa-solid fa-pause"></i>
                            <span>{{ __('server_logs.pause') }}</span>
                        </button>
                    </div>
                </div>

                <div class="server-logs-status">
                    <span class="server-log-pill is-live" data-log-state>
                        <i class="fa-solid fa-circle"></i>
                        <span>{{ __('server_logs.live') }}</span>
                    </span>
                    <span data-log-meta>{{ __('server_logs.waiting') }}</span>
                </div>

                <pre class="server-log-terminal" data-log-output aria-live="polite">{{ __('server_logs.loading') }}</pre>
            </section>
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/dashboard-sidebar.js') }}?v=20260528-sidebar-toggle"></script>
    <script src="{{ asset('js/dashboard-controls.js') }}?v=20260529-shared-controls"></script>
    <script src="{{ asset('js/server-logs.js') }}?v=20260604-server-logs"></script>
    @include('partials.realtime-alerts')
</body>
</html>
