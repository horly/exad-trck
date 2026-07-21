<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('profile.two_factor_challenge_title') }} - {{ $applicationSettings->app_name }}</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth-login.css') }}?v=20260719-security-challenge">
</head>
<body>
<main class="security-challenge-shell">
    <section class="security-challenge-card">
        <img class="security-challenge-logo" src="{{ $applicationSettings->logoUrl() }}" alt="{{ $applicationSettings->app_name }}">
        <span class="security-challenge-icon"><i class="fa-solid fa-shield-halved"></i></span>
        <h1>{{ __('profile.two_factor_challenge_title') }}</h1>
        <p>{{ __('profile.two_factor_challenge_description') }}</p>
        <form method="POST" action="{{ route('two-factor.login') }}">
            @csrf
            <div data-auth-code-panel>
                <label class="form-label" for="code">{{ __('profile.authentication_code') }}</label>
                <input id="code" class="form-control security-code-input @error('code') is-invalid @enderror" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" autofocus>
                @error('code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div data-auth-recovery-panel hidden>
                <label class="form-label" for="recovery_code">{{ __('profile.recovery_code') }}</label>
                <input id="recovery_code" class="form-control @error('recovery_code') is-invalid @enderror" name="recovery_code" autocomplete="one-time-code">
                @error('recovery_code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <button class="security-mode-toggle" type="button" data-auth-recovery-toggle data-code-label="{{ __('profile.use_authentication_code') }}" data-recovery-label="{{ __('profile.use_recovery_code') }}">{{ __('profile.use_recovery_code') }}</button>
            <button class="login-button" type="submit">{{ __('profile.continue') }}</button>
        </form>
    </section>
</main>
<script src="{{ asset('js/auth-security.js') }}?v=20260719-security-challenge"></script>
</body>
</html>
