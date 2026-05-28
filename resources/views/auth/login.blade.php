<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('auth.login_title') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth-login.css') }}">
</head>
<body>
    <main class="login-shell">
        <div class="top-actions">
        <div class="language-switch dropdown">
            <button class="language-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <svg aria-hidden="true" viewBox="0 0 24 24">
                    <path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z"/>
                    <path d="M3.6 9h16.8M3.6 15h16.8M12 3c2.1 2.2 3.1 5.2 3.1 9s-1 6.8-3.1 9c-2.1-2.2-3.1-5.2-3.1-9s1-6.8 3.1-9Z"/>
                </svg>
                <span>{{ strtoupper(app()->getLocale()) }}</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end language-menu">
                <li>
                    <a class="language-option {{ app()->getLocale() === 'fr' ? 'active' : '' }}" href="{{ route('lang.switch', 'fr') }}">
                        <span class="language-code">FR</span>
                        <strong>{{ __('auth.language_fr') }}</strong>
                        @if (app()->getLocale() === 'fr')
                            <svg class="language-check" aria-hidden="true" viewBox="0 0 24 24"><path d="m5 12 4 4 10-10"/></svg>
                        @endif
                    </a>
                </li>
                <li>
                    <a class="language-option {{ app()->getLocale() === 'en' ? 'active' : '' }}" href="{{ route('lang.switch', 'en') }}">
                        <span class="language-code">EN</span>
                        <strong>{{ __('auth.language_en') }}</strong>
                        @if (app()->getLocale() === 'en')
                            <svg class="language-check" aria-hidden="true" viewBox="0 0 24 24"><path d="m5 12 4 4 10-10"/></svg>
                        @endif
                    </a>
                </li>
            </ul>
        </div>
        </div>

        <section class="login-hero">
            <div class="hero-inner">
                <img class="hero-logo" src="{{ asset('images/logo-exad-cropped.png') }}" alt="EXAD Tracking">

                <div class="hero-content">
                    <h1>{{ __('auth.hero_title') }}</h1>
                    <h2>{{ __('auth.hero_subtitle') }}</h2>
                    <span class="hero-rule"></span>
                    <p>{{ __('auth.hero_description') }}</p>
                </div>

                <div class="feature-list">
                    <article class="feature-item">
                        <span class="feature-icon">
                            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 21s6-5.1 6-11a6 6 0 0 0-12 0c0 5.9 6 11 6 11Z"/><path d="M12 12.2a2.3 2.3 0 1 0 0-4.6 2.3 2.3 0 0 0 0 4.6Z"/></svg>
                        </span>
                        <div>
                            <strong>{{ __('auth.feature_realtime_title') }}</strong>
                            <small>{{ __('auth.feature_realtime_desc') }}</small>
                        </div>
                    </article>
                    <article class="feature-item">
                        <span class="feature-icon">
                            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 15.5V8.8c0-1 .8-1.8 1.8-1.8h8.4c1 0 1.8.8 1.8 1.8v6.7"/><path d="M16 11h2.4l2 3.3v1.2"/><path d="M6.8 18.5a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM17.4 18.5a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/><path d="M8.8 16.5h6.6"/></svg>
                        </span>
                        <div>
                            <strong>{{ __('auth.feature_fleet_title') }}</strong>
                            <small>{{ __('auth.feature_fleet_desc') }}</small>
                        </div>
                    </article>
                    <article class="feature-item">
                        <span class="feature-icon">
                            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M7 4h10a2 2 0 0 1 2 2v14l-3-2-3 2-3-2-3 2-3-2V6a2 2 0 0 1 2-2Z"/><path d="M8 9h8M8 13h6"/></svg>
                        </span>
                        <div>
                            <strong>{{ __('auth.feature_reports_title') }}</strong>
                            <small>{{ __('auth.feature_reports_desc') }}</small>
                        </div>
                    </article>
                    <article class="feature-item">
                        <span class="feature-icon">
                            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 3 5.5 5.7v5.4c0 4.1 2.6 7.8 6.5 9.1 3.9-1.3 6.5-5 6.5-9.1V5.7L12 3Z"/><path d="m9.3 12 1.8 1.8 3.8-4"/></svg>
                        </span>
                        <div>
                            <strong>{{ __('auth.feature_security_title') }}</strong>
                            <small>{{ __('auth.feature_security_desc') }}</small>
                        </div>
                    </article>
                </div>

                <div class="hero-stats">
                    <article>
                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 12a8 8 0 0 1 16 0"/><path d="M12 12l4-4"/><path d="M7 20h10"/></svg>
                        <strong>{{ __('auth.stat_monitoring_value') }}</strong>
                        <span>{{ __('auth.stat_monitoring_label') }}</span>
                    </article>
                    <article>
                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 4v16M4 12h16"/><circle cx="12" cy="12" r="7"/></svg>
                        <strong>{{ __('auth.stat_control_value') }}</strong>
                        <span>{{ __('auth.stat_control_label') }}</span>
                    </article>
                    <article>
                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 3 5 6v5c0 4.4 2.8 8.2 7 9.5 4.2-1.3 7-5.1 7-9.5V6l-7-3Z"/></svg>
                        <strong>{{ __('auth.stat_reliability_value') }}</strong>
                        <span>{{ __('auth.stat_reliability_label') }}</span>
                    </article>
                    <article>
                        <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path d="M12 7v5l3 2"/></svg>
                        <strong>{{ __('auth.stat_performance_value') }}</strong>
                        <span>{{ __('auth.stat_performance_label') }}</span>
                    </article>
                </div>
            </div>
        </section>

        <section class="login-panel-wrapper">
            <div class="login-card">
                <div class="login-card-header">
                    <img class="login-logo" src="{{ asset('images/logo-exad-cropped.png') }}" alt="EXAD Tracking">
                    <h2>{{ __('auth.login_title') }}</h2>
                    <p>{{ __('auth.login_subtitle') }}</p>
                </div>

                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" novalidate>
                    @csrf

                    <div class="form-group-block">
                        <label for="email" class="form-label">{{ __('auth.email') }}</label>
                        <div class="field-shell @error('email') is-invalid @enderror">
                            <svg class="field-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M4 6.5h16v11H4z"/><path d="m4.8 7.2 7.2 5.6 7.2-5.6"/></svg>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                class="form-control @error('email') is-invalid @enderror"
                                placeholder="{{ __('auth.email_placeholder') }}"
                                autocomplete="email"
                                required
                                autofocus
                            >
                        </div>
                        @error('email')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group-block">
                        <label for="password" class="form-label">{{ __('auth.password') }}</label>
                        <div class="field-shell @error('password') is-invalid @enderror">
                            <svg class="field-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M7 10V8a5 5 0 0 1 10 0v2"/><path d="M5.5 10h13v10h-13z"/></svg>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="{{ __('auth.password_placeholder') }}"
                                autocomplete="current-password"
                                required
                            >
                            <button class="password-toggle" type="button" aria-label="{{ __('auth.toggle_password') }}" data-password-toggle>
                                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M2.8 12s3.4-5.8 9.2-5.8 9.2 5.8 9.2 5.8-3.4 5.8-9.2 5.8S2.8 12 2.8 12Z"/><circle cx="12" cy="12" r="2.6"/></svg>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="login-options">
                        <div class="form-check">
                            <input id="remember" type="checkbox" name="remember" class="form-check-input">
                            <label for="remember" class="form-check-label">{{ __('auth.remember') }}</label>
                        </div>

                        @if (Route::has('password.request'))
                            <a class="forgot-link" href="{{ route('password.request') }}">{{ __('auth.forgot_password') }}</a>
                        @endif
                    </div>

                    <button type="submit" class="btn login-button">
                        {{ __('auth.login_button') }}
                    </button>
                </form>

                <div class="security-note">
                    <span class="security-icon">
                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M7 10V8a5 5 0 0 1 10 0v2"/><path d="M5.5 10h13v10h-13z"/></svg>
                    </span>
                    <div>
                        <strong>{{ __('auth.secure_data') }}</strong>
                        <small>{{ __('auth.security_standard') }}</small>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        document.querySelector('[data-password-toggle]')?.addEventListener('click', function () {
            const password = document.getElementById('password');
            password.type = password.type === 'password' ? 'text' : 'password';
        });
    </script>
</body>
</html>
