<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('drivers.title') }} - {{ $applicationSettings->app_name }}</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=20260528-compact-ui">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260721-client-preview-icons">
</head>
<body class="app-font-manrope dashboard-body">
    @php
        $canManageDrivers = $canManageDrivers ?? auth()->user()->isSuperadmin();
        $editingDriverId = $canManageDrivers ? (int) old('editing_driver_id', 0) : 0;
        $driverFormAction = $canManageDrivers
            ? ($editingDriverId ? route('drivers.update', $editingDriverId) : route('drivers.store'))
            : null;
        $oldVehicleIds = $canManageDrivers
            ? collect(old('authorized_vehicle_ids', []))->map(fn ($id) => (int) $id)->all()
            : [];
    @endphp

    <div class="dashboard-shell">
        @include('partials.sidebar', ['active' => 'drivers'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                @include('partials.sidebar-toggle')
                <div>
                    <p class="eyebrow mb-1">{{ __('drivers.eyebrow') }}</p>
                    <h1>{{ __('drivers.title') }}</h1>
                </div>
                @include('partials.topbar-actions')
            </header>

            @if ($canManageDrivers)
                <div class="users-page-actions">
                    <button type="button" class="btn btn-primary users-primary-button" data-bs-toggle="modal" data-bs-target="#driverModal" data-driver-create>
                        <i class="fa-solid fa-user-plus"></i>
                        <span>{{ __('drivers.new') }}</span>
                    </button>
                </div>
            @endif

            <div data-datatable-container>
                @include('drivers.partials.table')
            </div>
        </main>
    </div>

    @if (session('status'))
        @php($toastType = session('status_type', 'success'))
        <div class="app-toast app-toast-{{ $toastType }}" role="status" aria-live="polite" data-app-toast>
            <span class="app-toast-icon" aria-hidden="true"><i class="fa-solid {{ $toastType === 'danger' ? 'fa-trash-can' : 'fa-check' }}"></i></span>
            <span class="app-toast-message">{{ session('status') }}</span>
            <button type="button" class="app-toast-close" aria-label="{{ __('drivers.close_notification') }}" data-app-toast-close><i class="fa-solid fa-xmark"></i></button>
            <span class="app-toast-progress" aria-hidden="true"></span>
        </div>
    @endif

    @if ($canManageDrivers)
        <div class="modal fade users-modal driver-modal" id="driverModal" tabindex="-1" aria-labelledby="driverModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered driver-modal-dialog">
            <div class="modal-content">
                <form class="driver-modal-form" method="POST" action="{{ $driverFormAction }}" enctype="multipart/form-data" novalidate data-validate-form data-required-message="{{ __('validation.required') }}" data-email-message="{{ __('validation.email') }}" data-driver-form data-loading-form data-loading-text="{{ __('drivers.processing') }}" data-address-search-url="{{ route('addresses.search') }}">
                    @csrf
                    <input type="hidden" name="_method" value="{{ $editingDriverId ? 'PUT' : 'POST' }}" data-driver-method>
                    <input type="hidden" name="editing_driver_id" value="{{ old('editing_driver_id') }}" data-driver-id>

                    <div class="modal-header">
                        <div class="driver-modal-heading">
                            <span class="driver-modal-heading-icon"><i class="fa-solid fa-id-card"></i></span>
                            <h2 class="modal-title" id="driverModalTitle" data-driver-title>{{ $editingDriverId ? __('drivers.edit_title') : __('drivers.create_title') }}</h2>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('drivers.cancel') }}"></button>
                    </div>

                    <div class="modal-body driver-modal-body">
                        <section class="driver-form-section">
                            <div class="driver-form-section-header">
                                <i class="fa-solid fa-user"></i>
                                <h3>{{ __('drivers.identity') }}</h3>
                            </div>

                            <div class="driver-photo-field">
                                <span class="driver-photo-preview" data-driver-photo-preview><i class="fa-solid fa-camera"></i></span>
                                <div>
                                    <label for="driver_photo" class="form-label">{{ __('drivers.photo') }}</label>
                                    <input id="driver_photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="form-control @error('photo') is-invalid @enderror" data-driver-photo>
                                    <small>{{ __('drivers.photo_help') }}</small>
                                    @error('photo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="users-form-grid">
                                <div>
                                    <label for="driver_first_name" class="form-label">{{ __('drivers.first_name') }} *</label>
                                    <input id="driver_first_name" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" placeholder="{{ __('drivers.first_name_placeholder') }}" required data-driver-field="first_name">
                                    @error('first_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label for="driver_middle_name" class="form-label">{{ __('drivers.middle_name') }}</label>
                                    <input id="driver_middle_name" name="middle_name" class="form-control @error('middle_name') is-invalid @enderror" value="{{ old('middle_name') }}" placeholder="{{ __('drivers.middle_name_placeholder') }}" data-driver-field="middle_name">
                                    @error('middle_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label for="driver_last_name" class="form-label">{{ __('drivers.last_name') }}</label>
                                    <input id="driver_last_name" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}" placeholder="{{ __('drivers.last_name_placeholder') }}" data-driver-field="last_name">
                                    @error('last_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label for="driver_employee_id" class="form-label">{{ __('drivers.employee_id_label') }}</label>
                                    <input id="driver_employee_id" name="employee_id" class="form-control @error('employee_id') is-invalid @enderror" value="{{ old('employee_id') }}" placeholder="{{ __('drivers.employee_placeholder') }}" data-driver-field="employee_id">
                                    @error('employee_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label for="driver_social_security_number" class="form-label">{{ __('drivers.social_security_number') }}</label>
                                    <input id="driver_social_security_number" name="social_security_number" class="form-control @error('social_security_number') is-invalid @enderror" value="{{ old('social_security_number') }}" placeholder="{{ __('drivers.social_security_placeholder') }}" data-driver-field="social_security_number">
                                    @error('social_security_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </section>

                        <section class="driver-form-section">
                            <div class="driver-form-section-header"><i class="fa-solid fa-diagram-project"></i><h3>{{ __('drivers.assignment') }}</h3></div>
                            <div class="users-form-grid">
                                <div>
                                    <label for="driver_fleet_id" class="form-label">{{ __('drivers.fleet') }} *</label>
                                    <select id="driver_fleet_id" name="fleet_id" class="form-select @error('fleet_id') is-invalid @enderror" required data-driver-fleet data-searchable-database data-search-placeholder="{{ __('ui.search_options') }}" data-no-results="{{ __('ui.no_option_match') }}" data-option-icon="fa-warehouse">
                                        <option value="">{{ __('drivers.choose_fleet') }}</option>
                                        @foreach ($fleets as $fleet)
                                            <option value="{{ $fleet->id }}" @selected((int) old('fleet_id') === $fleet->id)>{{ $fleet->name }} &middot; {{ $fleet->code }}</option>
                                        @endforeach
                                    </select>
                                    @error('fleet_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label for="driver_department_id" class="form-label">{{ __('drivers.department') }}</label>
                                    <select id="driver_department_id" name="department_id" class="form-select @error('department_id') is-invalid @enderror" data-driver-department data-searchable-database data-search-placeholder="{{ __('ui.search_options') }}" data-no-results="{{ __('ui.no_option_match') }}" data-option-icon="fa-sitemap">
                                        <option value="">{{ __('drivers.choose_department') }}</option>
                                        @foreach ($departmentsForForm as $department)
                                            <option value="{{ $department->id }}" data-fleet-id="{{ $department->fleet_id }}" @selected((int) old('department_id') === $department->id)>{{ $department->name }}{{ $department->code ? ' - '.$department->code : '' }}</option>
                                        @endforeach
                                    </select>
                                    @error('department_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label for="driver_identifier_type" class="form-label">{{ __('drivers.identifier_type') }}</label>
                                    <select id="driver_identifier_type" name="identifier_type" class="form-select @error('identifier_type') is-invalid @enderror" data-driver-field="identifier_type">
                                        <option value="rfid" @selected(old('identifier_type', 'rfid') === 'rfid')>{{ __('drivers.identifier_rfid') }}</option>
                                        <option value="ibutton" @selected(old('identifier_type') === 'ibutton')>{{ __('drivers.identifier_ibutton') }}</option>
                                        <option value="nfc" @selected(old('identifier_type') === 'nfc')>{{ __('drivers.identifier_nfc') }}</option>
                                    </select>
                                    @error('identifier_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label for="driver_rfid_uid" class="form-label">{{ __('drivers.rfid_uid') }}</label>
                                    <input id="driver_rfid_uid" name="rfid_uid" class="form-control technical-code @error('rfid_uid') is-invalid @enderror" value="{{ old('rfid_uid') }}" placeholder="{{ __('drivers.badge_placeholder') }}" data-driver-field="rfid_uid">
                                    @error('rfid_uid')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="users-form-full">
                                    <label for="driver_tags" class="form-label">{{ __('drivers.tags') }}</label>
                                    <input id="driver_tags" name="tags" class="form-control @error('tags') is-invalid @enderror" value="{{ is_array(old('tags')) ? implode(', ', old('tags')) : old('tags') }}" placeholder="{{ __('drivers.tags_placeholder') }}" data-driver-field="tags">
                                    @error('tags')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="users-form-full">
                                    <div class="driver-vehicle-selector-heading">
                                        <span><strong>{{ __('drivers.choose_vehicles') }}</strong><small>{{ __('drivers.vehicle_help') }}</small></span>
                                    </div>
                                    <div class="driver-vehicle-toolbar">
                                        <label class="driver-vehicle-search">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                            <input type="search" placeholder="{{ __('drivers.vehicle_search_placeholder') }}" data-driver-vehicle-search>
                                        </label>
                                        <span class="driver-vehicle-count" data-driver-vehicle-count>{{ __('drivers.vehicle_selected_count', ['count' => 0]) }}</span>
                                    </div>
                                    <div class="driver-vehicle-selector" data-driver-vehicles>
                                        @foreach ($vehiclesForForm as $vehicle)
                                            <label class="driver-vehicle-option" data-driver-vehicle data-fleet-id="{{ $vehicle->fleet_id }}">
                                                <input type="checkbox" name="authorized_vehicle_ids[]" value="{{ $vehicle->id }}" @checked(in_array($vehicle->id, $oldVehicleIds, true))>
                                                <span class="driver-vehicle-option-icon"><i class="fa-solid fa-car-side"></i></span>
                                                <span><strong>{{ $vehicle->name }}</strong><small>{{ $vehicle->registration_number }}</small></span>
                                                <span class="driver-vehicle-option-check"><i class="fa-solid fa-check"></i></span>
                                            </label>
                                        @endforeach
                                        <p class="driver-no-vehicle" data-driver-no-vehicle>{{ __('drivers.choose_fleet_for_vehicles') }}</p>
                                    </div>
                                    @error('authorized_vehicle_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    @error('authorized_vehicle_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label for="driver_status" class="form-label">{{ __('drivers.status') }} *</label>
                                    <select id="driver_status" name="status" class="form-select @error('status') is-invalid @enderror" required data-driver-field="status">
                                        <option value="active" @selected(old('status', 'active') === 'active')>{{ __('drivers.status_active') }}</option>
                                        <option value="inactive" @selected(old('status') === 'inactive')>{{ __('drivers.status_inactive') }}</option>
                                    </select>
                                    @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </section>

                        <section class="driver-form-section">
                            <div class="driver-form-section-header"><i class="fa-solid fa-address-book"></i><h3>{{ __('drivers.contact') }}</h3></div>
                            <div class="users-form-grid">
                                <div><label for="driver_phone" class="form-label">{{ __('drivers.phone') }}</label><input id="driver_phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="{{ __('drivers.phone_placeholder') }}" data-driver-field="phone">@error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                                <div><label for="driver_email" class="form-label">{{ __('drivers.email') }}</label><input id="driver_email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="{{ __('drivers.email_placeholder') }}" data-driver-field="email">@error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                            </div>
                        </section>

                        <section class="driver-form-section">
                            <div class="driver-form-section-header"><i class="fa-solid fa-location-dot"></i><h3>{{ __('drivers.location') }}</h3></div>
                            <div class="users-form-grid">
                                <div class="users-form-full driver-address-search" data-driver-address-search data-empty-text="{{ __('drivers.address_no_results') }}" data-error-text="{{ __('drivers.address_search_error') }}">
                                    <label for="driver_address" class="form-label">{{ __('drivers.address') }}</label>
                                    <div class="driver-address-input-shell">
                                        <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                                        <input id="driver_address" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}" placeholder="{{ __('drivers.address_placeholder') }}" autocomplete="off" data-driver-field="address" data-driver-address-input aria-autocomplete="list" aria-controls="driverAddressSuggestions">
                                        <span class="driver-address-spinner" data-driver-address-spinner hidden><i class="fa-solid fa-spinner fa-spin"></i></span>
                                    </div>
                                    <input type="hidden" name="location_latitude" value="{{ old('location_latitude') }}" data-driver-field="location_latitude" data-driver-address-latitude>
                                    <input type="hidden" name="location_longitude" value="{{ old('location_longitude') }}" data-driver-field="location_longitude" data-driver-address-longitude>
                                    <div id="driverAddressSuggestions" class="driver-address-suggestions" role="listbox" data-driver-address-suggestions hidden></div>
                                    @error('address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    @error('location_latitude')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    @error('location_longitude')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div>
                                    <label for="driver_location_radius_meters" class="form-label">{{ __('drivers.location_radius') }}</label>
                                    <select id="driver_location_radius_meters" name="location_radius_meters" class="form-select @error('location_radius_meters') is-invalid @enderror" data-driver-field="location_radius_meters">
                                        @foreach ([50, 100, 150, 250, 500, 1000] as $radius)
                                            <option value="{{ $radius }}" @selected((int) old('location_radius_meters', 150) === $radius)>{{ __('drivers.meters', ['value' => $radius]) }}</option>
                                        @endforeach
                                    </select>
                                    @error('location_radius_meters')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </section>

                        <section class="driver-form-section">
                            <div class="driver-form-section-header"><i class="fa-solid fa-id-badge"></i><h3>{{ __('drivers.license') }}</h3></div>
                            <div class="users-form-grid">
                                <div><label for="driver_license_number" class="form-label">{{ __('drivers.license_number') }}</label><input id="driver_license_number" name="license_number" class="form-control @error('license_number') is-invalid @enderror" value="{{ old('license_number') }}" placeholder="{{ __('drivers.license_number_placeholder') }}" data-driver-field="license_number">@error('license_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                                <div><label for="driver_license_type" class="form-label">{{ __('drivers.license_type') }}</label><input id="driver_license_type" name="license_type" class="form-control @error('license_type') is-invalid @enderror" value="{{ old('license_type') }}" placeholder="{{ __('drivers.license_type_placeholder') }}" data-driver-field="license_type">@error('license_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                                <div><label for="driver_license_issued_at" class="form-label">{{ __('drivers.license_issued_at') }}</label><input id="driver_license_issued_at" type="date" name="license_issued_at" class="form-control @error('license_issued_at') is-invalid @enderror" value="{{ old('license_issued_at') }}" data-driver-field="license_issued_at">@error('license_issued_at')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                                <div><label for="driver_license_expires_at" class="form-label">{{ __('drivers.license_expires_at') }}</label><input id="driver_license_expires_at" type="date" name="license_expires_at" class="form-control @error('license_expires_at') is-invalid @enderror" value="{{ old('license_expires_at') }}" data-driver-field="license_expires_at">@error('license_expires_at')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                            </div>
                        </section>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn users-cancel-button" data-bs-dismiss="modal">{{ __('drivers.cancel') }}</button>
                        <button type="submit" class="btn btn-primary" data-loading-button data-driver-submit>{{ $editingDriverId ? __('drivers.save') : __('drivers.create') }}</button>
                    </div>
                </form>
            </div>
        </div>
        </div>
    @endif

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/dashboard-sidebar.js') }}?v=20260716-fleet-submenu"></script>
    <script src="{{ asset('js/dashboard-controls.js') }}?v=20260529-shared-controls"></script>
    <script src="{{ asset('js/datatable-controls.js') }}?v=20260529-datatable-controls"></script>
    @include('partials.realtime-alerts')
    @if ($canManageDrivers)
        <script src="{{ asset('js/confirm-delete.js') }}?v=20260529-delete-confirm"></script>
        <script src="{{ asset('js/form-validation.js') }}?v=20260529-form-validation"></script>
        <script src="{{ asset('js/form-loading.js') }}?v=20260529-form-loading"></script>
        <script src="{{ asset('js/searchable-select.js') }}?v=20260719-database-selects"></script>
        <script src="{{ asset('js/driver-address-search.js') }}?v=20260719-driver-geofence"></script>
        <script>
        (() => {
            const form = document.querySelector('[data-driver-form]');
            if (!form) return;

            const modalElement = document.getElementById('driverModal');
            const method = form.querySelector('[data-driver-method]');
            const editingId = form.querySelector('[data-driver-id]');
            const title = form.querySelector('[data-driver-title]');
            const submit = form.querySelector('[data-driver-submit]');
            const fleet = form.querySelector('[data-driver-fleet]');
            const department = form.querySelector('[data-driver-department]');
            const photo = form.querySelector('[data-driver-photo]');
            const photoPreview = form.querySelector('[data-driver-photo-preview]');
            const vehicleOptions = [...form.querySelectorAll('[data-driver-vehicle]')];
            const noVehicle = form.querySelector('[data-driver-no-vehicle]');
            const vehicleSearch = form.querySelector('[data-driver-vehicle-search]');
            const vehicleCount = form.querySelector('[data-driver-vehicle-count]');
            const storeAction = @json(route('drivers.store'));
            const vehicleCountTemplate = @json(__('drivers.vehicle_selected_count', ['count' => '__count__']));
            const chooseFleetVehicleText = @json(__('drivers.choose_fleet_for_vehicles'));
            const noVehicleText = @json(__('drivers.no_vehicle'));
            const noVehicleMatchText = @json(__('drivers.no_vehicle_match'));

            const setPreview = (url = '') => {
                photoPreview.innerHTML = url
                    ? `<img src="${url}" alt="">`
                    : '<i class="fa-solid fa-camera"></i>';
            };

            const updateVehicleCount = () => {
                const selected = vehicleOptions.filter((option) => {
                    const input = option.querySelector('input');
                    return input && !input.disabled && input.checked;
                }).length;

                if (vehicleCount) {
                    vehicleCount.textContent = vehicleCountTemplate.replace('__count__', selected);
                }
            };

            const filterAssignments = (fleetId, departmentId = '', keepVehicleSelection = false) => {
                [...department.options].forEach((option, index) => {
                    if (index === 0) return;
                    const visible = Boolean(fleetId) && option.dataset.fleetId === String(fleetId);
                    option.hidden = !visible;
                    option.disabled = !visible;
                });
                department.value = departmentId && [...department.options].some(option => !option.disabled && option.value === String(departmentId)) ? String(departmentId) : '';

                const searchQuery = (vehicleSearch?.value || '').trim().toLowerCase();
                let availableVehicles = 0;
                let visibleVehicles = 0;
                vehicleOptions.forEach((option) => {
                    const belongsToFleet = Boolean(fleetId) && option.dataset.fleetId === String(fleetId);
                    const matchesSearch = !searchQuery || option.textContent.toLowerCase().includes(searchQuery);
                    const visible = belongsToFleet && matchesSearch;
                    const input = option.querySelector('input');

                    option.hidden = !visible;
                    input.disabled = !belongsToFleet;

                    if (!belongsToFleet && !keepVehicleSelection) {
                        input.checked = false;
                    }

                    if (belongsToFleet) {
                        availableVehicles += 1;
                    }

                    if (visible) visibleVehicles += 1;
                });

                if (noVehicle) {
                    noVehicle.textContent = !fleetId
                        ? chooseFleetVehicleText
                        : (availableVehicles === 0 ? noVehicleText : noVehicleMatchText);
                    noVehicle.hidden = visibleVehicles > 0;
                }

                department.dispatchEvent(new Event('searchable-select:refresh'));
                updateVehicleCount();
            };

            const setField = (name, value) => {
                const field = form.querySelector(`[data-driver-field="${name}"]`);
                if (field) field.value = value ?? '';
            };

            fleet.addEventListener('change', () => {
                if (vehicleSearch) vehicleSearch.value = '';
                filterAssignments(fleet.value);
            });
            vehicleSearch?.addEventListener('input', () => filterAssignments(fleet.value, department.value, true));
            vehicleOptions.forEach((option) => {
                option.querySelector('input')?.addEventListener('change', updateVehicleCount);
            });
            photo.addEventListener('change', () => {
                const file = photo.files?.[0];
                setPreview(file ? URL.createObjectURL(file) : '');
            });

            document.addEventListener('click', (event) => {
                if (event.target.closest('[data-driver-create]')) {
                    form.reset();
                    form.action = storeAction;
                    method.value = 'POST';
                    editingId.value = '';
                    title.textContent = @json(__('drivers.create_title'));
                    submit.textContent = @json(__('drivers.create'));
                    setField('identifier_type', 'rfid');
                    setField('status', 'active');
                    setPreview();
                    if (vehicleSearch) vehicleSearch.value = '';
                    filterAssignments('');
                    return;
                }

                const button = event.target.closest('[data-driver-edit]');
                if (!button) return;

                const driver = JSON.parse(button.dataset.driver || '{}');
                form.action = button.dataset.action;
                method.value = 'PUT';
                editingId.value = button.dataset.driverId || '';
                title.textContent = @json(__('drivers.edit_title'));
                submit.textContent = @json(__('drivers.save'));
                fleet.value = driver.fleet_id || '';
                fleet.dispatchEvent(new Event('searchable-select:refresh'));
                if (vehicleSearch) vehicleSearch.value = '';
                filterAssignments(driver.fleet_id, driver.department_id, true);
                ['first_name', 'middle_name', 'last_name', 'employee_id', 'social_security_number', 'identifier_type', 'rfid_uid', 'phone', 'email', 'address', 'location_latitude', 'location_longitude', 'location_radius_meters', 'license_number', 'license_type', 'license_issued_at', 'license_expires_at', 'tags', 'status']
                    .forEach((name) => setField(name, driver[name]));
                const selectedVehicles = new Set((driver.vehicle_ids || []).map(String));
                vehicleOptions.forEach((option) => {
                    const input = option.querySelector('input');
                    input.checked = !input.disabled && selectedVehicles.has(input.value);
                });
                updateVehicleCount();
                setPreview(driver.photo_url || '');
            });

            filterAssignments(fleet.value, department.value);

            @if ($errors->any())
                bootstrap.Modal.getOrCreateInstance(modalElement).show();
            @endif
        })();

        const driverToast = document.querySelector('[data-app-toast]');
        if (driverToast) {
            const hideToast = () => driverToast.classList.add('is-hiding');
            driverToast.querySelector('[data-app-toast-close]')?.addEventListener('click', hideToast);
            setTimeout(hideToast, 5200);
        }
        </script>
    @endif
</body>
</html>
