<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('profile.confirm_password_title') }} - {{ $applicationSettings->app_name }}</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth-login.css') }}?v=20260719-security-challenge">
</head>
<body>
<main class="security-challenge-shell">
    <section class="security-challenge-card">
        <img class="security-challenge-logo" src="{{ $applicationSettings->logoUrl() }}" alt="{{ $applicationSettings->app_name }}">
        <span class="security-challenge-icon"><i class="fa-solid fa-lock"></i></span>
        <h1>{{ __('profile.confirm_password_title') }}</h1>
        <p>{{ __('profile.confirm_password_description') }}</p>
        <form method="POST" action="{{ route('password.confirm.store') }}">
            @csrf
            <label class="form-label" for="password">{{ __('profile.current_password') }}</label>
            <div class="field-shell @error('password') is-invalid @enderror"><i class="fa-solid fa-lock field-icon"></i><input id="password" class="form-control" type="password" name="password" autocomplete="current-password" required autofocus><button class="password-toggle" type="button" data-auth-password-toggle aria-label="{{ __('profile.toggle_password') }}"><i class="fa-regular fa-eye"></i></button></div>
            @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <button class="login-button" type="submit">{{ __('profile.confirm') }}</button>
        </form>
    </section>
</main>
<script src="{{ asset('js/auth-security.js') }}?v=20260719-security-challenge"></script>
</body>
</html>
