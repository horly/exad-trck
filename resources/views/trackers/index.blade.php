<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('trackers.title') }} - EXAD Tracking</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=20260528-compact-ui">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260602-sidebar-version-global">
</head>
<body class="app-font-manrope dashboard-body">
    <div class="dashboard-shell">
        @include('partials.sidebar', ['active' => 'trackers'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <p class="eyebrow mb-1">{{ __('trackers.eyebrow') }}</p>
                    <h1>{{ __('trackers.title') }}</h1>
                </div>

                @include('partials.topbar-actions')
            </header>

            @if ($canManageDevices)
                <div class="users-page-actions">
                    <button type="button" class="btn btn-primary users-primary-button" data-bs-toggle="modal" data-bs-target="#trackerModal" data-tracker-create>
                        <i class="fa-solid fa-plus"></i>
                        <span>{{ __('trackers.new_tracker') }}</span>
                    </button>
                </div>
            @endif

            <div data-datatable-container>
                @include('trackers.partials.table')
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
            <button type="button" class="app-toast-close" aria-label="{{ __('trackers.close_notification') }}" data-app-toast-close>
                <i class="fa-solid fa-xmark"></i>
            </button>
            <span class="app-toast-progress" aria-hidden="true"></span>
        </div>
    @endif

    @if ($canManageDevices)
        <div class="modal fade users-modal" id="trackerModal" tabindex="-1" aria-labelledby="trackerModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered users-modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('trackers.store') }}" novalidate data-validate-form data-required-message="{{ __('validation.required') }}" data-email-message="{{ __('validation.email') }}" data-tracker-form data-loading-form data-loading-text="{{ __('trackers.processing') }}">
                        @csrf
                        <input type="hidden" name="_method" value="POST" data-tracker-method>

                        <div class="modal-header">
                            <h2 class="modal-title" id="trackerModalTitle" data-tracker-title>{{ __('trackers.create_title') }}</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('trackers.cancel') }}"></button>
                        </div>

                        <div class="modal-body">
                            <div class="users-form-grid">
                                <div>
                                    <label for="tracker_vehicle_id" class="form-label">{{ __('trackers.vehicle') }} *</label>
                                    <select id="tracker_vehicle_id" name="vehicle_id" class="form-select @error('vehicle_id') is-invalid @enderror" required data-tracker-vehicle>
                                        <option value="">{{ __('trackers.choose_vehicle') }}</option>
                                        @foreach ($manageableVehicles as $vehicle)
                                            <option
                                                value="{{ $vehicle->id }}"
                                                @selected((int) old('vehicle_id') === $vehicle->id)
                                                @if (! in_array($vehicle->id, $availableVehicleIds, true)) hidden disabled data-vehicle-assigned="true" @endif
                                            >
                                                {{ $vehicle->name }} · {{ $vehicle->registration_number }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('vehicle_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div>
                                    <label for="tracker_imei" class="form-label">{{ __('trackers.imei') }} *</label>
                                    <input id="tracker_imei" name="imei" class="form-control @error('imei') is-invalid @enderror" value="{{ old('imei') }}" placeholder="{{ __('trackers.imei_placeholder') }}" required data-tracker-imei>
                                    @error('imei')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div>
                                    <label for="tracker_name" class="form-label">{{ __('trackers.name') }}</label>
                                    <input id="tracker_name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="{{ __('trackers.name_placeholder') }}" data-tracker-name>
                                    @error('name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div>
                                    <label for="tracker_brand" class="form-label">{{ __('trackers.brand') }} *</label>
                                    <select id="tracker_brand" name="brand" class="form-select @error('brand') is-invalid @enderror" required data-tracker-brand>
                                        <option value="">{{ __('trackers.choose_brand') }}</option>
                                        @foreach ($trackerBrands as $brand)
                                            <option value="{{ $brand }}" @selected(old('brand') === $brand)>{{ __('trackers.brand_' . $brand) }}</option>
                                        @endforeach
                                    </select>
                                    @error('brand')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="field-shell search-select" data-tracker-model-shell @if (! old('brand')) hidden @endif>
                                    <label for="tracker_model" class="form-label">{{ __('trackers.model') }} *</label>
                                    <div class="search-select-control">
                                        <input
                                            id="tracker_model"
                                            name="model"
                                            class="form-control search-select-value @error('model') is-invalid @enderror"
                                            value="{{ old('model') }}"
                                            placeholder="{{ __('trackers.choose_model') }}"
                                            readonly
                                            required
                                            autocomplete="off"
                                            data-tracker-model
                                            @disabled(! old('brand'))
                                        >
                                        <button type="button" class="search-select-toggle" aria-label="{{ __('trackers.choose_model') }}" aria-expanded="false" aria-controls="trackerModelMenu" data-tracker-model-toggle>
                                            <i class="fa-solid fa-chevron-down"></i>
                                        </button>
                                    </div>
                                    <div class="search-select-menu" id="trackerModelMenu" hidden data-tracker-model-menu>
                                        <div class="search-select-search">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                            <input type="search" class="form-control" placeholder="{{ __('trackers.model_search_placeholder') }}" data-tracker-model-search>
                                        </div>
                                        <div class="search-select-options" role="listbox" aria-label="{{ __('trackers.model') }}" data-tracker-model-options></div>
                                    </div>
                                    @error('model')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div>
                                    <label for="tracker_sim_number" class="form-label">{{ __('trackers.sim_number') }}</label>
                                    <input id="tracker_sim_number" name="sim_number" class="form-control @error('sim_number') is-invalid @enderror" value="{{ old('sim_number') }}" placeholder="{{ __('trackers.sim_placeholder') }}" data-tracker-sim>
                                    @error('sim_number')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="field-shell search-select" data-tracker-operator-shell>
                                    <label for="tracker_operator_name" class="form-label">{{ __('trackers.operator_name') }}</label>
                                    <div class="search-select-control">
                                        <input
                                            id="tracker_operator_name"
                                            name="operator_name"
                                            class="form-control search-select-value @error('operator_name') is-invalid @enderror"
                                            value="{{ old('operator_name') }}"
                                            placeholder="{{ __('trackers.choose_operator') }}"
                                            readonly
                                            autocomplete="off"
                                            data-tracker-operator
                                        >
                                        <button type="button" class="search-select-toggle" aria-label="{{ __('trackers.choose_operator') }}" aria-expanded="false" aria-controls="trackerOperatorMenu" data-tracker-operator-toggle>
                                            <i class="fa-solid fa-chevron-down"></i>
                                        </button>
                                    </div>
                                    <div class="search-select-menu" id="trackerOperatorMenu" hidden data-tracker-operator-menu>
                                        <div class="search-select-search">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                            <input type="search" class="form-control" placeholder="{{ __('trackers.operator_search_placeholder') }}" data-tracker-operator-search>
                                        </div>
                                        <div class="search-select-options" role="listbox" aria-label="{{ __('trackers.operator_name') }}" data-tracker-operator-options></div>
                                    </div>
                                    @error('operator_name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div>
                                    <label for="tracker_protocol" class="form-label">{{ __('trackers.protocol') }} *</label>
                                    <select id="tracker_protocol" name="protocol" class="form-select @error('protocol') is-invalid @enderror" required data-tracker-protocol>
                                        <option value="TCP" @selected(old('protocol', 'TCP') === 'TCP')>TCP</option>
                                        <option value="UDP" @selected(old('protocol') === 'UDP')>UDP</option>
                                        <option value="HTTP" @selected(old('protocol') === 'HTTP')>HTTP</option>
                                    </select>
                                    @error('protocol')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn users-cancel-button" data-bs-dismiss="modal">{{ __('trackers.cancel') }}</button>
                            <button type="submit" class="btn btn-primary" data-loading-button data-tracker-submit>{{ __('trackers.create') }}</button>
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
    @if ($canManageDevices)
        <script src="{{ asset('js/confirm-delete.js') }}?v=20260529-delete-confirm"></script>
        <script src="{{ asset('js/form-validation.js') }}?v=20260529-form-validation"></script>
        <script src="{{ asset('js/form-loading.js') }}?v=20260529-form-loading"></script>
        <script>
            (() => {
                const form = document.querySelector('[data-tracker-form]');
                if (!form) {
                    return;
                }

                const title = form.querySelector('[data-tracker-title]');
                const method = form.querySelector('[data-tracker-method]');
                const submit = form.querySelector('[data-tracker-submit]');
                const fields = {
                    vehicle: form.querySelector('[data-tracker-vehicle]'),
                    imei: form.querySelector('[data-tracker-imei]'),
                    name: form.querySelector('[data-tracker-name]'),
                    brand: form.querySelector('[data-tracker-brand]'),
                    model: form.querySelector('[data-tracker-model]'),
                    modelShell: form.querySelector('[data-tracker-model-shell]'),
                    modelToggle: form.querySelector('[data-tracker-model-toggle]'),
                    modelMenu: form.querySelector('[data-tracker-model-menu]'),
                    modelSearch: form.querySelector('[data-tracker-model-search]'),
                    modelOptions: form.querySelector('[data-tracker-model-options]'),
                    sim: form.querySelector('[data-tracker-sim]'),
                    operator: form.querySelector('[data-tracker-operator]'),
                    operatorShell: form.querySelector('[data-tracker-operator-shell]'),
                    operatorToggle: form.querySelector('[data-tracker-operator-toggle]'),
                    operatorMenu: form.querySelector('[data-tracker-operator-menu]'),
                    operatorSearch: form.querySelector('[data-tracker-operator-search]'),
                    operatorOptions: form.querySelector('[data-tracker-operator-options]'),
                    protocol: form.querySelector('[data-tracker-protocol]'),
                };
                const storeAction = @json(route('trackers.store'));
                const trackerModels = @json($trackerModels);
                const trackerOperators = @json($trackerOperators);
                let activeModelList = [];

                const resetVehicleOptionsForCreate = () => {
                    Array.from(fields.vehicle.options).forEach((option) => {
                        if (!option.dataset.vehicleAssigned) {
                            return;
                        }

                        option.hidden = true;
                        option.disabled = true;
                    });
                };

                const unlockCurrentVehicleForEdit = (vehicleId) => {
                    resetVehicleOptionsForCreate();

                    const option = fields.vehicle.querySelector(`option[value="${CSS.escape(vehicleId)}"]`);
                    if (!option) {
                        return;
                    }

                    option.hidden = false;
                    option.disabled = false;
                };

                const closeModelMenu = () => {
                    fields.modelMenu.hidden = true;
                    fields.modelToggle.setAttribute('aria-expanded', 'false');
                    fields.modelShell.classList.remove('is-open');
                };

                const openModelMenu = () => {
                    if (fields.model.disabled) {
                        return;
                    }

                    fields.modelMenu.hidden = false;
                    fields.modelToggle.setAttribute('aria-expanded', 'true');
                    fields.modelShell.classList.add('is-open');
                    fields.modelSearch.focus({ preventScroll: true });
                    fields.modelSearch.select();
                };

                const selectModel = (model) => {
                    fields.model.value = model;
                    fields.model.dispatchEvent(new Event('input', { bubbles: true }));
                    fields.model.dispatchEvent(new Event('change', { bubbles: true }));
                    closeModelMenu();
                };

                const closeOperatorMenu = () => {
                    fields.operatorMenu.hidden = true;
                    fields.operatorToggle.setAttribute('aria-expanded', 'false');
                    fields.operatorShell.classList.remove('is-open');
                };

                const openOperatorMenu = () => {
                    fields.operatorMenu.hidden = false;
                    fields.operatorToggle.setAttribute('aria-expanded', 'true');
                    fields.operatorShell.classList.add('is-open');
                    fields.operatorSearch.focus({ preventScroll: true });
                    fields.operatorSearch.select();
                };

                const selectOperator = (operator) => {
                    fields.operator.value = operator;
                    fields.operator.dispatchEvent(new Event('input', { bubbles: true }));
                    fields.operator.dispatchEvent(new Event('change', { bubbles: true }));
                    closeOperatorMenu();
                };

                const renderModelOptions = (search = '') => {
                    const query = search.trim().toLowerCase();
                    const matches = activeModelList.filter((model) => model.toLowerCase().includes(query));

                    fields.modelOptions.replaceChildren();

                    if (matches.length === 0) {
                        const empty = document.createElement('div');
                        empty.className = 'search-select-empty';
                        empty.textContent = @json(__('trackers.no_model_result'));
                        fields.modelOptions.append(empty);
                        return;
                    }

                    matches.forEach((model) => {
                        const option = document.createElement('button');
                        option.type = 'button';
                        option.className = 'search-select-option';
                        option.dataset.modelValue = model;
                        option.setAttribute('role', 'option');
                        option.setAttribute('aria-selected', fields.model.value === model ? 'true' : 'false');
                        option.textContent = model;
                        fields.modelOptions.append(option);
                    });
                };

                const renderOperatorOptions = (search = '') => {
                    const query = search.trim().toLowerCase();
                    const matches = trackerOperators.filter((operator) => operator.toLowerCase().includes(query));

                    fields.operatorOptions.replaceChildren();

                    if (query === '') {
                        const emptyChoice = document.createElement('button');
                        emptyChoice.type = 'button';
                        emptyChoice.className = 'search-select-option';
                        emptyChoice.dataset.operatorValue = '';
                        emptyChoice.setAttribute('role', 'option');
                        emptyChoice.setAttribute('aria-selected', fields.operator.value === '' ? 'true' : 'false');
                        emptyChoice.textContent = @json(__('trackers.no_operator_selected'));
                        fields.operatorOptions.append(emptyChoice);
                    }

                    if (matches.length === 0) {
                        const empty = document.createElement('div');
                        empty.className = 'search-select-empty';
                        empty.textContent = @json(__('trackers.no_operator_result'));
                        fields.operatorOptions.append(empty);
                        return;
                    }

                    matches.forEach((operator) => {
                        const option = document.createElement('button');
                        option.type = 'button';
                        option.className = 'search-select-option';
                        option.dataset.operatorValue = operator;
                        option.setAttribute('role', 'option');
                        option.setAttribute('aria-selected', fields.operator.value === operator ? 'true' : 'false');
                        option.textContent = operator;
                        fields.operatorOptions.append(option);
                    });
                };

                const populateModels = (brand, selectedModel = '') => {
                    activeModelList = trackerModels[brand] || [];

                    const hasBrand = Boolean(brand);
                    fields.model.disabled = !hasBrand;
                    fields.modelShell.hidden = !hasBrand;
                    fields.model.value = hasBrand ? selectedModel : '';
                    fields.modelSearch.value = '';
                    renderModelOptions();
                    closeModelMenu();
                };

                document.addEventListener('click', (event) => {
                    const modelOption = event.target.closest('[data-tracker-model-options] .search-select-option');
                    if (modelOption) {
                        selectModel(modelOption.dataset.modelValue || '');
                        return;
                    }

                    const operatorOption = event.target.closest('[data-tracker-operator-options] .search-select-option');
                    if (operatorOption) {
                        selectOperator(operatorOption.dataset.operatorValue || '');
                        return;
                    }

                    if (event.target.closest('[data-tracker-model-toggle], [data-tracker-model]')) {
                        closeOperatorMenu();
                        fields.modelMenu.hidden ? openModelMenu() : closeModelMenu();
                        return;
                    }

                    if (event.target.closest('[data-tracker-operator-toggle], [data-tracker-operator]')) {
                        closeModelMenu();
                        fields.operatorMenu.hidden ? openOperatorMenu() : closeOperatorMenu();
                        return;
                    }

                    if (!event.target.closest('[data-tracker-model-shell]')) {
                        closeModelMenu();
                    }

                    if (!event.target.closest('[data-tracker-operator-shell]')) {
                        closeOperatorMenu();
                    }

                    if (event.target.closest('[data-tracker-create]')) {
                        form.action = storeAction;
                        method.value = 'POST';
                        title.textContent = @json(__('trackers.create_title'));
                        submit.textContent = @json(__('trackers.create'));
                        form.reset();
                        resetVehicleOptionsForCreate();
                        populateModels('');
                        fields.operator.value = '';
                        fields.operatorSearch.value = '';
                        renderOperatorOptions();
                        closeOperatorMenu();
                        fields.protocol.value = 'TCP';
                        return;
                    }

                    const editButton = event.target.closest('[data-tracker-edit]');
                    if (!editButton) {
                        return;
                    }

                    form.action = editButton.dataset.action;
                    method.value = 'PUT';
                    title.textContent = @json(__('trackers.edit_title'));
                    submit.textContent = @json(__('trackers.save'));
                    unlockCurrentVehicleForEdit(editButton.dataset.vehicleId || '');
                    fields.vehicle.value = editButton.dataset.vehicleId || '';
                    fields.imei.value = editButton.dataset.imei || '';
                    fields.name.value = editButton.dataset.name || '';
                    fields.brand.value = editButton.dataset.brand || '';
                    populateModels(fields.brand.value, editButton.dataset.model || '');
                    fields.sim.value = editButton.dataset.simNumber || '';
                    fields.operator.value = editButton.dataset.operatorName || '';
                    fields.operatorSearch.value = '';
                    renderOperatorOptions();
                    closeOperatorMenu();
                    fields.protocol.value = editButton.dataset.protocol || 'TCP';
                });

                fields.brand.addEventListener('change', () => populateModels(fields.brand.value));
                fields.modelSearch.addEventListener('input', () => renderModelOptions(fields.modelSearch.value));
                fields.modelSearch.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        fields.modelOptions.querySelector('.search-select-option')?.click();
                    }

                    if (event.key === 'Escape') {
                        closeModelMenu();
                    }
                });
                fields.operatorSearch.addEventListener('input', () => renderOperatorOptions(fields.operatorSearch.value));
                fields.operatorSearch.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        fields.operatorOptions.querySelector('.search-select-option')?.click();
                    }

                    if (event.key === 'Escape') {
                        closeOperatorMenu();
                    }
                });
                renderOperatorOptions();

                @if ($errors->any())
                    populateModels(fields.brand.value, @json(old('model')));
                    renderOperatorOptions();
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('trackerModal')).show();
                @endif
            })();
        </script>
    @endif
    <script>
        const trackerToast = document.querySelector('[data-app-toast]');
        if (trackerToast) {
            const hideToast = () => trackerToast.classList.add('is-hiding');
            trackerToast.querySelector('[data-app-toast-close]')?.addEventListener('click', hideToast);
            setTimeout(hideToast, 5200);
        }
    </script>
</body>
</html>
