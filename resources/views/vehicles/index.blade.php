<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('vehicles.title') }} - EXAD Tracking</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=20260528-compact-ui">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260602-sidebar-version-global">
</head>
<body class="app-font-manrope dashboard-body">
    <div class="dashboard-shell">
        @include('partials.sidebar', ['active' => 'vehicles'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <p class="eyebrow mb-1">{{ __('vehicles.eyebrow') }}</p>
                    <h1>{{ __('vehicles.title') }}</h1>
                </div>

                @include('partials.topbar-actions')
            </header>

            @if ($canManageVehicles)
                <div class="users-page-actions">
                    <button type="button" class="btn btn-primary users-primary-button" data-bs-toggle="modal" data-bs-target="#vehicleModal" data-vehicle-create>
                        <i class="fa-solid fa-plus"></i>
                        <span>{{ __('vehicles.new_vehicle') }}</span>
                    </button>
                </div>
            @endif

            <div data-datatable-container>
                @include('vehicles.partials.table')
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
            <button type="button" class="app-toast-close" aria-label="{{ __('vehicles.close_notification') }}" data-app-toast-close>
                <i class="fa-solid fa-xmark"></i>
            </button>
            <span class="app-toast-progress" aria-hidden="true"></span>
        </div>
    @endif

    @if ($canManageVehicles)
        <div class="modal fade users-modal" id="vehicleModal" tabindex="-1" aria-labelledby="vehicleModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered users-modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('vehicles.store') }}" novalidate data-validate-form data-required-message="{{ __('validation.required') }}" data-email-message="{{ __('validation.email') }}" data-vehicle-form data-loading-form data-loading-text="{{ __('vehicles.processing') }}">
                        @csrf
                        <input type="hidden" name="_method" value="POST" data-vehicle-method>

                        <div class="modal-header">
                            <h2 class="modal-title" id="vehicleModalTitle" data-vehicle-title>{{ __('vehicles.create_title') }}</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('vehicles.cancel') }}"></button>
                        </div>

                        <div class="modal-body">
                            <div class="users-form-grid">
                                <div>
                                    <label for="vehicle_fleet_id" class="form-label">{{ __('vehicles.fleet') }} *</label>
                                    <select id="vehicle_fleet_id" name="fleet_id" class="form-select @error('fleet_id') is-invalid @enderror" required data-vehicle-fleet>
                                        <option value="">{{ __('vehicles.choose_fleet') }}</option>
                                        @foreach ($manageableFleets as $fleet)
                                            <option value="{{ $fleet->id }}" @selected((int) old('fleet_id') === $fleet->id)>
                                                {{ $fleet->name }} · {{ $fleet->code }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('fleet_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div>
                                    <label for="vehicle_name" class="form-label">{{ __('vehicles.name') }} *</label>
                                    <input id="vehicle_name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="{{ __('vehicles.name_placeholder') }}" required data-vehicle-name>
                                    @error('name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div>
                                    <label for="vehicle_registration_number" class="form-label">{{ __('vehicles.registration_number') }} *</label>
                                    <input id="vehicle_registration_number" name="registration_number" class="form-control @error('registration_number') is-invalid @enderror" value="{{ old('registration_number') }}" placeholder="{{ __('vehicles.registration_placeholder') }}" required data-vehicle-registration>
                                    @error('registration_number')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div>
                                    <label for="vehicle_type" class="form-label">{{ __('vehicles.type') }} *</label>
                                    <select id="vehicle_type" name="vehicle_type" class="form-select @error('vehicle_type') is-invalid @enderror" required data-vehicle-type>
                                        @foreach ($vehicleTypes as $type)
                                            <option value="{{ $type }}" @selected(old('vehicle_type', 'passenger_car') === $type)>{{ __('vehicles.type_' . $type) }}</option>
                                        @endforeach
                                    </select>
                                    @error('vehicle_type')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div>
                                    <label for="vehicle_brand" class="form-label">{{ __('vehicles.brand') }}</label>
                                    <input id="vehicle_brand" name="brand" class="form-control @error('brand') is-invalid @enderror" value="{{ old('brand') }}" placeholder="{{ __('vehicles.brand_placeholder') }}" data-vehicle-brand>
                                    @error('brand')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div>
                                    <label for="vehicle_model" class="form-label">{{ __('vehicles.model') }}</label>
                                    <input id="vehicle_model" name="model" class="form-control @error('model') is-invalid @enderror" value="{{ old('model') }}" placeholder="{{ __('vehicles.model_placeholder') }}" data-vehicle-model>
                                    @error('model')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div>
                                    <label for="vehicle_color" class="form-label">{{ __('vehicles.color') }}</label>
                                    <input id="vehicle_color" name="color" class="form-control @error('color') is-invalid @enderror" value="{{ old('color') }}" placeholder="{{ __('vehicles.color_placeholder') }}" data-vehicle-color>
                                    @error('color')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div>
                                    <label for="vehicle_year" class="form-label">{{ __('vehicles.year') }}</label>
                                    <input id="vehicle_year" type="number" min="1950" max="2100" name="year" class="form-control @error('year') is-invalid @enderror" value="{{ old('year') }}" placeholder="{{ __('vehicles.year_placeholder') }}" data-vehicle-year>
                                    @error('year')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div>
                                    <label for="vehicle_subscription_plan" class="form-label">{{ __('vehicles.subscription_plan') }} *</label>
                                    <select id="vehicle_subscription_plan" name="subscription_plan" class="form-select @error('subscription_plan') is-invalid @enderror" required data-vehicle-plan>
                                        <option value="basic" @selected(old('subscription_plan', 'basic') === 'basic')>{{ __('vehicles.plan_basic') }}</option>
                                        <option value="premium" @selected(old('subscription_plan') === 'premium')>{{ __('vehicles.plan_premium') }}</option>
                                    </select>
                                    @error('subscription_plan')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div>
                                    <label for="vehicle_status" class="form-label">{{ __('vehicles.status') }} *</label>
                                    <select id="vehicle_status" name="status" class="form-select @error('status') is-invalid @enderror" required data-vehicle-status>
                                        <option value="active" @selected(old('status', 'active') === 'active')>{{ __('vehicles.status_active') }}</option>
                                        <option value="inactive" @selected(old('status') === 'inactive')>{{ __('vehicles.status_inactive') }}</option>
                                        <option value="maintenance" @selected(old('status') === 'maintenance')>{{ __('vehicles.status_maintenance') }}</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn users-cancel-button" data-bs-dismiss="modal">{{ __('vehicles.cancel') }}</button>
                            <button type="submit" class="btn btn-primary" data-loading-button data-vehicle-submit>{{ __('vehicles.create') }}</button>
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
    @if ($canManageVehicles)
        <script src="{{ asset('js/confirm-delete.js') }}?v=20260529-delete-confirm"></script>
        <script src="{{ asset('js/form-validation.js') }}?v=20260529-form-validation"></script>
        <script src="{{ asset('js/form-loading.js') }}?v=20260529-form-loading"></script>
        <script>
            (() => {
                const form = document.querySelector('[data-vehicle-form]');
                if (!form) {
                    return;
                }

                const title = form.querySelector('[data-vehicle-title]');
                const method = form.querySelector('[data-vehicle-method]');
                const submit = form.querySelector('[data-vehicle-submit]');
                const fields = {
                    fleet: form.querySelector('[data-vehicle-fleet]'),
                    name: form.querySelector('[data-vehicle-name]'),
                    registration: form.querySelector('[data-vehicle-registration]'),
                    brand: form.querySelector('[data-vehicle-brand]'),
                    model: form.querySelector('[data-vehicle-model]'),
                    color: form.querySelector('[data-vehicle-color]'),
                    year: form.querySelector('[data-vehicle-year]'),
                    type: form.querySelector('[data-vehicle-type]'),
                    plan: form.querySelector('[data-vehicle-plan]'),
                    status: form.querySelector('[data-vehicle-status]'),
                };
                const storeAction = @json(route('vehicles.store'));
                const legacyTypes = {
                    car: 'passenger_car',
                    utility: 'fourgonnette',
                    bus: 'bus_coach',
                    tricycle_tuk_tuk: 'tricycle',
                    agricultural: 'tractor',
                    construction: 'bulldozer',
                    special: 'ambulance',
                    electric_hybrid: 'passenger_car',
                    other: 'ambulance',
                };

                document.addEventListener('click', (event) => {
                    if (event.target.closest('[data-vehicle-create]')) {
                        form.action = storeAction;
                        method.value = 'POST';
                        title.textContent = @json(__('vehicles.create_title'));
                        submit.textContent = @json(__('vehicles.create'));
                        form.reset();
                        fields.type.value = 'passenger_car';
                        fields.plan.value = 'basic';
                        fields.status.value = 'active';
                        return;
                    }

                    const editButton = event.target.closest('[data-vehicle-edit]');
                    if (!editButton) {
                        return;
                    }

                    form.action = editButton.dataset.action;
                    method.value = 'PUT';
                    title.textContent = @json(__('vehicles.edit_title'));
                    submit.textContent = @json(__('vehicles.save'));
                    fields.fleet.value = editButton.dataset.fleetId || '';
                    fields.name.value = editButton.dataset.name || '';
                    fields.registration.value = editButton.dataset.registrationNumber || '';
                    fields.brand.value = editButton.dataset.brand || '';
                    fields.model.value = editButton.dataset.model || '';
                    fields.color.value = editButton.dataset.color || '';
                    fields.year.value = editButton.dataset.year || '';
                    const vehicleType = editButton.dataset.vehicleType || 'passenger_car';
                    fields.type.value = legacyTypes[vehicleType] || vehicleType;
                    fields.plan.value = editButton.dataset.subscriptionPlan || 'basic';
                    fields.status.value = editButton.dataset.status || 'active';
                });

                @if ($errors->any())
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('vehicleModal')).show();
                @endif
            })();
        </script>
    @endif
    <script>
        const vehicleToast = document.querySelector('[data-app-toast]');
        if (vehicleToast) {
            const hideToast = () => vehicleToast.classList.add('is-hiding');
            vehicleToast.querySelector('[data-app-toast-close]')?.addEventListener('click', hideToast);
            setTimeout(hideToast, 5200);
        }
    </script>
</body>
</html>
