<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('customization.title') }} - {{ $applicationSettings->app_name }}</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=20260528-compact-ui">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260719-application-theme">
    <link rel="stylesheet" href="{{ asset('css/customization.css') }}?v=20260721-internal-logo">
</head>
<body class="app-font-manrope dashboard-body customization-page-body">
    <div class="dashboard-shell">
        @include('partials.sidebar', ['active' => 'customization'])

        <main class="dashboard-main customization-main">
            <header class="dashboard-topbar">
                @include('partials.sidebar-toggle')
                <div>
                    <p class="eyebrow mb-1">{{ __('customization.eyebrow') }}</p>
                    <h1>{{ __('customization.title') }}</h1>
                    <p class="dashboard-breadcrumb">{{ __('customization.breadcrumb') }}</p>
                </div>
                @include('partials.topbar-actions')
            </header>

            <div class="customization-content">
                <div class="customization-intro">
                    <div>
                        <h2>{{ __('customization.page_title') }}</h2>
                        <p>{{ __('customization.page_intro') }}</p>
                    </div>
                    <span class="customization-scope"><i class="fa-solid fa-shield-halved"></i>{{ __('customization.superadmin_only') }}</span>
                </div>

                <form method="POST" action="{{ route('customization.update') }}" enctype="multipart/form-data" data-customization-form data-loading-form>
                    @csrf
                    @method('PATCH')

                    <section class="customization-section" aria-labelledby="identity-title">
                        <div class="customization-section-heading">
                            <span><i class="fa-regular fa-window-maximize"></i></span>
                            <div><h3 id="identity-title">{{ __('customization.identity_title') }}</h3><p>{{ __('customization.identity_help') }}</p></div>
                        </div>
                        <div class="customization-form-grid">
                            <div class="customization-field">
                                <label class="form-label" for="app_name">{{ __('customization.app_name') }} *</label>
                                <input id="app_name" class="form-control @error('app_name') is-invalid @enderror" name="app_name" value="{{ old('app_name', $settings->app_name) }}" maxlength="80" required>
                                @error('app_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="customization-field">
                                <label class="form-label" for="short_name">{{ __('customization.short_name') }} *</label>
                                <input id="short_name" class="form-control @error('short_name') is-invalid @enderror" name="short_name" value="{{ old('short_name', $settings->short_name) }}" maxlength="40" required>
                                @error('short_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="customization-field customization-field-half">
                                <label class="form-label" for="website_url">{{ __('customization.website_url') }}</label>
                                <div class="customization-input-icon"><i class="fa-solid fa-link"></i><input id="website_url" class="form-control @error('website_url') is-invalid @enderror" type="url" name="website_url" value="{{ old('website_url', $settings->website_url) }}" placeholder="https://exemple.com"></div>
                                @error('website_url')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <section class="customization-section" aria-labelledby="visual-title">
                        <div class="customization-section-heading">
                            <span><i class="fa-regular fa-image"></i></span>
                            <div><h3 id="visual-title">{{ __('customization.visual_title') }}</h3><p>{{ __('customization.visual_help') }}</p></div>
                        </div>
                        <div class="customization-assets-grid">
                            <div class="customization-asset">
                                <div class="customization-logo-preview" data-logo-preview><img src="{{ $settings->logoUrl() }}" alt="{{ $settings->app_name }}"></div>
                                <div class="customization-asset-copy">
                                    <strong>{{ __('customization.logo') }}</strong>
                                    <label class="btn customization-upload-button" for="logo"><i class="fa-solid fa-arrow-up-from-bracket"></i>{{ __('customization.change_logo') }}</label>
                                    <input id="logo" class="visually-hidden @error('logo') is-invalid @enderror" type="file" name="logo" accept="image/png,image/jpeg,image/webp" data-image-input data-preview-target="[data-logo-preview] img">
                                    <small>{{ __('customization.logo_help') }}</small>
                                    @error('logo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="customization-asset">
                                <div class="customization-logo-preview customization-internal-logo-preview" data-internal-logo-preview><img src="{{ $settings->internalLogoUrl() }}" alt="{{ __('customization.internal_logo') }}"></div>
                                <div class="customization-asset-copy">
                                    <strong>{{ __('customization.internal_logo') }}</strong>
                                    <label class="btn customization-upload-button" for="internal_logo"><i class="fa-solid fa-arrow-up-from-bracket"></i>{{ __('customization.change_internal_logo') }}</label>
                                    <input id="internal_logo" @class(['visually-hidden', 'is-invalid' => $errors->has('internal_logo')]) type="file" name="internal_logo" accept="image/png,image/jpeg,image/webp" data-image-input data-preview-target="[data-internal-logo-preview] img">
                                    <small>{{ __('customization.internal_logo_help') }}</small>
                                    @error('internal_logo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="customization-asset">
                                <div class="customization-favicon-preview" data-favicon-preview><img src="{{ $settings->faviconUrl() }}" alt="{{ __('customization.favicon') }}"></div>
                                <div class="customization-asset-copy">
                                    <strong>{{ __('customization.favicon') }}</strong>
                                    <label class="btn customization-upload-button" for="favicon"><i class="fa-solid fa-arrow-up-from-bracket"></i>{{ __('customization.change_favicon') }}</label>
                                    <input id="favicon" class="visually-hidden @error('favicon') is-invalid @enderror" type="file" name="favicon" accept="image/png,image/webp,image/x-icon,.ico" data-image-input data-preview-target="[data-favicon-preview] img">
                                    <small>{{ __('customization.favicon_help') }}</small>
                                    @error('favicon')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="customization-section" aria-labelledby="colors-title">
                        <div class="customization-section-heading customization-colors-heading">
                            <span><i class="fa-solid fa-palette"></i></span>
                            <div><h3 id="colors-title">{{ __('customization.colors_title') }}</h3><p>{{ __('customization.colors_help') }}</p></div>
                            <button class="btn customization-reset-button" type="button" data-reset-colors><i class="fa-solid fa-arrow-rotate-left"></i>{{ __('customization.restore_colors') }}</button>
                        </div>
                        <div class="customization-colors-grid">
                            @foreach ([
                                'primary_color' => ['label' => __('customization.primary_color'), 'variable' => '--exad-primary'],
                                'secondary_color' => ['label' => __('customization.secondary_color'), 'variable' => '--exad-secondary'],
                                'button_color' => ['label' => __('customization.button_color'), 'variable' => '--exad-button'],
                                'avatar_color' => ['label' => __('customization.avatar_color'), 'variable' => '--exad-avatar'],
                                'accent_color' => ['label' => __('customization.accent_color'), 'variable' => '--exad-accent'],
                                'sidebar_start_color' => ['label' => __('customization.sidebar_start_color'), 'variable' => '--exad-sidebar'],
                                'sidebar_end_color' => ['label' => __('customization.sidebar_end_color'), 'variable' => '--exad-sidebar-deep'],
                            ] as $field => $color)
                                <div class="customization-color-field">
                                    <label class="form-label" for="{{ $field }}">{{ $color['label'] }}</label>
                                    <div class="customization-color-control @error($field) is-invalid @enderror">
                                        <input id="{{ $field }}" type="color" name="{{ $field }}" value="{{ old($field, $settings->{$field}) }}" data-theme-color="{{ $color['variable'] }}" data-default-color="{{ \App\Models\ApplicationSetting::DEFAULT_COLORS[$field] }}">
                                        <output for="{{ $field }}" data-color-value>{{ strtoupper(old($field, $settings->{$field})) }}</output>
                                    </div>
                                    @error($field)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="customization-section" aria-labelledby="support-title">
                        <div class="customization-section-heading">
                            <span><i class="fa-solid fa-headset"></i></span>
                            <div><h3 id="support-title">{{ __('customization.support_title') }}</h3><p>{{ __('customization.support_help') }}</p></div>
                        </div>
                        <div class="customization-form-grid">
                            <div class="customization-field">
                                <label class="form-label" for="support_email">{{ __('customization.support_email') }}</label>
                                <div class="customization-input-icon"><i class="fa-regular fa-envelope"></i><input id="support_email" class="form-control @error('support_email') is-invalid @enderror" type="email" name="support_email" value="{{ old('support_email', $settings->support_email) }}" placeholder="support@exemple.com"></div>
                                @error('support_email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="customization-field">
                                <label class="form-label" for="support_phone">{{ __('customization.support_phone') }}</label>
                                <div class="customization-input-icon"><i class="fa-solid fa-phone"></i><input id="support_phone" class="form-control @error('support_phone') is-invalid @enderror" name="support_phone" value="{{ old('support_phone', $settings->support_phone) }}" placeholder="+243 000 000 000"></div>
                                @error('support_phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="customization-actions">
                        <a class="btn customization-cancel-button" href="{{ route('dashboard') }}">{{ __('customization.cancel') }}</a>
                        <button class="btn customization-save-button" type="submit" data-loading-button><i class="fa-solid fa-circle-check"></i>{{ __('customization.save') }}</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    @if(session('customization_status') === 'updated')
        <div class="app-toast app-toast-success" role="status" data-app-toast><span class="app-toast-icon"><i class="fa-solid fa-check"></i></span><span class="app-toast-message">{{ __('customization.updated') }}</span><button class="app-toast-close" type="button" data-app-toast-close><i class="fa-solid fa-xmark"></i></button><span class="app-toast-progress"></span></div>
    @endif

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/dashboard-sidebar.js') }}?v=20260716-fleet-submenu"></script>
    <script src="{{ asset('js/dashboard-controls.js') }}?v=20260529-shared-controls"></script>
    <script src="{{ asset('js/form-loading.js') }}?v=20260529-form-loading"></script>
    <script src="{{ asset('js/customization.js') }}?v=20260719-customization"></script>
    @include('partials.realtime-alerts')
</body>
</html>
