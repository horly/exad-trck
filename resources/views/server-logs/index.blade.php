<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('server_logs.title') }} - EXAD Tracking</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=20260528-compact-ui">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260719-server-console-tools">
    <link rel="stylesheet" href="{{ asset('vendor/server-console/server-console.css') }}?v=20260719-server-console-tools">
</head>
<body class="app-font-manrope dashboard-body">
    <div class="dashboard-shell">
        @include('partials.sidebar', ['active' => 'server-logs'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                @include('partials.sidebar-toggle')
                <div>
                    <p class="eyebrow mb-1">{{ __('server_logs.eyebrow') }}</p>
                    <h1>{{ __('server_logs.title') }}</h1>
                    <p class="dashboard-breadcrumb">{{ __('server_logs.breadcrumb') }}</p>
                </div>

                @include('partials.topbar-actions')
            </header>

            <section class="server-operations" data-server-operations>
                <div class="server-operation-tabs" role="tablist" aria-label="{{ __('server_logs.operation_tabs_label') }}">
                    <button type="button" class="server-operation-tab active" role="tab" aria-selected="true" data-server-section="logs">
                        <i class="fa-solid fa-file-waveform"></i><span>{{ __('server_logs.logs_tab') }}</span>
                    </button>
                    <button type="button" class="server-operation-tab" role="tab" aria-selected="false" data-server-section="console">
                        <i class="fa-solid fa-terminal"></i><span>{{ __('server_logs.console_tab') }}</span>
                    </button>
                </div>

                <div data-server-section-panel="logs">
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
                                    <button type="button" class="server-log-tab {{ $selected === $key ? 'active' : '' }}" data-log-key="{{ $key }}" aria-pressed="{{ $selected === $key ? 'true' : 'false' }}">
                                        <i class="fa-solid {{ $log['icon'] }}"></i><span>{{ $log['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>

                            <div class="server-logs-actions">
                                <label class="server-logs-lines"><span>{{ __('server_logs.lines') }}</span><select class="form-select" data-log-lines>@foreach ([100, 300, 600, 1000, 1500] as $lines)<option value="{{ $lines }}" @selected($lines === $defaultLines)>{{ $lines }}</option>@endforeach</select></label>
                                <button type="button" class="server-log-button" data-log-refresh><i class="fa-solid fa-rotate-right"></i><span>{{ __('server_logs.refresh') }}</span></button>
                                <button type="button" class="server-log-button" data-log-pause data-pause-label="{{ __('server_logs.pause') }}" data-resume-label="{{ __('server_logs.resume') }}"><i class="fa-solid fa-pause"></i><span>{{ __('server_logs.pause') }}</span></button>
                            </div>
                        </div>

                        <div class="server-logs-status">
                            <span class="server-log-pill is-live" data-log-state><i class="fa-solid fa-circle"></i><span>{{ __('server_logs.live') }}</span></span>
                            <span data-log-meta>{{ __('server_logs.waiting') }}</span>
                        </div>

                        <pre class="server-log-terminal" data-log-output aria-live="polite">{{ __('server_logs.loading') }}</pre>
                    </section>
                </div>

                <div data-server-section-panel="console" hidden>
                    <section
                        class="server-console-panel"
                        data-server-console
                        data-enabled="{{ config('server_console.enabled') ? 'true' : 'false' }}"
                        data-ticket-endpoint="{{ route('server-logs.console-ticket') }}"
                        data-disconnected-label="{{ __('server_logs.console_disconnected') }}"
                        data-connecting-label="{{ __('server_logs.console_connecting') }}"
                        data-connected-label="{{ __('server_logs.console_connected') }}"
                        data-authentication-error="{{ __('server_logs.console_authentication_error') }}"
                        data-unavailable-label="{{ __('server_logs.console_unavailable') }}"
                        data-fullscreen-label="{{ __('server_logs.console_fullscreen') }}"
                        data-exit-fullscreen-label="{{ __('server_logs.console_exit_fullscreen') }}"
                    >
                        <div class="server-console-toolbar">
                            <span class="server-console-state is-disconnected" data-console-state><i class="fa-solid fa-circle"></i><span>{{ __('server_logs.console_disconnected') }}</span></span>
                            <div class="server-console-actions">
                                <button type="button" class="server-log-button server-console-icon-button" data-console-fullscreen title="{{ __('server_logs.console_fullscreen') }}" aria-label="{{ __('server_logs.console_fullscreen') }}"><i class="fa-solid fa-expand"></i></button>
                                <button type="button" class="server-log-button" data-console-connect><i class="fa-solid fa-plug"></i><span>{{ __('server_logs.console_connect') }}</span></button>
                                <button type="button" class="server-log-button server-console-disconnect" data-console-disconnect hidden><i class="fa-solid fa-power-off"></i><span>{{ __('server_logs.console_disconnect') }}</span></button>
                            </div>
                        </div>
                        <div class="server-console-terminal" data-console-terminal aria-label="{{ __('server_logs.console_terminal_label') }}"></div>
                    </section>
                </div>
            </section>
        </main>
    </div>

    <div class="modal fade users-modal" id="serverConsoleAuthModal" tabindex="-1" aria-labelledby="serverConsoleAuthTitle" aria-hidden="true" data-console-auth-modal>
        <div class="modal-dialog modal-dialog-centered users-modal-dialog server-console-auth-dialog">
            <div class="modal-content">
                <form data-console-auth-form autocomplete="off">
                    <div class="modal-header">
                        <div class="form-modal-heading"><span class="form-modal-heading-icon"><i class="fa-solid fa-terminal"></i></span><h2 class="modal-title" id="serverConsoleAuthTitle">{{ __('server_logs.console_auth_title') }}</h2></div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('server_logs.console_cancel') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="users-form-grid">
                            <div class="users-form-full"><label for="server_console_username" class="form-label">{{ __('server_logs.console_username') }}</label><input id="server_console_username" class="form-control" name="username" required autocomplete="off" spellcheck="false" data-console-username></div>
                            <div class="users-form-full"><label for="server_console_password" class="form-label">{{ __('server_logs.console_password') }}</label><input id="server_console_password" class="form-control" type="password" name="password" required autocomplete="off" data-console-password></div>
                            <div class="users-form-full"><p class="server-console-auth-error" data-console-auth-error hidden></p></div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn users-cancel-button" data-bs-dismiss="modal">{{ __('server_logs.console_cancel') }}</button><button type="submit" class="btn btn-primary" data-console-auth-submit><i class="fa-solid fa-lock-open"></i><span>{{ __('server_logs.console_open') }}</span></button></div>
                </form>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/dashboard-sidebar.js') }}?v=20260626-responsive-sidebar-default"></script>
    <script src="{{ asset('js/dashboard-controls.js') }}?v=20260529-shared-controls"></script>
    <script src="{{ asset('js/server-logs.js') }}?v=20260604-server-logs"></script>
    <script type="module" src="{{ asset('vendor/server-console/server-console.js') }}?v=20260719-server-console-tools"></script>
    @include('partials.realtime-alerts')
</body>
</html>
