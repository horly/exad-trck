<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ __('downloads.meta_description') }}">
    <meta name="theme-color" content="{{ $applicationSettings->primary_color }}">
    <meta property="og:title" content="{{ __('downloads.page_title') }} · {{ $applicationSettings->app_name }}">
    <meta property="og:description" content="{{ __('downloads.meta_description') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ route('mobile.downloads.index') }}">
    <meta property="og:image" content="{{ asset('images/icon-exad-tracking.png') }}">
    <title>{{ __('downloads.page_title') }} · {{ $applicationSettings->app_name }}</title>
    <link rel="canonical" href="{{ route('mobile.downloads.index') }}">
    @include('partials.favicon')
    @include('partials.application-theme')
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/mobile-download.css') }}?v=20260901">
</head>
<body>
    <div class="download-page">
        <header class="download-header">
            <a class="download-brand" href="{{ route('mobile.downloads.index') }}" aria-label="{{ $applicationSettings->app_name }}">
                <img src="{{ $applicationSettings->internalLogoUrl() }}" alt="{{ $applicationSettings->app_name }}">
                <span>{{ $applicationSettings->short_name }}</span>
            </a>

            <nav class="download-nav" aria-label="{{ __('downloads.navigation_downloads') }}">
                <a class="download-nav-link is-active" href="#versions">{{ __('downloads.navigation_downloads') }}</a>
                <div class="download-languages" aria-label="{{ __('auth.language') }}">
                    <a href="{{ route('lang.switch', 'fr') }}" @class(['is-active' => app()->getLocale() === 'fr']) lang="fr">FR</a>
                    <a href="{{ route('lang.switch', 'en') }}" @class(['is-active' => app()->getLocale() === 'en']) lang="en">EN</a>
                </div>
                <a class="download-login" href="{{ route('login') }}">
                    <span>{{ __('downloads.navigation_login') }}</span>
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            </nav>
        </header>

        <main>
            <section class="download-hero" id="versions">
                <div class="download-hero-glow" aria-hidden="true"></div>
                <div class="download-hero-grid">
                    <div class="download-hero-copy">
                        <div class="download-eyebrow">
                            <i class="fa-brands fa-android" aria-hidden="true"></i>
                            <span>{{ __('downloads.android_only') }}</span>
                        </div>
                        <h1>{{ __('downloads.hero_title') }}</h1>
                        <p>{{ __('downloads.hero_description') }}</p>

                        <div class="download-trust-row">
                            <span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i>{{ __('downloads.secure_download') }}</span>
                            <span><i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i>{{ __('downloads.minimum_android', ['version' => config('mobile_releases.android.minimum_version')]) }}</span>
                            <span><i class="fa-solid fa-layer-group" aria-hidden="true"></i>{{ __('downloads.universal_apk') }}</span>
                        </div>
                    </div>

                    <div class="download-device-stage" aria-hidden="true">
                        <div class="download-orbit download-orbit-one"></div>
                        <div class="download-orbit download-orbit-two"></div>
                        <div class="download-phone">
                            <div class="download-phone-speaker"></div>
                            <div class="download-phone-screen">
                                <div class="download-phone-topline">
                                    <span>EXAD Tracking</span>
                                    <i class="fa-solid fa-signal"></i>
                                </div>
                                <img src="{{ asset('images/icon-exad-tracking.png') }}" alt="">
                                <strong>{{ $applicationSettings->short_name }}</strong>
                                <small>{{ __('downloads.feature_realtime_title') }}</small>
                                <div class="download-phone-map">
                                    <span class="download-route"></span>
                                    <i class="fa-solid fa-location-dot download-pin-one"></i>
                                    <i class="fa-solid fa-location-dot download-pin-two"></i>
                                </div>
                                <div class="download-phone-metrics">
                                    <span><b>24</b><small>km/h</small></span>
                                    <span><b>12</b><small>GPS</small></span>
                                    <span><b>4G</b><small>Live</small></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="download-latest-section">
                <div class="download-container">
                    <article class="download-latest-card">
                        <div class="download-app-icon">
                            <img src="{{ asset('images/icon-exad-tracking.png') }}" alt="{{ $applicationSettings->app_name }}">
                        </div>
                        <div class="download-latest-content">
                            <span class="download-current-badge"><i class="fa-solid fa-sparkles" aria-hidden="true"></i>{{ __('downloads.latest_version') }}</span>
                            <h2>{{ $applicationSettings->app_name }}</h2>
                            <div class="download-version-line">
                                <strong>{{ __('downloads.version', ['version' => $currentRelease['full_version']]) }}</strong>
                                <span>{{ __('downloads.build', ['build' => $currentRelease['build']]) }}</span>
                                <span>{{ __('downloads.released_on', ['date' => $currentRelease['formatted_date']]) }}</span>
                            </div>
                            <p>{{ __($currentRelease['summary_key']) }}</p>
                            <div class="download-file-meta">
                                <span><i class="fa-brands fa-android" aria-hidden="true"></i>{{ __('downloads.file_details', ['size' => $currentRelease['size']]) }}</span>
                                <span><i class="fa-solid fa-shield" aria-hidden="true"></i>{{ __('downloads.checksum') }}</span>
                            </div>
                            <details class="download-checksum download-checksum-current">
                                <summary>{{ __('downloads.integrity_details') }}</summary>
                                <code>{{ $currentRelease['sha256'] }}</code>
                            </details>
                        </div>
                        <a class="download-primary-button" href="{{ route('mobile.downloads.android', $currentRelease['slug']) }}" aria-label="{{ __('downloads.download_version', ['version' => $currentRelease['full_version'], 'build' => $currentRelease['build']]) }}">
                            <i class="fa-solid fa-download" aria-hidden="true"></i>
                            <span>{{ __('downloads.download_apk') }}</span>
                            <small>{{ $currentRelease['size'] }}</small>
                        </a>
                    </article>
                </div>
            </section>

            <section class="download-features-section">
                <div class="download-container">
                    <div class="download-feature-grid">
                        <article>
                            <span class="download-feature-icon is-blue"><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i></span>
                            <h3>{{ __('downloads.feature_realtime_title') }}</h3>
                            <p>{{ __('downloads.feature_realtime_description') }}</p>
                        </article>
                        <article>
                            <span class="download-feature-icon is-orange"><i class="fa-solid fa-bell" aria-hidden="true"></i></span>
                            <h3>{{ __('downloads.feature_alerts_title') }}</h3>
                            <p>{{ __('downloads.feature_alerts_description') }}</p>
                        </article>
                        <article>
                            <span class="download-feature-icon is-purple"><i class="fa-solid fa-truck-fast" aria-hidden="true"></i></span>
                            <h3>{{ __('downloads.feature_fleet_title') }}</h3>
                            <p>{{ __('downloads.feature_fleet_description') }}</p>
                        </article>
                        <article>
                            <span class="download-feature-icon is-green"><i class="fa-solid fa-user-shield" aria-hidden="true"></i></span>
                            <h3>{{ __('downloads.feature_security_title') }}</h3>
                            <p>{{ __('downloads.feature_security_description') }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="download-archives-section">
                <div class="download-container">
                    <div class="download-section-heading">
                        <span>{{ __('downloads.navigation_downloads') }}</span>
                        <h2>{{ __('downloads.archives_title') }}</h2>
                        <p>{{ __('downloads.archives_description') }}</p>
                    </div>

                    <div class="download-archive-list">
                        @foreach ($archivedReleases as $release)
                            <article class="download-archive-row">
                                <div class="download-archive-icon"><i class="fa-brands fa-android" aria-hidden="true"></i></div>
                                <div class="download-archive-version">
                                    <strong>{{ __('downloads.version', ['version' => $release['full_version']]) }}</strong>
                                    <span>{{ __('downloads.build', ['build' => $release['build']]) }}</span>
                                </div>
                                <div class="download-archive-description">
                                    <p>{{ __($release['summary_key']) }}</p>
                                    <span>{{ __('downloads.released_on', ['date' => $release['formatted_date']]) }} · {{ $release['size'] }}</span>
                                </div>
                                <details class="download-checksum">
                                    <summary>{{ __('downloads.checksum') }}</summary>
                                    <code>{{ $release['sha256'] }}</code>
                                </details>
                                <a href="{{ route('mobile.downloads.android', $release['slug']) }}" aria-label="{{ __('downloads.download_version', ['version' => $release['full_version'], 'build' => $release['build']]) }}">
                                    <i class="fa-solid fa-download" aria-hidden="true"></i>
                                </a>
                            </article>
                        @endforeach
                    </div>
                    <div class="download-archive-notice">
                        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                        <p>{{ __('downloads.downgrade_notice') }}</p>
                    </div>
                </div>
            </section>

            <section class="download-install-section">
                <div class="download-container download-install-grid">
                    <div class="download-install-intro">
                        <span class="download-section-kicker">Android</span>
                        <h2>{{ __('downloads.installation_title') }}</h2>
                        <p>{{ __('downloads.installation_description') }}</p>
                        <div class="download-notice">
                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                            <p>{{ __('downloads.direct_distribution_notice') }}</p>
                        </div>
                        <div class="download-notice download-notice-warning">
                            <i class="fa-solid fa-certificate" aria-hidden="true"></i>
                            <p>{{ __('downloads.test_signature_notice') }}</p>
                        </div>
                    </div>
                    <ol class="download-install-steps">
                        <li>
                            <span>01</span>
                            <div><strong>{{ __('downloads.step_download_title') }}</strong><p>{{ __('downloads.step_download_description') }}</p></div>
                        </li>
                        <li>
                            <span>02</span>
                            <div><strong>{{ __('downloads.step_authorize_title') }}</strong><p>{{ __('downloads.step_authorize_description') }}</p></div>
                        </li>
                        <li>
                            <span>03</span>
                            <div><strong>{{ __('downloads.step_install_title') }}</strong><p>{{ __('downloads.step_install_description') }}</p></div>
                        </li>
                    </ol>
                </div>
            </section>
        </main>

        <footer class="download-footer">
            <div class="download-container">
                <div>
                    <img src="{{ $applicationSettings->internalLogoUrl() }}" alt="{{ $applicationSettings->app_name }}">
                    <p>{{ __('downloads.footer_tagline') }}</p>
                </div>
                <span>© {{ now()->year }} {{ $applicationSettings->short_name }} · {{ __('downloads.all_rights_reserved') }}</span>
            </div>
        </footer>
    </div>
</body>
</html>
