@php
    $active = $active ?? '';
    $user = auth()->user();
    $homeRoute = $user->isSuperadmin() ? route('dashboard') : route('fleets.index');
    $fleetSectionActive = in_array($active, ['fleets', 'vehicles', 'trackers', 'drivers', 'departments', 'garages', 'maintenance'], true);
@endphp

<aside class="dashboard-sidebar">
    <div class="sidebar-brand">
        <a class="brand-mark" href="{{ $homeRoute }}" aria-label="EXAD Tracking">
            <img src="{{ asset('images/exad-cropped-white.png') }}" alt="EXAD Tracking">
        </a>
        <div>
            <strong>EXAD Tracking</strong>
            <span>{{ $user->isSuperadmin() ? __('dashboard.superadmin_console') : __('fleets.subscription') }}</span>
        </div>
    </div>

    <nav class="nav flex-column dashboard-nav" aria-label="{{ __('dashboard.main_navigation') }}">
        @if ($user->isSuperadmin())
            <a class="nav-link {{ $active === 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="fa-solid fa-gauge-high"></i>
                <span>{{ __('dashboard.title') }}</span>
            </a>
            <a class="nav-link {{ $active === 'users' ? 'active' : '' }}" href="{{ route('users.index') }}">
                <i class="fa-solid fa-users"></i>
                <span>{{ __('dashboard.users') }}</span>
            </a>
            <a class="nav-link {{ $active === 'subscriptions' ? 'active' : '' }}" href="{{ route('subscriptions.index') }}">
                <i class="fa-solid fa-layer-group"></i>
                <span>{{ __('dashboard.subscriptions') }}</span>
            </a>
            <div class="sidebar-nav-group {{ $fleetSectionActive ? 'is-open' : '' }}" data-sidebar-menu>
                <button
                    type="button"
                    class="nav-link sidebar-nav-group-toggle {{ $fleetSectionActive ? 'active' : '' }}"
                    aria-expanded="{{ $fleetSectionActive ? 'true' : 'false' }}"
                    data-sidebar-menu-toggle
                >
                    <i class="fa-solid fa-truck-fast"></i>
                    <span>{{ __('dashboard.fleet') }}</span>
                    <i class="fa-solid fa-chevron-down sidebar-nav-group-chevron"></i>
                </button>
                <div class="sidebar-nav-submenu" data-sidebar-submenu>
                    <a class="nav-link {{ $active === 'fleets' ? 'active' : '' }}" href="{{ route('fleets.index') }}">
                        <i class="fa-solid fa-warehouse"></i>
                        <span>{{ __('dashboard.fleet') }}</span>
                    </a>
                    <a class="nav-link {{ $active === 'vehicles' ? 'active' : '' }}" href="{{ route('vehicles.index') }}">
                        <i class="fa-solid fa-car-side"></i>
                        <span>{{ __('dashboard.vehicle') }}</span>
                    </a>
                    <a class="nav-link {{ $active === 'trackers' ? 'active' : '' }}" href="{{ route('trackers.index') }}">
                        <i class="fa-solid fa-satellite-dish"></i>
                        <span>{{ __('dashboard.trackers') }}</span>
                    </a>
                    <a class="nav-link {{ $active === 'drivers' ? 'active' : '' }}" href="{{ route('drivers.index') }}">
                        <i class="fa-solid fa-id-card"></i>
                        <span>{{ __('dashboard.drivers') }}</span>
                    </a>
                    <a class="nav-link {{ $active === 'departments' ? 'active' : '' }}" href="{{ route('departments.index') }}">
                        <i class="fa-solid fa-sitemap"></i>
                        <span>{{ __('dashboard.departments') }}</span>
                    </a>
                    <a class="nav-link {{ $active === 'garages' ? 'active' : '' }}" href="{{ route('garages.index') }}">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                        <span>{{ __('dashboard.garages') }}</span>
                    </a>
                    <a class="nav-link {{ $active === 'maintenance' ? 'active' : '' }}" href="{{ route('maintenance.index') }}">
                        <i class="fa-solid fa-clipboard-check"></i>
                        <span>{{ __('dashboard.maintenance') }}</span>
                    </a>
                </div>
            </div>
        @endif
        @unless ($user->isSuperadmin())
            <a class="nav-link {{ $active === 'fleets' ? 'active' : '' }}" href="{{ route('fleets.index') }}">
                <i class="fa-solid fa-truck-fast"></i>
                <span>{{ __('dashboard.fleet') }}</span>
            </a>
        @endunless
        <a class="nav-link {{ $active === 'map' ? 'active' : '' }}" href="{{ route('map.index') }}">
            <i class="fa-solid fa-map-location-dot"></i>
            <span>{{ __('dashboard.map') }}</span>
        </a>
        <a class="nav-link {{ $active === 'alerts' ? 'active' : '' }}" href="{{ route('alerts.index') }}">
            <i class="fa-solid fa-bell"></i>
            <span>{{ __('dashboard.alerts') }}</span>
        </a>
        @if ($user->isSuperadmin())
            <a class="nav-link {{ $active === 'alert-rules' ? 'active' : '' }}" href="{{ route('alert-rules.index') }}">
                <i class="fa-solid fa-sliders"></i>
                <span>{{ __('alert_rules.sidebar') }}</span>
            </a>
            <a class="nav-link {{ $active === 'reports' ? 'active' : '' }}" href="{{ route('reports.index') }}">
                <i class="fa-solid fa-file-lines"></i>
                <span>{{ __('dashboard.reports') }}</span>
            </a>
            <a class="nav-link {{ $active === 'server-logs' ? 'active' : '' }}" href="{{ route('server-logs.index') }}">
                <i class="fa-solid fa-terminal"></i>
                <span>{{ __('dashboard.server_logs') }}</span>
            </a>
            <a class="nav-link {{ $active === 'server-monitoring' ? 'active' : '' }}" href="{{ route('server-monitoring.index') }}">
                <i class="fa-solid fa-chart-line"></i>
                <span>{{ __('dashboard.server_monitoring') }}</span>
            </a>
        @endif
        <a class="nav-link {{ $active === 'customization' ? 'active' : '' }}" href="{{ route('customization.index') }}">
            <i class="fa-solid fa-sliders"></i>
            <span>{{ __('dashboard.customization') }}</span>
        </a>
    </nav>

    <div class="sidebar-version">
        <i class="fa-solid fa-shield-halved"></i>
        <span class="sidebar-version-full">{{ __('dashboard.version') }}</span>
        <span class="sidebar-version-compact">v.1.0</span>
    </div>
</aside>
