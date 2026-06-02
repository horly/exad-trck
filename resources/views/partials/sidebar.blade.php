@php
    $active = $active ?? '';
    $user = auth()->user();
    $homeRoute = $user->isSuperadmin() ? route('dashboard') : route('fleets.index');
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

    <button class="sidebar-toggle" type="button" aria-label="{{ __('dashboard.sidebar_toggle') }}" data-sidebar-toggle>
        <i class="fa-solid fa-chevron-left" data-sidebar-toggle-icon></i>
    </button>

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
        @endif

        <a class="nav-link {{ $active === 'fleets' ? 'active' : '' }}" href="{{ route('fleets.index') }}">
            <i class="fa-solid fa-truck-fast"></i>
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
        <a class="nav-link {{ $active === 'map' ? 'active' : '' }}" href="{{ route('map.index') }}">
            <i class="fa-solid fa-map-location-dot"></i>
            <span>{{ __('dashboard.map') }}</span>
        </a>
        <a class="nav-link {{ $active === 'alerts' ? 'active' : '' }}" href="{{ route('alerts.index') }}">
            <i class="fa-solid fa-bell"></i>
            <span>{{ __('dashboard.alerts') }}</span>
        </a>
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
