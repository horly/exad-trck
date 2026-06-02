<div class="topbar-actions">
    <button class="icon-button" type="button" aria-label="{{ __('dashboard.fullscreen') }}" data-fullscreen-toggle>
        <i class="fa-solid fa-expand" data-fullscreen-icon></i>
    </button>

    <button class="icon-button theme-toggle" type="button" aria-label="{{ __('dashboard.theme') }}" data-theme-toggle>
        <i class="fa-solid fa-sun" data-theme-icon></i>
    </button>

    @if (auth()->user()->isSuperadmin())
        <a class="icon-button alert-notification-button" href="{{ route('alerts.index') }}" aria-label="{{ __('dashboard.alert_notifications') }}">
            <i class="fa-regular fa-bell"></i>
            <span class="alert-notification-badge {{ $newAlertsCount > 0 ? '' : 'is-hidden' }}" data-alert-notification-count aria-label="{{ trans_choice('dashboard.alert_notifications_count', $newAlertsCount, ['count' => $newAlertsCount]) }}">{{ $newAlertsCount > 99 ? '99+' : $newAlertsCount }}</span>
        </a>
    @endif

    <div class="dropdown">
        <button class="pill-button dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa-solid fa-globe"></i>
            <span>{{ strtoupper(app()->getLocale()) }}</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end dashboard-language-menu">
            <li>
                <a class="dashboard-language-option {{ app()->getLocale() === 'fr' ? 'active' : '' }}" href="{{ route('lang.switch', 'fr') }}">
                    <span class="dashboard-language-code">FR</span>
                    <strong>{{ __('auth.language_fr') }}</strong>
                    @if (app()->getLocale() === 'fr')
                        <i class="fa-solid fa-check"></i>
                    @endif
                </a>
            </li>
            <li>
                <a class="dashboard-language-option {{ app()->getLocale() === 'en' ? 'active' : '' }}" href="{{ route('lang.switch', 'en') }}">
                    <span class="dashboard-language-code">EN</span>
                    <strong>{{ __('auth.language_en') }}</strong>
                    @if (app()->getLocale() === 'en')
                        <i class="fa-solid fa-check"></i>
                    @endif
                </a>
            </li>
        </ul>
    </div>

    <div class="dropdown">
        <button class="user-pill dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
            <strong>{{ auth()->user()->name }}</strong>
        </button>
        <div class="dropdown-menu dropdown-menu-end user-menu">
            <div class="user-menu-header">
                <strong>{{ auth()->user()->name }}</strong>
                <span>{{ auth()->user()->email }}</span>
                <em>{{ auth()->user()->isSuperadmin() ? __('dashboard.superadmin_badge') : strtoupper(auth()->user()->role->value) }}</em>
            </div>

            <div class="user-menu-links">
                @if (auth()->user()->isSuperadmin())
                    <a href="{{ route('dashboard') }}" class="user-menu-link">
                        <i class="fa-solid fa-gauge-high"></i>
                        <span>{{ __('dashboard.title') }}</span>
                    </a>
                @endif
                <a href="#" class="user-menu-link">
                    <i class="fa-solid fa-circle-user"></i>
                    <span>{{ __('dashboard.profile') }}</span>
                </a>
                @if (auth()->user()->isSuperadmin())
                    <a href="{{ route('users.index') }}" class="user-menu-link">
                        <i class="fa-solid fa-users"></i>
                        <span>{{ __('dashboard.user_management') }}</span>
                    </a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="user-menu-link user-menu-button">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        <span>{{ __('dashboard.logout') }}</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
