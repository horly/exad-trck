<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('garages.title') }} - EXAD Tracking</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=20260528-compact-ui">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260719-maintenance-corporate-2">
</head>
<body class="app-font-manrope dashboard-body">
<div class="dashboard-shell">
    @include('partials.sidebar', ['active' => 'garages'])
    <main class="dashboard-main">
        <header class="dashboard-topbar">@include('partials.sidebar-toggle')<div><p class="eyebrow mb-1">{{ __('garages.eyebrow') }}</p><h1>{{ __('garages.title') }}</h1></div>@include('partials.topbar-actions')</header>
        <div class="users-page-actions"><button class="btn btn-primary users-primary-button" data-bs-toggle="modal" data-bs-target="#garageModal" data-garage-create><i class="fa-solid fa-plus"></i><span>{{ __('garages.new') }}</span></button></div>
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <div data-datatable-container>@include('garages.partials.table')</div>
    </main>
</div>

@if (session('status'))<div class="app-toast app-toast-{{ session('status_type', 'success') }}" role="status" data-app-toast><span class="app-toast-icon"><i class="fa-solid fa-check"></i></span><span class="app-toast-message">{{ session('status') }}</span><button class="app-toast-close" data-app-toast-close><i class="fa-solid fa-xmark"></i></button><span class="app-toast-progress"></span></div>@endif

<div class="modal fade users-modal" id="garageModal" tabindex="-1" aria-labelledby="garageModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered users-modal-dialog garage-modal-dialog"><div class="modal-content">
        <form method="POST" action="{{ route('garages.store') }}" data-garage-form data-address-search-url="{{ route('addresses.search') }}" data-loading-form enctype="multipart/form-data">
            @csrf <input type="hidden" name="_method" value="POST" data-field="method"><input type="hidden" name="editing_garage_id" value="{{ old('editing_garage_id') }}">
            <div class="modal-header"><div class="form-modal-heading"><span class="form-modal-heading-icon"><i class="fa-solid fa-screwdriver-wrench"></i></span><h2 class="modal-title" id="garageModalTitle" data-garage-title data-create-label="{{ __('garages.create_title') }}">{{ __('garages.create_title') }}</h2></div><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="{{ __('garages.cancel') }}"></button></div>
            <div class="modal-body"><div class="users-form-grid">
                <div><label class="form-label">{{ __('garages.name') }} *</label><input class="form-control" name="name" required data-field="name"></div>
                <div><label class="form-label">{{ __('garages.type') }} *</label><select class="form-select" name="type" data-field="type"><option value="internal">{{ __('garages.internal') }}</option><option value="external">{{ __('garages.external') }}</option></select></div>
                <div><label class="form-label">{{ __('garages.responsible') }}</label><input class="form-control" name="responsible_name" data-field="responsible_name"></div>
                <div><label class="form-label">{{ __('garages.dispatcher') }}</label><input class="form-control" name="dispatcher_name" data-field="dispatcher_name"></div>
                <div><label class="form-label">{{ __('garages.phone') }}</label><input class="form-control" name="phone" data-field="phone"></div>
                <div><label class="form-label">{{ __('garages.email') }}</label><input class="form-control" type="email" name="email" data-field="email"></div>
                <div class="users-form-full maintenance-address-field"><label class="form-label">{{ __('garages.address') }}</label><input class="form-control" name="address" autocomplete="off" placeholder="{{ __('garages.address_placeholder') }}" data-field="address" data-garage-address><input type="hidden" name="latitude" data-field="latitude"><input type="hidden" name="longitude" data-field="longitude"><div class="maintenance-address-results" data-address-results hidden></div></div>
                <div><label class="form-label">{{ __('garages.specialties') }}</label><input class="form-control" name="specialties" placeholder="{{ __('garages.specialties_placeholder') }}" data-field="specialties"></div>
                <div><label class="form-label">{{ __('garages.status') }}</label><select class="form-select" name="status" data-field="status"><option value="active">{{ __('garages.status_active') }}</option><option value="inactive">{{ __('garages.status_inactive') }}</option></select></div>
                <div class="users-form-full"><label class="form-label">{{ __('garages.notes') }}</label><textarea class="form-control" rows="3" name="notes" data-field="notes"></textarea></div>
            </div></div>
            <div class="modal-footer"><button type="button" class="btn users-cancel-button" data-bs-dismiss="modal">{{ __('garages.cancel') }}</button><button class="btn btn-primary" data-garage-submit data-create-label="{{ __('garages.create') }}" data-save-label="{{ __('garages.save') }}">{{ __('garages.create') }}</button></div>
        </form>
    </div></div>
</div>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script><script src="{{ asset('js/dashboard-sidebar.js') }}"></script><script src="{{ asset('js/datatable-controls.js') }}"></script><script src="{{ asset('js/confirm-delete.js') }}"></script><script src="{{ asset('js/form-loading.js') }}"></script>
@include('partials.realtime-alerts')
<script src="{{ asset('js/garages.js') }}?v=20260719-global-garages"></script>
</body></html>
