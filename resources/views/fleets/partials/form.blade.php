<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - EXAD Tracking</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=20260528-compact-ui">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260602-sidebar-version-global">
</head>
<body class="app-font-manrope dashboard-body">
    <main class="dashboard-main mx-auto" style="max-width: 860px;">
        <header class="dashboard-topbar">
            <div>
                <p class="eyebrow mb-1">{{ $eyebrow }}</p>
                <h1>{{ $title }}</h1>
            </div>

            @include('partials.topbar-actions')
        </header>

        <div class="users-page-actions">
            <a href="{{ route('fleets.index') }}" class="btn btn-outline-primary">
                {{ __('fleets.back') }}
            </a>
        </div>

        <div class="panel">
            <form method="POST" action="{{ $action }}" class="row g-3" novalidate data-validate-form data-required-message="{{ __('validation.required') }}" data-email-message="{{ __('validation.email') }}" data-loading-form data-loading-text="{{ __('fleets.processing') }}">
                @csrf
                @if ($method !== 'POST')
                    @method($method)
                @endif

                <div class="col-md-8">
                    <label for="name" class="form-label">{{ __('fleets.name') }}</label>
                    <input
                        id="name"
                        name="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $fleet?->name) }}"
                        required
                    >
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="code" class="form-label">{{ __('fleets.code') }}</label>
                    <input
                        id="code"
                        name="code"
                        class="form-control @error('code') is-invalid @enderror"
                        value="{{ old('code', $fleet?->code) }}"
                        required
                    >
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="description" class="form-label">{{ __('fleets.description') }}</label>
                    <textarea
                        id="description"
                        name="description"
                        class="form-control @error('description') is-invalid @enderror"
                        rows="4"
                    >{{ old('description', $fleet?->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="status" class="form-label">{{ __('fleets.status') }}</label>
                    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                        <option value="active" @selected(old('status', $fleet?->status ?? 'active') === 'active')>{{ __('fleets.active') }}</option>
                        <option value="inactive" @selected(old('status', $fleet?->status) === 'inactive')>{{ __('fleets.inactive') }}</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary" data-loading-button>
                        {{ __('fleets.save') }}
                    </button>
                </div>
            </form>
        </div>
    </main>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/dashboard-controls.js') }}?v=20260529-shared-controls"></script>
    @include('partials.realtime-alerts')
    <script src="{{ asset('js/form-validation.js') }}?v=20260529-form-validation"></script>
    <script src="{{ asset('js/form-loading.js') }}?v=20260529-form-loading"></script>
</body>
</html>
