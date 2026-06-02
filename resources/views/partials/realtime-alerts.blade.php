@if (auth()->check() && auth()->user()->isSuperadmin())
    @php
        $realtimeAlertConfig = [
            'key' => config('broadcasting.connections.reverb.key'),
            'host' => config('broadcasting.connections.reverb.options.host'),
            'port' => config('broadcasting.connections.reverb.options.port'),
            'scheme' => config('broadcasting.connections.reverb.options.scheme'),
            'authEndpoint' => url('/broadcasting/auth'),
            'channel' => 'private-superadmin.alerts',
            'event' => 'alert.created',
            'csrfToken' => csrf_token(),
            'alertsIndexEndpoint' => route('alerts.index'),
            'recentEndpoint' => route('alerts.recent'),
            'latestAlertId' => (int) \App\Models\Alert::query()->visibleTo(auth()->user())->max('id'),
            'pollInterval' => 5000,
        ];
    @endphp

    <div class="app-toast app-toast-info alert-live-toast" role="status" aria-live="polite" hidden data-alert-live-toast>
        <span class="app-toast-icon" aria-hidden="true">
            <i class="fa-solid fa-bell"></i>
        </span>
        <span class="app-toast-message">
            <strong>{{ __('alerts.toast_title') }}</strong>
            <em data-alert-live-message></em>
        </span>
        <button type="button" class="app-toast-close" aria-label="{{ __('alerts.close_notification') }}" data-alert-live-close>
            <i class="fa-solid fa-xmark"></i>
        </button>
        <span class="app-toast-progress" aria-hidden="true"></span>
    </div>

    <script>
        window.exadRealtimeConfig = @json($realtimeAlertConfig);
        window.exadAlertLabels = {
            connected: @json(__('alerts.realtime_connected')),
            connecting: @json(__('alerts.realtime_connecting')),
            unavailable: @json(__('alerts.realtime_unavailable')),
        };
    </script>
    <script src="{{ asset('js/alerts-realtime.js') }}?v=20260602-global-realtime-alerts"></script>
@endif
