<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('departments.title') }} - {{ $applicationSettings->app_name }}</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=20260528-compact-ui">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260721-client-preview-icons">
</head>
<body class="app-font-manrope dashboard-body">
    @php
        $editingDepartmentId = (int) old('editing_department_id', 0);
        $departmentFormAction = $editingDepartmentId
            ? route('departments.update', $editingDepartmentId)
            : route('departments.store');
    @endphp

    <div class="dashboard-shell">
        @include('partials.sidebar', ['active' => 'departments'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                @include('partials.sidebar-toggle')
                <div>
                    <p class="eyebrow mb-1">{{ __('departments.eyebrow') }}</p>
                    <h1>{{ __('departments.title') }}</h1>
                </div>
                @include('partials.topbar-actions')
            </header>

            <div class="users-page-actions">
                <button type="button" class="btn btn-primary users-primary-button" data-bs-toggle="modal" data-bs-target="#departmentModal" data-department-create>
                    <i class="fa-solid fa-plus"></i>
                    <span>{{ __('departments.new') }}</span>
                </button>
            </div>

            <div data-datatable-container>
                @include('departments.partials.table')
            </div>
        </main>
    </div>

    @if (session('status'))
        @php($toastType = session('status_type', 'success'))
        <div class="app-toast app-toast-{{ $toastType }}" role="status" aria-live="polite" data-app-toast>
            <span class="app-toast-icon" aria-hidden="true"><i class="fa-solid {{ $toastType === 'danger' ? 'fa-trash-can' : 'fa-check' }}"></i></span>
            <span class="app-toast-message">{{ session('status') }}</span>
            <button type="button" class="app-toast-close" aria-label="{{ __('departments.close_notification') }}" data-app-toast-close><i class="fa-solid fa-xmark"></i></button>
            <span class="app-toast-progress" aria-hidden="true"></span>
        </div>
    @endif

    <div class="modal fade users-modal" id="departmentModal" tabindex="-1" aria-labelledby="departmentModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered users-modal-dialog department-modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ $departmentFormAction }}" novalidate data-validate-form data-required-message="{{ __('validation.required') }}" data-department-form data-loading-form data-loading-text="{{ __('departments.processing') }}">
                    @csrf
                    <input type="hidden" name="_method" value="{{ $editingDepartmentId ? 'PUT' : 'POST' }}" data-department-method>
                    <input type="hidden" name="editing_department_id" value="{{ old('editing_department_id') }}" data-department-id>

                    <div class="modal-header">
                        <div class="form-modal-heading">
                            <span class="form-modal-heading-icon"><i class="fa-solid fa-sitemap"></i></span>
                            <h2 class="modal-title" id="departmentModalTitle" data-department-title>{{ $editingDepartmentId ? __('departments.edit_title') : __('departments.create_title') }}</h2>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('departments.cancel') }}"></button>
                    </div>

                    <div class="modal-body">
                        <div class="users-form-grid">
                            <div>
                                <label for="department_fleet_id" class="form-label">{{ __('departments.fleet') }} *</label>
                                <select id="department_fleet_id" name="fleet_id" class="form-select @error('fleet_id') is-invalid @enderror" required data-department-fleet data-searchable-database data-search-placeholder="{{ __('ui.search_options') }}" data-no-results="{{ __('ui.no_option_match') }}" data-option-icon="fa-warehouse">
                                    <option value="">{{ __('departments.choose_fleet') }}</option>
                                    @foreach ($fleets as $fleet)
                                        <option value="{{ $fleet->id }}" @selected((int) old('fleet_id') === $fleet->id)>{{ $fleet->name }} &middot; {{ $fleet->code }}</option>
                                    @endforeach
                                </select>
                                @error('fleet_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div>
                                <label for="department_name" class="form-label">{{ __('departments.name') }} *</label>
                                <input id="department_name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="{{ __('departments.name_placeholder') }}" required data-department-name>
                                @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div>
                                <label for="department_code" class="form-label">{{ __('departments.code') }}</label>
                                <input id="department_code" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="{{ __('departments.code_placeholder') }}" data-department-code>
                                @error('code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div>
                                <label for="department_status" class="form-label">{{ __('departments.status') }} *</label>
                                <select id="department_status" name="status" class="form-select @error('status') is-invalid @enderror" required data-department-status>
                                    <option value="active" @selected(old('status', 'active') === 'active')>{{ __('departments.status_active') }}</option>
                                    <option value="inactive" @selected(old('status') === 'inactive')>{{ __('departments.status_inactive') }}</option>
                                </select>
                                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="users-form-full">
                                <label for="department_description" class="form-label">{{ __('departments.description') }}</label>
                                <textarea id="department_description" name="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="{{ __('departments.description_placeholder') }}" data-department-description>{{ old('description') }}</textarea>
                                @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn users-cancel-button" data-bs-dismiss="modal">{{ __('departments.cancel') }}</button>
                        <button type="submit" class="btn btn-primary" data-loading-button data-department-submit>{{ $editingDepartmentId ? __('departments.save') : __('departments.create') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/dashboard-sidebar.js') }}?v=20260716-fleet-submenu"></script>
    <script src="{{ asset('js/dashboard-controls.js') }}?v=20260529-shared-controls"></script>
    <script src="{{ asset('js/datatable-controls.js') }}?v=20260529-datatable-controls"></script>
    @include('partials.realtime-alerts')
    <script src="{{ asset('js/confirm-delete.js') }}?v=20260529-delete-confirm"></script>
    <script src="{{ asset('js/form-validation.js') }}?v=20260529-form-validation"></script>
    <script src="{{ asset('js/form-loading.js') }}?v=20260529-form-loading"></script>
    <script src="{{ asset('js/searchable-select.js') }}?v=20260719-database-selects"></script>
    <script>
        (() => {
            const form = document.querySelector('[data-department-form]');
            if (!form) return;

            const modalElement = document.getElementById('departmentModal');
            const fields = {
                id: form.querySelector('[data-department-id]'),
                method: form.querySelector('[data-department-method]'),
                title: form.querySelector('[data-department-title]'),
                submit: form.querySelector('[data-department-submit]'),
                fleet: form.querySelector('[data-department-fleet]'),
                name: form.querySelector('[data-department-name]'),
                code: form.querySelector('[data-department-code]'),
                status: form.querySelector('[data-department-status]'),
                description: form.querySelector('[data-department-description]'),
            };
            const storeAction = @json(route('departments.store'));

            document.addEventListener('click', (event) => {
                if (event.target.closest('[data-department-create]')) {
                    form.reset();
                    form.action = storeAction;
                    fields.id.value = '';
                    fields.method.value = 'POST';
                    fields.status.value = 'active';
                    fields.title.textContent = @json(__('departments.create_title'));
                    fields.submit.textContent = @json(__('departments.create'));
                    return;
                }

                const button = event.target.closest('[data-department-edit]');
                if (!button) return;

                form.action = button.dataset.action;
                fields.id.value = button.dataset.departmentId || '';
                fields.method.value = 'PUT';
                fields.fleet.value = button.dataset.fleetId || '';
                fields.name.value = button.dataset.name || '';
                fields.code.value = button.dataset.code || '';
                fields.description.value = button.dataset.description || '';
                fields.status.value = button.dataset.status || 'active';
                fields.fleet.dispatchEvent(new Event('searchable-select:refresh'));
                fields.title.textContent = @json(__('departments.edit_title'));
                fields.submit.textContent = @json(__('departments.save'));
            });

            @if ($errors->any())
                bootstrap.Modal.getOrCreateInstance(modalElement).show();
            @endif
        })();

        const departmentToast = document.querySelector('[data-app-toast]');
        if (departmentToast) {
            const hideToast = () => departmentToast.classList.add('is-hiding');
            departmentToast.querySelector('[data-app-toast-close]')?.addEventListener('click', hideToast);
            setTimeout(hideToast, 5200);
        }
    </script>
</body>
</html>
