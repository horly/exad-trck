<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('profile.title') }} - {{ $applicationSettings->app_name }}</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/cropperjs/cropper.min.css') }}?v=1.6.2">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=20260528-compact-ui">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260721-client-preview-icons">
    <link rel="stylesheet" href="{{ asset('css/profile.css') }}?v=20260719-profile">
</head>
@php
    $user = auth()->user();
    $photoUrl = $user->profilePhotoUrl();
    $twoFactorEnabled = $user->hasEnabledTwoFactorAuthentication();
    $twoFactorPending = ! empty($user->two_factor_secret) && ! $twoFactorEnabled;
    $recoveryCodes = session('recovery_codes', []);
    $returnUrl = $user->isSuperadmin() ? route('dashboard') : route('fleets.index');
    $statusKey = session('profile_status') ?: session('status');
    $statusMessages = [
        'personal-updated' => __('profile.personal_updated'),
        'email-updated' => __('profile.email_updated'),
        'password-updated' => __('profile.password_updated'),
        'photo-updated' => __('profile.photo_updated'),
        'photo-removed' => __('profile.photo_removed'),
        'two-factor-setup' => __('profile.two_factor_setup'),
        'two-factor-enabled' => __('profile.two_factor_enabled_notice'),
        'two-factor-disabled' => __('profile.two_factor_disabled_notice'),
        'recovery-codes-regenerated' => __('profile.recovery_codes_regenerated'),
    ];
@endphp
<body class="app-font-manrope dashboard-body profile-page-body">
<div class="dashboard-shell">
    @include('partials.sidebar', ['active' => 'profile'])
    <main class="dashboard-main profile-main">
        <header class="dashboard-topbar">
            @include('partials.sidebar-toggle')
            <div><p class="eyebrow mb-1">{{ __('profile.eyebrow') }}</p><h1>{{ __('profile.title') }}</h1></div>
            @include('partials.topbar-actions')
        </header>

        <div class="profile-content">
            <a class="profile-back-link" href="{{ $returnUrl }}"><i class="fa-solid fa-arrow-left"></i>{{ __('profile.back') }}</a>
            <div class="profile-heading">
                <div><h2>{{ __('profile.title') }}</h2><p>{{ __('profile.intro') }}</p></div>
                <span class="profile-role-badge">{{ __('profile.role_'.$user->role->value) }}</span>
            </div>

            <section class="profile-card profile-photo-card">
                <div class="profile-section-heading">
                    <span class="profile-section-icon"><i class="fa-solid fa-camera"></i></span>
                    <div><h3>{{ __('profile.photo_title') }}</h3><p>{{ __('profile.photo_description') }}</p></div>
                </div>
                <div class="profile-photo-row">
                    <span class="profile-avatar-large" data-profile-avatar>
                        @if($photoUrl)<img src="{{ $photoUrl }}" alt="{{ $user->name }}">@else<span>{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>@endif
                    </span>
                    <div class="profile-photo-actions">
                        <input id="profilePhotoSource" type="file" accept="image/jpeg,image/png,image/webp" hidden data-profile-photo-source>
                        <button class="btn profile-secondary-button" type="button" data-choose-profile-photo><i class="fa-solid fa-arrow-up-from-bracket"></i>{{ $photoUrl ? __('profile.change_photo') : __('profile.choose_photo') }}</button>
                        @if($photoUrl)
                            <form method="POST" action="{{ route('profile.photo.destroy') }}" data-loading-form>@csrf @method('DELETE')<button class="btn profile-danger-link" type="submit"><i class="fa-regular fa-trash-can"></i>{{ __('profile.remove_photo') }}</button></form>
                        @endif
                        <small>{{ __('profile.photo_help') }}</small>
                        @error('photo', 'updateProfilePhoto')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>

            <section class="profile-card profile-security-card">
                <div class="profile-section-heading profile-security-heading">
                    <span class="profile-section-icon"><i class="fa-solid fa-shield-halved"></i></span>
                    <div><h3>{{ __('profile.two_factor_title') }}</h3><p>{{ __('profile.two_factor_description') }}</p></div>
                    <span class="profile-status-badge {{ $twoFactorEnabled ? 'is-enabled' : ($twoFactorPending ? 'is-pending' : 'is-disabled') }}">
                        {{ $twoFactorEnabled ? __('profile.two_factor_enabled') : ($twoFactorPending ? __('profile.two_factor_pending') : __('profile.two_factor_disabled')) }}
                    </span>
                </div>

                @if(!$twoFactorEnabled && !$twoFactorPending)
                    <div class="profile-security-action">
                        <p>{{ __('profile.two_factor_disabled_text') }}</p>
                        <form method="POST" action="{{ route('profile.two-factor.enable') }}" class="profile-protected-action" data-loading-form>
                            @csrf
                            <div class="profile-password-field"><label class="form-label" for="enable_current_password">{{ __('profile.current_password') }} *</label><div class="profile-input-icon"><input id="enable_current_password" class="form-control @error('enable_current_password', 'enableTwoFactorAuthentication') is-invalid @enderror" type="password" name="enable_current_password" autocomplete="current-password" required><button type="button" data-profile-password-toggle title="{{ __('profile.toggle_password') }}"><i class="fa-regular fa-eye"></i></button></div>@error('enable_current_password', 'enableTwoFactorAuthentication')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                            <button class="btn profile-primary-button" type="submit"><i class="fa-solid fa-shield-halved"></i>{{ __('profile.enable_two_factor') }}</button>
                        </form>
                    </div>
                @elseif($twoFactorPending)
                    <div class="profile-two-factor-setup">
                        <div class="profile-qr-code">{!! $user->twoFactorQrCodeSvg() !!}</div>
                        <div class="profile-two-factor-copy">
                            <strong>{{ __('profile.scan_qr') }}</strong>
                            <form method="POST" action="{{ route('profile.two-factor.confirm') }}" data-loading-form>
                                @csrf
                                <label class="form-label" for="two_factor_code">{{ __('profile.authentication_code') }} *</label>
                                <input id="two_factor_code" class="form-control profile-code-input @error('code', 'confirmTwoFactorAuthentication') is-invalid @enderror" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" required>
                                @error('code', 'confirmTwoFactorAuthentication')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <button class="btn profile-primary-button" type="submit"><i class="fa-solid fa-check"></i>{{ __('profile.confirm_two_factor') }}</button>
                            </form>
                            <form method="POST" action="{{ route('profile.two-factor.disable') }}" class="profile-cancel-setup" data-loading-form>
                                @csrf @method('DELETE')
                                <label class="form-label" for="cancel_setup_password">{{ __('profile.current_password') }} *</label>
                                <div class="profile-input-icon"><input id="cancel_setup_password" class="form-control @error('disable_current_password', 'disableTwoFactorAuthentication') is-invalid @enderror" type="password" name="disable_current_password" autocomplete="current-password" required><button type="button" data-profile-password-toggle title="{{ __('profile.toggle_password') }}"><i class="fa-regular fa-eye"></i></button></div>
                                @error('disable_current_password', 'disableTwoFactorAuthentication')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <button class="btn profile-danger-button" type="submit"><i class="fa-solid fa-xmark"></i>{{ __('profile.cancel_setup') }}</button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="profile-two-factor-enabled">
                        <p><i class="fa-solid fa-circle-check"></i>{{ __('profile.two_factor_enabled_text') }}</p>
                        @if($recoveryCodes)
                            <div class="profile-recovery-block">
                                <div><strong>{{ __('profile.recovery_codes') }}</strong><small>{{ __('profile.recovery_codes_description') }}</small></div>
                                <div class="profile-recovery-codes">@foreach($recoveryCodes as $recoveryCode)<code>{{ $recoveryCode }}</code>@endforeach</div>
                            </div>
                        @endif
                        <div class="profile-security-actions-grid">
                            <form method="POST" action="{{ route('profile.two-factor.recovery-codes') }}" data-loading-form>
                                @csrf
                                <label class="form-label" for="recovery_current_password">{{ __('profile.current_password') }} *</label>
                                <div class="profile-input-icon"><input id="recovery_current_password" class="form-control @error('recovery_current_password', 'recoveryCodes') is-invalid @enderror" type="password" name="recovery_current_password" autocomplete="current-password" required><button type="button" data-profile-password-toggle title="{{ __('profile.toggle_password') }}"><i class="fa-regular fa-eye"></i></button></div>
                                @error('recovery_current_password', 'recoveryCodes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <div class="profile-inline-actions"><button class="btn profile-secondary-button" type="submit"><i class="fa-solid fa-key"></i>{{ __('profile.show_recovery_codes') }}</button><button class="btn profile-secondary-button" type="submit" formaction="{{ route('profile.two-factor.recovery-codes.regenerate') }}"><i class="fa-solid fa-arrows-rotate"></i>{{ __('profile.regenerate_recovery_codes') }}</button></div>
                            </form>
                            <form method="POST" action="{{ route('profile.two-factor.disable') }}" data-loading-form>
                                @csrf @method('DELETE')
                                <label class="form-label" for="disable_current_password">{{ __('profile.current_password') }} *</label>
                                <div class="profile-input-icon"><input id="disable_current_password" class="form-control @error('disable_current_password', 'disableTwoFactorAuthentication') is-invalid @enderror" type="password" name="disable_current_password" autocomplete="current-password" required><button type="button" data-profile-password-toggle title="{{ __('profile.toggle_password') }}"><i class="fa-regular fa-eye"></i></button></div>
                                @error('disable_current_password', 'disableTwoFactorAuthentication')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <button class="btn profile-danger-button" type="submit"><i class="fa-solid fa-shield-virus"></i>{{ __('profile.disable_two_factor') }}</button>
                            </form>
                        </div>
                    </div>
                @endif
            </section>

            <div class="profile-settings-grid">
                <section class="profile-card profile-personal-card">
                    <div class="profile-section-heading"><span class="profile-section-icon"><i class="fa-solid fa-address-card"></i></span><div><h3>{{ __('profile.personal_title') }}</h3><p>{{ __('profile.personal_description') }}</p></div></div>
                    <form method="POST" action="{{ route('profile.personal.update') }}" data-loading-form>
                        @csrf @method('PATCH')
                        <div class="profile-form-grid">
                            <div><label class="form-label" for="profile_name">{{ __('profile.name') }} *</label><input id="profile_name" class="form-control @error('name', 'updatePersonalInformation') is-invalid @enderror" name="name" value="{{ old('name', $user->name) }}" required>@error('name', 'updatePersonalInformation')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                            <div><label class="form-label" for="profile_phone">{{ __('profile.phone') }}</label><input id="profile_phone" class="form-control @error('phone', 'updatePersonalInformation') is-invalid @enderror" name="phone" value="{{ old('phone', $user->phone) }}">@error('phone', 'updatePersonalInformation')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                            <div><label class="form-label" for="profile_role">{{ __('profile.role') }}</label><input id="profile_role" class="form-control" value="{{ __('profile.role_'.$user->role->value) }}" readonly></div>
                            <div class="profile-form-full"><label class="form-label" for="profile_address">{{ __('profile.address') }}</label><textarea id="profile_address" class="form-control @error('address', 'updatePersonalInformation') is-invalid @enderror" name="address" rows="4">{{ old('address', $user->address) }}</textarea>@error('address', 'updatePersonalInformation')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                        </div>
                        <div class="profile-form-actions"><button class="btn profile-primary-button" type="submit"><i class="fa-solid fa-check"></i>{{ __('profile.update_personal') }}</button></div>
                    </form>
                </section>

                <div class="profile-account-column">
                    <section class="profile-card">
                        <div class="profile-section-heading"><span class="profile-section-icon"><i class="fa-solid fa-envelope"></i></span><div><h3>{{ __('profile.email_title') }}</h3><p>{{ __('profile.email_description') }}</p></div></div>
                        <form method="POST" action="{{ route('profile.email.update') }}" data-loading-form>
                            @csrf @method('PATCH')
                            <div class="profile-field"><label class="form-label" for="profile_email">{{ __('profile.email') }} *</label><input id="profile_email" class="form-control @error('email', 'updateEmail') is-invalid @enderror" type="email" name="email" value="{{ old('email', $user->email) }}" required>@error('email', 'updateEmail')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                            <div class="profile-field"><label class="form-label" for="email_current_password">{{ __('profile.current_password') }} *</label><div class="profile-input-icon"><input id="email_current_password" class="form-control @error('email_current_password', 'updateEmail') is-invalid @enderror" type="password" name="email_current_password" autocomplete="current-password" required><button type="button" data-profile-password-toggle title="{{ __('profile.toggle_password') }}"><i class="fa-regular fa-eye"></i></button></div>@error('email_current_password', 'updateEmail')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                            <div class="profile-form-actions"><button class="btn profile-primary-button" type="submit"><i class="fa-solid fa-envelope-circle-check"></i>{{ __('profile.change_email') }}</button></div>
                        </form>
                    </section>

                    <section class="profile-card">
                        <div class="profile-section-heading"><span class="profile-section-icon"><i class="fa-solid fa-lock"></i></span><div><h3>{{ __('profile.password_title') }}</h3><p>{{ __('profile.password_description') }}</p></div></div>
                        <form method="POST" action="{{ route('user-password.update') }}" data-profile-password-form data-loading-form>
                            @csrf @method('PUT')
                            <div class="profile-field"><label class="form-label" for="current_password">{{ __('profile.current_password') }} *</label><div class="profile-input-icon"><input id="current_password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" type="password" name="current_password" autocomplete="current-password" required><button type="button" data-profile-password-toggle title="{{ __('profile.toggle_password') }}"><i class="fa-regular fa-eye"></i></button></div>@error('current_password', 'updatePassword')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                            <div class="profile-field"><label class="form-label" for="password">{{ __('profile.new_password') }} *</label><div class="profile-input-icon"><input id="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" type="password" name="password" autocomplete="new-password" required aria-describedby="profile-password-rules" data-profile-new-password><button type="button" data-profile-password-toggle title="{{ __('profile.toggle_password') }}"><i class="fa-regular fa-eye"></i></button></div>@error('password', 'updatePassword')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                            <ul class="profile-password-rules" id="profile-password-rules" aria-live="polite"><li data-password-rule="length">{{ __('profile.password_length') }}</li><li data-password-rule="case">{{ __('profile.password_case') }}</li><li data-password-rule="number">{{ __('profile.password_number') }}</li><li data-password-rule="symbol">{{ __('profile.password_symbol') }}</li><li data-password-rule="match">{{ __('profile.password_match') }}</li></ul>
                            <div class="profile-field"><label class="form-label" for="password_confirmation">{{ __('profile.confirm_password') }} *</label><div class="profile-input-icon"><input id="password_confirmation" class="form-control" type="password" name="password_confirmation" autocomplete="new-password" required aria-describedby="profile-password-rules" data-profile-password-confirmation><button type="button" data-profile-password-toggle title="{{ __('profile.toggle_password') }}"><i class="fa-regular fa-eye"></i></button></div></div>
                            <div class="profile-form-actions"><button class="btn profile-primary-button" type="submit"><i class="fa-solid fa-key"></i>{{ __('profile.change_password') }}</button></div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </main>
</div>

@if($statusKey && isset($statusMessages[$statusKey]))
    <div class="app-toast app-toast-success" role="status" data-app-toast><span class="app-toast-icon"><i class="fa-solid fa-check"></i></span><span class="app-toast-message">{{ $statusMessages[$statusKey] }}</span><button class="app-toast-close" type="button" data-app-toast-close><i class="fa-solid fa-xmark"></i></button><span class="app-toast-progress"></span></div>
@endif

<div class="modal fade users-modal profile-crop-modal" id="profileCropModal" tabindex="-1" aria-labelledby="profileCropModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered users-modal-dialog"><div class="modal-content">
        <form method="POST" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data" data-profile-photo-form data-loading-form>
            @csrf
            <input type="file" name="photo" hidden data-profile-photo-output>
            <div class="modal-header"><div class="form-modal-heading"><span class="form-modal-heading-icon"><i class="fa-solid fa-crop-simple"></i></span><h2 class="modal-title" id="profileCropModalTitle">{{ __('profile.crop_title') }}</h2></div><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="{{ __('profile.cancel') }}"></button></div>
            <div class="modal-body profile-crop-body">
                <div class="profile-crop-stage"><img alt="" data-profile-crop-image></div>
                <aside class="profile-crop-aside"><span>{{ __('profile.crop_preview') }}</span><div class="profile-crop-preview" data-profile-crop-preview></div><div class="profile-crop-tools"><button type="button" title="{{ __('profile.zoom_in') }}" data-crop-action="zoom-in"><i class="fa-solid fa-magnifying-glass-plus"></i></button><button type="button" title="{{ __('profile.zoom_out') }}" data-crop-action="zoom-out"><i class="fa-solid fa-magnifying-glass-minus"></i></button><button type="button" title="{{ __('profile.rotate_left') }}" data-crop-action="rotate-left"><i class="fa-solid fa-rotate-left"></i></button><button type="button" title="{{ __('profile.rotate_right') }}" data-crop-action="rotate-right"><i class="fa-solid fa-rotate-right"></i></button><button type="button" title="{{ __('profile.reset_crop') }}" data-crop-action="reset"><i class="fa-solid fa-arrows-rotate"></i></button></div></aside>
            </div>
            <div class="modal-footer"><button type="button" class="btn users-cancel-button" data-bs-dismiss="modal">{{ __('profile.cancel') }}</button><button class="btn btn-primary" type="button" data-save-profile-crop><i class="fa-solid fa-check"></i>{{ __('profile.crop_save') }}</button></div>
        </form>
    </div></div>
</div>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/cropperjs/cropper.min.js') }}?v=1.6.2"></script>
<script src="{{ asset('js/dashboard-sidebar.js') }}"></script>
<script src="{{ asset('js/form-loading.js') }}"></script>
@include('partials.realtime-alerts')
<script src="{{ asset('js/profile.js') }}?v=20260719-profile"></script>
</body>
</html>
