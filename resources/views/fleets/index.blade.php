<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('fleets.title') }} - EXAD Tracking</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=20260528-compact-ui">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260602-sidebar-version-global">
</head>
<body class="app-font-manrope dashboard-body">
    <div class="dashboard-shell">
        @include('partials.sidebar', ['active' => 'fleets'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <p class="eyebrow mb-1">{{ __('fleets.subscription') }}</p>
                    <h1>{{ __('fleets.title') }}</h1>
                </div>

                @include('partials.topbar-actions')
            </header>

            @if ($canManageFleets)
                <div class="users-page-actions">
                    <button type="button" class="btn btn-primary users-primary-button" data-bs-toggle="modal" data-bs-target="#fleetModal" data-fleet-create>
                        <i class="fa-solid fa-plus"></i>
                        <span>{{ __('fleets.new_fleet') }}</span>
                    </button>
                </div>
            @endif

            <div data-datatable-container>
                @include('fleets.partials.table')
            </div>
        </main>
    </div>

    @if (session('status'))
        @php($toastType = session('status_type', 'success'))
        <div class="app-toast app-toast-{{ $toastType }}" role="status" aria-live="polite" data-app-toast>
            <span class="app-toast-icon" aria-hidden="true">
                <i class="fa-solid {{ $toastType === 'danger' ? 'fa-trash-can' : 'fa-check' }}"></i>
            </span>
            <span class="app-toast-message">{{ session('status') }}</span>
            <button type="button" class="app-toast-close" aria-label="{{ __('fleets.close_notification') }}" data-app-toast-close>
                <i class="fa-solid fa-xmark"></i>
            </button>
            <span class="app-toast-progress" aria-hidden="true"></span>
        </div>
    @endif

    @if ($canManageFleets)
        <div class="modal fade users-modal" id="fleetModal" tabindex="-1" aria-labelledby="fleetModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered users-modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('fleets.store') }}" novalidate data-validate-form data-required-message="{{ __('validation.required') }}" data-email-message="{{ __('validation.email') }}" data-fleet-form data-loading-form data-loading-text="{{ __('fleets.processing') }}">
                        @csrf
                        <input type="hidden" name="_method" value="POST" data-fleet-method>

                        <div class="modal-header">
                            <h2 class="modal-title" id="fleetModalTitle" data-fleet-title>{{ __('fleets.create_title') }}</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('fleets.cancel') }}"></button>
                        </div>

                        <div class="modal-body">
                            <div class="users-form-grid">
                                <div>
                                    <label for="fleet_name" class="form-label">{{ __('fleets.name') }} *</label>
                                    <input id="fleet_name" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="{{ __('fleets.name_placeholder') }}" value="{{ old('name') }}" required data-fleet-name>
                                    @error('name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div>
                                    <label for="fleet_code" class="form-label">{{ __('fleets.code') }} *</label>
                                    <input id="fleet_code" name="code" class="form-control @error('code') is-invalid @enderror" placeholder="{{ __('fleets.code_placeholder') }}" value="{{ old('code') }}" required data-fleet-code>
                                    @error('code')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="grid-full">
                                    <label for="fleet_description" class="form-label">{{ __('fleets.description') }}</label>
                                    <textarea id="fleet_description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="{{ __('fleets.description_placeholder') }}" data-fleet-description>{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div>
                                    <label for="fleet_status" class="form-label">{{ __('fleets.status') }} *</label>
                                    <select id="fleet_status" name="status" class="form-select @error('status') is-invalid @enderror" data-fleet-status>
                                        <option value="active" @selected(old('status', 'active') === 'active')>{{ __('fleets.active') }}</option>
                                        <option value="inactive" @selected(old('status') === 'inactive')>{{ __('fleets.inactive') }}</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div>
                                    <label for="fleet_admin" class="form-label">{{ __('fleets.initial_admin') }} *</label>
                                    <select id="fleet_admin" name="admin_id" class="form-select @error('admin_id') is-invalid @enderror" data-fleet-admin @disabled(! auth()->user()->isSuperadmin())>
                                        @if (auth()->user()->isSuperadmin())
                                            <option value="">{{ __('fleets.choose_admin') }}</option>
                                        @endif
                                        @foreach ($assignableAdmins as $assignableAdmin)
                                            <option value="{{ $assignableAdmin->id }}" @selected((int) old('admin_id', auth()->user()->isAdmin() ? auth()->id() : null) === $assignableAdmin->id)>
                                                {{ $assignableAdmin->name }} · {{ $assignableAdmin->email }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if (! auth()->user()->isSuperadmin())
                                        <input type="hidden" name="admin_id" value="{{ auth()->id() }}">
                                    @endif
                                    <small class="technical-code d-block mt-2">{{ __('fleets.initial_admin_hint') }}</small>
                                    @error('admin_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn users-cancel-button" data-bs-dismiss="modal">{{ __('fleets.cancel') }}</button>
                            <button type="submit" class="btn btn-primary" data-loading-button data-fleet-submit>{{ __('fleets.create') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/dashboard-sidebar.js') }}?v=20260528-sidebar-toggle"></script>
    <script src="{{ asset('js/dashboard-controls.js') }}?v=20260529-shared-controls"></script>
    <script src="{{ asset('js/datatable-controls.js') }}?v=20260529-datatable-controls"></script>
    @include('partials.realtime-alerts')
    @if ($canManageFleets)
        <script src="{{ asset('js/confirm-delete.js') }}?v=20260529-delete-confirm"></script>
        <script src="{{ asset('js/form-validation.js') }}?v=20260529-form-validation"></script>
        <script src="{{ asset('js/form-loading.js') }}?v=20260529-form-loading"></script>
        <script>
            (() => {
                const form = document.querySelector('[data-fleet-form]');
                if (!form) {
                    return;
                }

                const modalElement = document.getElementById('fleetModal');
                const title = form.querySelector('[data-fleet-title]');
                const method = form.querySelector('[data-fleet-method]');
                const submit = form.querySelector('[data-fleet-submit]');
                const name = form.querySelector('[data-fleet-name]');
                const code = form.querySelector('[data-fleet-code]');
                const description = form.querySelector('[data-fleet-description]');
                const status = form.querySelector('[data-fleet-status]');
                const admin = form.querySelector('[data-fleet-admin]');
                const storeAction = @json(route('fleets.store'));

                document.addEventListener('click', (event) => {
                    if (event.target.closest('[data-fleet-create]')) {
                        form.action = storeAction;
                        method.value = 'POST';
                        title.textContent = @json(__('fleets.create_title'));
                        submit.textContent = @json(__('fleets.create'));
                        form.reset();
                        status.value = 'active';
                        if (admin && !admin.disabled) {
                            admin.value = '';
                        }
                        return;
                    }

                    const editButton = event.target.closest('[data-fleet-edit]');
                    if (!editButton) {
                        return;
                    }

                    form.action = editButton.dataset.action;
                    method.value = 'PUT';
                    title.textContent = @json(__('fleets.edit_title'));
                    submit.textContent = @json(__('fleets.save'));
                    name.value = editButton.dataset.name || '';
                    code.value = editButton.dataset.code || '';
                    description.value = editButton.dataset.description || '';
                    status.value = editButton.dataset.status || 'active';
                    if (admin && !admin.disabled) {
                        admin.value = editButton.dataset.admin || '';
                    }
                });

                @if ($errors->any())
                    bootstrap.Modal.getOrCreateInstance(modalElement).show();
                @endif
            })();
        </script>
    @endif
    <script>
        const fleetToast = document.querySelector('[data-app-toast]');
        if (fleetToast) {
            const hideToast = () => fleetToast.classList.add('is-hiding');
            fleetToast.querySelector('[data-app-toast-close]')?.addEventListener('click', hideToast);
            setTimeout(hideToast, 5200);
        }
    </script>
</body>
</html>
