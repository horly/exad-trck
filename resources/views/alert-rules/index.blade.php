<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('alert_rules.title') }} - {{ $applicationSettings->app_name }}</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=20260528-compact-ui">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260719-database-selects">
</head>
<body class="app-font-manrope dashboard-body">
    <div class="dashboard-shell">
        @include('partials.sidebar', ['active' => 'alert-rules'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                @include('partials.sidebar-toggle')
                <div>
                    <p class="eyebrow mb-1">{{ __('alert_rules.eyebrow') }}</p>
                    <h1>{{ __('alert_rules.title') }}</h1>
                    <p class="dashboard-subtitle mb-0">{{ __('alert_rules.subtitle') }}</p>
                </div>

                @include('partials.topbar-actions')
            </header>

            <section class="alerts-summary-grid" aria-label="{{ __('alert_rules.title') }}">
                <article class="alert-summary-card">
                    <span class="alert-summary-icon alert-summary-total"><i class="fa-solid fa-sliders"></i></span>
                    <strong data-alert-stat="total">{{ $stats['total'] }}</strong>
                    <small>{{ __('alert_rules.rules_total') }}</small>
                </article>
                <article class="alert-summary-card">
                    <span class="alert-summary-icon alert-summary-new"><i class="fa-solid fa-circle-check"></i></span>
                    <strong data-alert-stat="active">{{ $stats['active'] }}</strong>
                    <small>{{ __('alert_rules.rules_active') }}</small>
                </article>
                <article class="alert-summary-card">
                    <span class="alert-summary-icon alert-summary-critical"><i class="fa-solid fa-satellite-dish"></i></span>
                    <strong data-alert-stat="equipment">{{ $stats['equipment'] }}</strong>
                    <small>{{ __('alert_rules.equipment_rules') }}</small>
                </article>
                <article class="alert-summary-card">
                    <span class="alert-summary-icon alert-summary-high"><i class="fa-solid fa-car-side"></i></span>
                    <strong data-alert-stat="vehicle">{{ $stats['vehicle'] }}</strong>
                    <small>{{ __('alert_rules.vehicle_rules') }}</small>
                </article>
            </section>

            <section class="alert-rule-guides">
                <article>
                    <i class="fa-solid fa-satellite-dish"></i>
                    <strong>{{ __('alert_rules.category_equipment') }}</strong>
                    <span>{{ __('alert_rules.explain_equipment') }}</span>
                </article>
                <article>
                    <i class="fa-solid fa-car-side"></i>
                    <strong>{{ __('alert_rules.category_vehicle') }}</strong>
                    <span>{{ __('alert_rules.explain_vehicle') }}</span>
                </article>
            </section>

            <div class="users-page-actions">
                <button type="button" class="btn btn-primary users-primary-button" data-rule-create data-bs-toggle="modal" data-bs-target="#alertRuleModal">
                    <i class="fa-solid fa-plus"></i>
                    <span>{{ __('alert_rules.add_rule') }}</span>
                </button>
            </div>

            <div data-datatable-container>
                @include('alert-rules.partials.table')
            </div>
        </main>
    </div>

    <div class="modal fade users-modal" id="alertRuleModal" tabindex="-1" aria-labelledby="alertRuleModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered users-modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('alert-rules.store') }}" data-alert-rule-form data-loading-form data-loading-text="{{ __('alert_rules.processing') }}">
                    @csrf
                    <input type="hidden" name="_method" value="POST" data-method-field>

                    <div class="modal-header">
                        <div class="form-modal-heading">
                            <span class="form-modal-heading-icon"><i class="fa-solid fa-bell"></i></span>
                            <h2 class="modal-title" id="alertRuleModalTitle">{{ __('alert_rules.create_rule') }}</h2>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('alert_rules.cancel') }}"></button>
                    </div>

                    <div class="modal-body">
                        <div class="users-form-grid alert-rule-form-grid">
                            <div class="grid-full">
                                <label for="rule_name" class="form-label">{{ __('alert_rules.name') }} *</label>
                                <input
                                    id="rule_name"
                                    name="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name') }}"
                                    placeholder="{{ __('alert_rules.rule_name_placeholder') }}"
                                    required
                                >
                                @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label for="rule_category" class="form-label">{{ __('alert_rules.category') }} *</label>
                                <select id="rule_category" name="category" class="form-select @error('category') is-invalid @enderror" required data-rule-category>
                                    <option value="equipment">{{ __('alert_rules.category_equipment') }}</option>
                                    <option value="vehicle">{{ __('alert_rules.category_vehicle') }}</option>
                                </select>
                                @error('category')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label for="rule_type" class="form-label">{{ __('alert_rules.type') }} *</label>
                                <select id="rule_type" name="type" class="form-select @error('type') is-invalid @enderror" required data-rule-type>
                                    @foreach ($typeGroups as $category => $types)
                                        <optgroup label="{{ __('alert_rules.category_'.$category) }}" data-type-category="{{ $category }}">
                                            @foreach ($types as $code => $label)
                                                <option value="{{ $code }}" data-category="{{ $category }}">{{ $label }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                @error('type')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label for="rule_severity" class="form-label">{{ __('alert_rules.severity') }} *</label>
                                <select id="rule_severity" name="severity" class="form-select @error('severity') is-invalid @enderror" required>
                                    @foreach ($severityOptions as $severity)
                                        <option value="{{ $severity }}">{{ __('alert_rules.severity_'.$severity) }}</option>
                                    @endforeach
                                </select>
                                @error('severity')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label for="rule_scope_type" class="form-label">{{ __('alert_rules.scope_type') }} *</label>
                                <select id="rule_scope_type" name="scope_type" class="form-select @error('scope_type') is-invalid @enderror" required data-scope-type>
                                    <option value="all">{{ __('alert_rules.scope_all') }}</option>
                                    <option value="fleet">{{ __('alert_rules.scope_fleet') }}</option>
                                    <option value="vehicle">{{ __('alert_rules.scope_vehicle') }}</option>
                                    <option value="device">{{ __('alert_rules.scope_device') }}</option>
                                </select>
                                @error('scope_type')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div data-scope-field="fleet" hidden>
                                <label for="rule_fleet_id" class="form-label">{{ __('alert_rules.fleet') }} *</label>
                                <select id="rule_fleet_id" name="fleet_id" class="form-select @error('fleet_id') is-invalid @enderror" data-searchable-database data-search-placeholder="{{ __('ui.search_options') }}" data-no-results="{{ __('ui.no_option_match') }}" data-option-icon="fa-warehouse">
                                    <option value="">{{ __('alert_rules.select_fleet') }}</option>
                                    @foreach ($fleets as $fleet)
                                        <option value="{{ $fleet->id }}">{{ $fleet->name }}{{ $fleet->code ? ' · '.$fleet->code : '' }}</option>
                                    @endforeach
                                </select>
                                @error('fleet_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div data-scope-field="vehicle" hidden>
                                <label for="rule_vehicle_id" class="form-label">{{ __('alert_rules.vehicle') }} *</label>
                                <select id="rule_vehicle_id" name="vehicle_id" class="form-select @error('vehicle_id') is-invalid @enderror" data-searchable-database data-search-placeholder="{{ __('ui.search_options') }}" data-no-results="{{ __('ui.no_option_match') }}" data-option-icon="fa-car-side">
                                    <option value="">{{ __('alert_rules.select_vehicle') }}</option>
                                    @foreach ($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}">{{ $vehicle->name }}{{ $vehicle->registration_number ? ' · '.$vehicle->registration_number : '' }}</option>
                                    @endforeach
                                </select>
                                @error('vehicle_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div data-scope-field="device" hidden>
                                <label for="rule_device_id" class="form-label">{{ __('alert_rules.device') }} *</label>
                                <select id="rule_device_id" name="device_id" class="form-select @error('device_id') is-invalid @enderror" data-searchable-database data-search-placeholder="{{ __('ui.search_options') }}" data-no-results="{{ __('ui.no_option_match') }}" data-option-icon="fa-satellite-dish">
                                    <option value="">{{ __('alert_rules.select_device') }}</option>
                                    @foreach ($devices as $device)
                                        <option value="{{ $device->id }}">{{ $device->name ?: $device->imei }} · {{ $device->imei }}</option>
                                    @endforeach
                                </select>
                                @error('device_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label for="rule_threshold_value" class="form-label">{{ __('alert_rules.threshold_value') }}</label>
                                <input
                                    id="rule_threshold_value"
                                    name="threshold_value"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control @error('threshold_value') is-invalid @enderror"
                                    value="{{ old('threshold_value') }}"
                                    placeholder="{{ __('alert_rules.threshold_value_placeholder') }}"
                                >
                                @error('threshold_value')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label for="rule_threshold_unit" class="form-label">{{ __('alert_rules.threshold_unit') }}</label>
                                <input
                                    id="rule_threshold_unit"
                                    name="threshold_unit"
                                    class="form-control @error('threshold_unit') is-invalid @enderror"
                                    value="{{ old('threshold_unit') }}"
                                    placeholder="{{ __('alert_rules.threshold_unit_placeholder') }}"
                                >
                                @error('threshold_unit')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="grid-full alert-rule-check-grid">
                                <span class="form-label">{{ __('alert_rules.channels') }}</span>
                                @foreach ($channelOptions as $channel)
                                    <label>
                                        <input type="checkbox" name="channels[]" value="{{ $channel }}" @checked($channel === 'platform')>
                                        <span>{{ __('alert_rules.channel_'.$channel) }}</span>
                                    </label>
                                @endforeach
                            </div>

                            <div class="grid-full alert-rule-check-grid">
                                <span class="form-label">{{ __('alert_rules.schedule_days') }}</span>
                                @foreach ($scheduleDays as $day)
                                    <label>
                                        <input type="checkbox" name="schedule_days[]" value="{{ $day }}">
                                        <span>{{ __('alert_rules.day_'.$day) }}</span>
                                    </label>
                                @endforeach
                            </div>

                            <div>
                                <label for="rule_starts_at" class="form-label">{{ __('alert_rules.starts_at') }}</label>
                                <input id="rule_starts_at" name="starts_at" type="time" class="form-control @error('starts_at') is-invalid @enderror" value="{{ old('starts_at') }}">
                                @error('starts_at')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label for="rule_ends_at" class="form-label">{{ __('alert_rules.ends_at') }}</label>
                                <input id="rule_ends_at" name="ends_at" type="time" class="form-control @error('ends_at') is-invalid @enderror" value="{{ old('ends_at') }}">
                                @error('ends_at')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="grid-full">
                                <input type="hidden" name="is_active" value="0">
                                <label class="feature-switch alert-rule-active-switch">
                                    <input type="checkbox" name="is_active" value="1" checked>
                                    <span>{{ __('alert_rules.active') }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn users-cancel-button" data-bs-dismiss="modal">{{ __('alert_rules.cancel') }}</button>
                        <button type="submit" class="btn btn-primary" data-loading-button>{{ __('alert_rules.create') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="app-toast app-toast-success" role="status" aria-live="polite" data-app-toast>
            <span class="app-toast-icon" aria-hidden="true">
                <i class="fa-solid fa-check"></i>
            </span>
            <span class="app-toast-message">{{ session('status') }}</span>
            <button type="button" class="app-toast-close" aria-label="{{ __('alert_rules.close_notification') }}" data-app-toast-close>
                <i class="fa-solid fa-xmark"></i>
            </button>
            <span class="app-toast-progress" aria-hidden="true"></span>
        </div>
    @endif

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/dashboard-sidebar.js') }}?v=20260626-responsive-sidebar-default"></script>
    <script src="{{ asset('js/dashboard-controls.js') }}?v=20260529-shared-controls"></script>
    <script src="{{ asset('js/datatable-controls.js') }}?v=20260709-alert-rules"></script>
    <script src="{{ asset('js/form-loading.js') }}?v=20260529-form-loading"></script>
    <script src="{{ asset('js/confirm-delete.js') }}?v=20260602-alert-rules"></script>
    <script src="{{ asset('js/searchable-select.js') }}?v=20260719-database-selects"></script>
    @include('partials.realtime-alerts')
    <script>
        const alertRuleModal = document.getElementById('alertRuleModal');
        const alertRuleForm = document.querySelector('[data-alert-rule-form]');
        const alertRuleTitle = document.getElementById('alertRuleModalTitle');
        const methodField = alertRuleForm?.querySelector('[data-method-field]');
        const categorySelect = alertRuleForm?.querySelector('[data-rule-category]');
        const typeSelect = alertRuleForm?.querySelector('[data-rule-type]');
        const scopeSelect = alertRuleForm?.querySelector('[data-scope-type]');
        const submitButton = alertRuleForm?.querySelector('[data-loading-button]');

        function setSelectValue(name, value) {
            const field = alertRuleForm?.querySelector(`[name="${name}"]`);
            if (field) {
                field.value = value || '';
                field.dispatchEvent(new Event('searchable-select:refresh'));
            }
        }

        function setCheckedGroup(name, values) {
            const selected = new Set((values || '').split(',').filter(Boolean));
            alertRuleForm?.querySelectorAll(`[name="${name}[]"]`).forEach((field) => {
                field.checked = selected.has(field.value);
            });
        }

        function syncScopeFields() {
            const selectedScope = scopeSelect?.value || 'all';
            alertRuleForm?.querySelectorAll('[data-scope-field]').forEach((field) => {
                field.hidden = field.dataset.scopeField !== selectedScope;
            });
        }

        function syncTypeCategory() {
            const selectedOption = typeSelect?.selectedOptions?.[0];
            if (selectedOption?.dataset.category && categorySelect) {
                categorySelect.value = selectedOption.dataset.category;
            }
        }

        function resetAlertRuleModal() {
            alertRuleForm?.reset();
            alertRuleForm.action = @json(route('alert-rules.store'));
            methodField.value = 'POST';
            alertRuleTitle.textContent = @json(__('alert_rules.create_rule'));
            submitButton.textContent = @json(__('alert_rules.create'));
            setCheckedGroup('channels', 'platform');
            setCheckedGroup('schedule_days', '');
            syncTypeCategory();
            syncScopeFields();
        }

        document.addEventListener('click', (event) => {
            if (event.target.closest('[data-rule-create]')) {
                resetAlertRuleModal();
                return;
            }

            const editButton = event.target.closest('[data-rule-edit]');

            if (!editButton || !alertRuleForm || !window.bootstrap) {
                return;
            }

            resetAlertRuleModal();
            alertRuleForm.action = editButton.dataset.action;
            methodField.value = 'PATCH';
            alertRuleTitle.textContent = @json(__('alert_rules.edit_rule'));
            submitButton.textContent = @json(__('alert_rules.save'));

            setSelectValue('name', editButton.dataset.name);
            setSelectValue('category', editButton.dataset.category);
            setSelectValue('type', editButton.dataset.type);
            setSelectValue('severity', editButton.dataset.severity);
            setSelectValue('scope_type', editButton.dataset.scopeType);
            setSelectValue('fleet_id', editButton.dataset.fleetId);
            setSelectValue('vehicle_id', editButton.dataset.vehicleId);
            setSelectValue('device_id', editButton.dataset.deviceId);
            setSelectValue('threshold_value', editButton.dataset.thresholdValue);
            setSelectValue('threshold_unit', editButton.dataset.thresholdUnit);
            setSelectValue('starts_at', editButton.dataset.startsAt);
            setSelectValue('ends_at', editButton.dataset.endsAt);
            setCheckedGroup('channels', editButton.dataset.channels || 'platform');
            setCheckedGroup('schedule_days', editButton.dataset.scheduleDays || '');
            alertRuleForm.querySelector('[name="is_active"][value="1"]').checked = editButton.dataset.isActive === '1';

            syncScopeFields();
            window.bootstrap.Modal.getOrCreateInstance(alertRuleModal).show();
        });

        scopeSelect?.addEventListener('change', syncScopeFields);
        typeSelect?.addEventListener('change', syncTypeCategory);

        const alertRuleToast = document.querySelector('[data-app-toast]');
        if (alertRuleToast) {
            const hideToast = () => alertRuleToast.classList.add('is-hiding');
            alertRuleToast.querySelector('[data-app-toast-close]')?.addEventListener('click', hideToast);
            setTimeout(hideToast, 5200);
        }

        @if ($errors->any())
            if (alertRuleModal && window.bootstrap) {
                window.bootstrap.Modal.getOrCreateInstance(alertRuleModal).show();
            }
        @endif
    </script>
</body>
</html>
