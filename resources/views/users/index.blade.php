<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('users.title') }} - EXAD Tracking</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=20260528-compact-ui">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260602-sidebar-version-global">
</head>
<body class="app-font-manrope dashboard-body">
    <div class="dashboard-shell">
        @include('partials.sidebar', ['active' => 'users'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('users.title') }}</h1>
                    <p class="dashboard-breadcrumb">{{ __('users.breadcrumb') }}</p>
                </div>

                @include('partials.topbar-actions')
            </header>

            <div class="users-page-actions">
                <button type="button" class="btn btn-primary users-primary-button" data-bs-toggle="modal" data-bs-target="#createUserModal" data-user-create>
                    <i class="fa-solid fa-user-plus"></i>
                    <span>{{ __('users.new_user') }}</span>
                </button>
            </div>

            <div data-datatable-container>
                @include('users.partials.table')
            </div>
        </main>
    </div>

    <div class="modal fade users-modal login-history-modal" id="loginHistoryModal" tabindex="-1" aria-labelledby="loginHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered login-history-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="loginHistoryModalLabel">
                        <i class="fa-regular fa-clock"></i>
                        <span data-history-title>{{ __('users.login_history_title', ['name' => '']) }}</span>
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('users.cancel') }}"></button>
                </div>

                <div class="modal-body">
                    <div class="history-toolbar">
                        <label class="users-search history-search" for="historySearch">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input id="historySearch" type="search" placeholder="{{ __('users.search') }}" data-history-search>
                        </label>
                    </div>

                    <div class="users-table-card">
                        <div class="table-responsive">
                            <table class="table align-middle users-table history-table">
                                <thead>
                                    <tr>
                                        <th>
                                            <button class="datatable-sort-link" type="button" data-history-sort="number">
                                                <span>{{ __('users.history_number') }}</span>
                                                <i class="fa-solid fa-sort"></i>
                                            </button>
                                        </th>
                                        <th>
                                            <button class="datatable-sort-link" type="button" data-history-sort="device">
                                                <span>{{ __('users.history_device') }}</span>
                                                <i class="fa-solid fa-sort"></i>
                                            </button>
                                        </th>
                                        <th>
                                            <button class="datatable-sort-link" type="button" data-history-sort="ip">
                                                <span>{{ __('users.history_ip') }}</span>
                                                <i class="fa-solid fa-sort"></i>
                                            </button>
                                        </th>
                                        <th>
                                            <button class="datatable-sort-link" type="button" data-history-sort="date">
                                                <span>{{ __('users.history_date') }}</span>
                                                <i class="fa-solid fa-sort"></i>
                                            </button>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody data-history-body>
                                    <tr>
                                        <td colspan="4" class="empty-state">{{ __('users.history_empty') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="datatable-footer history-footer">
                        <p data-history-summary>{{ __('users.pagination_summary', ['first' => 0, 'last' => 0, 'total' => 0]) }}</p>
                        <nav class="datatable-pagination" aria-label="{{ __('users.pagination') }}" data-history-pagination></nav>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn users-cancel-button" data-bs-dismiss="modal">{{ __('users.close') }}</button>
                </div>
            </div>
        </div>
    </div>

    @if (session('status'))
        @php($toastType = session('status_type', 'success'))
        <div class="app-toast app-toast-{{ $toastType }}" role="status" aria-live="polite" data-app-toast>
            <span class="app-toast-icon" aria-hidden="true">
                <i class="fa-solid {{ $toastType === 'danger' ? 'fa-trash-can' : 'fa-check' }}"></i>
            </span>
            <span class="app-toast-message">{{ session('status') }}</span>
            <button type="button" class="app-toast-close" aria-label="{{ __('users.close_notification') }}" data-app-toast-close>
                <i class="fa-solid fa-xmark"></i>
            </button>
            <span class="app-toast-progress" aria-hidden="true"></span>
        </div>
    @endif

    <div
        class="modal fade users-modal"
        id="createUserModal"
        tabindex="-1"
        aria-labelledby="createUserModalLabel"
        aria-hidden="true"
        data-create-title="{{ __('users.create_title') }}"
        data-edit-title="{{ __('users.edit_title') }}"
        data-create-button="{{ __('users.create_button') }}"
        data-update-button="{{ __('users.update_button') }}"
        data-password-label="{{ __('users.password') }}"
        data-password-confirmation-label="{{ __('users.password_confirmation') }}"
        data-password-optional="{{ __('users.password_optional') }}"
    >
        <div class="modal-dialog modal-dialog-centered users-modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="createUserModalLabel">{{ __('users.create_title') }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('users.cancel') }}"></button>
                </div>

                <form method="POST" action="{{ route('users.store') }}" novalidate data-validate-form data-required-message="{{ __('validation.required') }}" data-email-message="{{ __('validation.email') }}" data-store-action="{{ route('users.store') }}" data-loading-form data-loading-text="{{ __('users.processing') }}">
                    @csrf
                    <input type="hidden" name="_method" value="PUT" disabled data-method-field>
                    <div class="modal-body">
                        <div class="users-form-grid">
                            <div>
                                <label for="name" class="form-label">{{ __('users.name') }} *</label>
                                <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="{{ __('users.name_placeholder') }}" required>
                                @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label for="email" class="form-label">{{ __('users.email') }} *</label>
                                <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="{{ __('users.email_placeholder') }}" required>
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="grid-full">
                                <label for="password" class="form-label" data-password-label>{{ __('users.password') }} *</label>
                                <div class="password-field">
                                    <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="{{ __('users.password_placeholder') }}" required data-password-input data-password-main>
                                    <button type="button" class="password-eye" data-password-toggle aria-label="{{ __('auth.toggle_password') }}">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                                <ul class="password-rules">
                                    <li data-password-rule="min">{{ __('users.password_min') }}</li>
                                    <li data-password-rule="mixed">{{ __('users.password_mixed') }}</li>
                                    <li data-password-rule="number">{{ __('users.password_number') }}</li>
                                    <li data-password-rule="symbol">{{ __('users.password_symbol') }}</li>
                                </ul>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="grid-full">
                                <label for="password_confirmation" class="form-label" data-password-confirmation-label>{{ __('users.password_confirmation') }} *</label>
                                <div class="password-field">
                                    <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" placeholder="{{ __('users.password_confirmation_placeholder') }}" required data-password-input data-password-confirmation>
                                    <button type="button" class="password-eye" data-password-toggle aria-label="{{ __('auth.toggle_password') }}">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                </div>
                                <p class="password-match-message" data-password-match>{{ __('users.password_match') }}</p>
                            </div>

                            <div>
                                <label for="role" class="form-label">{{ __('users.role') }} *</label>
                                <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" required>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->value }}" @selected(old('role', 'user') === $role->value)>
                                            {{ __('users.role_'.$role->value) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label for="phone" class="form-label">{{ __('users.phone') }}</label>
                                <input id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="{{ __('users.phone_placeholder') }}">
                                @error('phone')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="grid-full">
                                <label for="address" class="form-label">{{ __('users.address') }}</label>
                                <input id="address" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}" placeholder="{{ __('users.address_placeholder') }}">
                                @error('address')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn users-cancel-button" data-bs-dismiss="modal">{{ __('users.cancel') }}</button>
                        <button type="submit" class="btn btn-primary" data-loading-button data-submit-label>{{ __('users.create_button') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/dashboard-sidebar.js') }}?v=20260528-sidebar-toggle"></script>
    <script src="{{ asset('js/dashboard-controls.js') }}?v=20260529-shared-controls"></script>
    <script src="{{ asset('js/datatable-controls.js') }}?v=20260529-datatable-controls"></script>
    @include('partials.realtime-alerts')
    <script src="{{ asset('js/confirm-delete.js') }}?v=20260529-delete-confirm"></script>
    <script src="{{ asset('js/form-validation.js') }}?v=20260529-form-validation"></script>
    <script src="{{ asset('js/form-loading.js') }}?v=20260529-form-loading"></script>
    <script>
        document.querySelectorAll('[data-password-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = button.parentElement.querySelector('[data-password-input]');
                input.type = input.type === 'password' ? 'text' : 'password';
            });
        });

        const passwordInput = document.querySelector('[data-password-main]');
        const passwordConfirmationInput = document.querySelector('[data-password-confirmation]');
        const passwordMatchMessage = document.querySelector('[data-password-match]');
        const passwordRules = {
            min: document.querySelector('[data-password-rule="min"]'),
            mixed: document.querySelector('[data-password-rule="mixed"]'),
            number: document.querySelector('[data-password-rule="number"]'),
            symbol: document.querySelector('[data-password-rule="symbol"]'),
        };

        const setState = (element, isValid, isDirty) => {
            if (!element) {
                return;
            }

            element.classList.toggle('is-valid', isDirty && isValid);
            element.classList.toggle('is-invalid', isDirty && !isValid);
        };

        const updatePasswordFeedback = () => {
            if (!passwordInput || !passwordConfirmationInput) {
                return;
            }

            const password = passwordInput.value;
            const confirmation = passwordConfirmationInput.value;
            const isPasswordDirty = password.length > 0;
            const ruleStatus = {
                min: password.length >= 12,
                mixed: /[a-z]/.test(password) && /[A-Z]/.test(password),
                number: /[A-Za-z]/.test(password) && /\d/.test(password),
                symbol: /[^A-Za-z0-9]/.test(password),
            };
            const isPasswordValid = Object.values(ruleStatus).every(Boolean);

            Object.entries(ruleStatus).forEach(([rule, isValid]) => {
                setState(passwordRules[rule], isValid, isPasswordDirty);
            });

            passwordInput.classList.toggle('is-valid', isPasswordDirty && isPasswordValid);
            passwordInput.classList.toggle('is-invalid', isPasswordDirty && !isPasswordValid);

            const isConfirmationDirty = confirmation.length > 0;
            const isConfirmationValid = isConfirmationDirty && password === confirmation;
            passwordConfirmationInput.classList.toggle('is-valid', isConfirmationValid);
            passwordConfirmationInput.classList.toggle('is-invalid', isConfirmationDirty && !isConfirmationValid);

            if (passwordMatchMessage) {
                passwordMatchMessage.classList.toggle('is-valid', isConfirmationValid);
                passwordMatchMessage.classList.toggle('is-invalid', isConfirmationDirty && !isConfirmationValid);
            }
        };

        passwordInput?.addEventListener('input', updatePasswordFeedback);
        passwordConfirmationInput?.addEventListener('input', updatePasswordFeedback);

        const userModal = document.getElementById('createUserModal');
        const userForm = userModal?.querySelector('form');
        const methodField = userForm?.querySelector('[data-method-field]');
        const modalTitle = userModal?.querySelector('.modal-title');
        const submitLabel = userForm?.querySelector('[data-submit-label]');
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const roleInput = document.getElementById('role');
        const phoneInput = document.getElementById('phone');
        const addressInput = document.getElementById('address');
        const passwordLabel = userForm?.querySelector('[data-password-label]');
        const confirmationLabel = userForm?.querySelector('[data-password-confirmation-label]');

        const resetPasswordFeedback = () => {
            passwordInput?.classList.remove('is-valid', 'is-invalid');
            passwordConfirmationInput?.classList.remove('is-valid', 'is-invalid');
            passwordMatchMessage?.classList.remove('is-valid', 'is-invalid');
            Object.values(passwordRules).forEach((rule) => rule?.classList.remove('is-valid', 'is-invalid'));
        };

        const setCreateMode = () => {
            if (!userModal || !userForm) {
                return;
            }

            userForm.reset();
            userForm.action = userForm.dataset.storeAction;
            delete userForm.dataset.loading;
            methodField.disabled = true;
            modalTitle.textContent = userModal.dataset.createTitle;
            submitLabel.textContent = userModal.dataset.createButton;
            passwordInput.required = true;
            passwordConfirmationInput.required = true;
            passwordLabel.textContent = `${userModal.dataset.passwordLabel} *`;
            confirmationLabel.textContent = `${userModal.dataset.passwordConfirmationLabel} *`;
            resetPasswordFeedback();
        };

        const setEditMode = (button) => {
            if (!userModal || !userForm) {
                return;
            }

            userForm.reset();
            userForm.action = button.dataset.updateUrl;
            delete userForm.dataset.loading;
            methodField.disabled = false;
            modalTitle.textContent = userModal.dataset.editTitle;
            submitLabel.textContent = userModal.dataset.updateButton;
            nameInput.value = button.dataset.name || '';
            emailInput.value = button.dataset.email || '';
            roleInput.value = button.dataset.role || 'user';
            phoneInput.value = button.dataset.phone || '';
            addressInput.value = button.dataset.address || '';
            passwordInput.value = '';
            passwordConfirmationInput.value = '';
            passwordInput.required = false;
            passwordConfirmationInput.required = false;
            passwordLabel.textContent = userModal.dataset.passwordOptional;
            confirmationLabel.textContent = userModal.dataset.passwordConfirmationLabel;
            resetPasswordFeedback();
        };

        document.addEventListener('click', (event) => {
            if (event.target.closest('[data-user-create]')) {
                setCreateMode();
                return;
            }

            const editButton = event.target.closest('[data-user-edit]');

            if (editButton) {
                setEditMode(editButton);
            }
        });

        userModal?.addEventListener('hidden.bs.modal', setCreateMode);

        const toast = document.querySelector('[data-app-toast]');
        if (toast) {
            const hideToast = () => toast.classList.add('is-hiding');
            toast.querySelector('[data-app-toast-close]')?.addEventListener('click', hideToast);
            setTimeout(hideToast, 5200);
        }

        window.exadLoginHistories = @json($loginHistories);
        const historyModal = document.getElementById('loginHistoryModal');
        const historyTitle = historyModal?.querySelector('[data-history-title]');
        const historySearch = historyModal?.querySelector('[data-history-search]');
        const historyBody = historyModal?.querySelector('[data-history-body]');
        const historySummary = historyModal?.querySelector('[data-history-summary]');
        const historyPagination = historyModal?.querySelector('[data-history-pagination]');
        const historyPerPage = 5;
        let activeHistoryRows = [];
        let historyPage = 1;
        let historySort = null;
        let historyDirection = 'asc';

        const escapeHtml = (value) => String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        const renderHistories = () => {
            if (!historyBody || !historySummary || !historyPagination) {
                return;
            }

            const query = (historySearch?.value || '').trim().toLowerCase();
            let rows = activeHistoryRows.filter((row) => {
                return row.device.toLowerCase().includes(query)
                    || row.ip.toLowerCase().includes(query)
                    || row.date.toLowerCase().includes(query);
            });
            const total = rows.length;
            const lastPage = Math.max(Math.ceil(total / historyPerPage), 1);

            if (historySort) {
                rows = [...rows].sort((first, second) => {
                    const firstValue = historySort === 'number' ? activeHistoryRows.indexOf(first) + 1 : first[historySort];
                    const secondValue = historySort === 'number' ? activeHistoryRows.indexOf(second) + 1 : second[historySort];
                    const result = String(firstValue).localeCompare(String(secondValue), undefined, { numeric: true });

                    return historyDirection === 'asc' ? result : -result;
                });
            }

            historyPage = Math.min(historyPage, lastPage);
            const firstIndex = total === 0 ? 0 : (historyPage - 1) * historyPerPage + 1;
            const lastIndex = Math.min(historyPage * historyPerPage, total);
            const pageRows = rows.slice((historyPage - 1) * historyPerPage, historyPage * historyPerPage);
            historySummary.textContent = `{{ __('users.pagination_summary', ['first' => '__FIRST__', 'last' => '__LAST__', 'total' => '__TOTAL__']) }}`
                .replace('__FIRST__', firstIndex)
                .replace('__LAST__', lastIndex)
                .replace('__TOTAL__', total);

            if (pageRows.length === 0) {
                historyBody.innerHTML = `<tr><td colspan="4" class="empty-state">{{ __('users.history_empty') }}</td></tr>`;
                historyPagination.innerHTML = '';
                return;
            }

            historyBody.innerHTML = pageRows.map((row, index) => `
                <tr>
                    <td>${firstIndex + index}</td>
                    <td>${escapeHtml(row.device)}</td>
                    <td>${escapeHtml(row.ip)}</td>
                    <td>${escapeHtml(row.date)}</td>
                </tr>
            `).join('');

            const pages = Array.from({ length: lastPage }, (_, index) => index + 1);
            historyPagination.innerHTML = `
                <button type="button" ${historyPage === 1 ? 'disabled' : ''} data-history-page="${historyPage - 1}">{{ __('users.previous') }}</button>
                ${pages.map((page) => `<button type="button" class="${page === historyPage ? 'active' : ''}" data-history-page="${page}">${page}</button>`).join('')}
                <button type="button" ${historyPage === lastPage ? 'disabled' : ''} data-history-page="${historyPage + 1}">{{ __('users.next') }}</button>
            `;
        };

        renderHistories();

        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-history-user]');

            if (!button) {
                return;
            }

            activeHistoryRows = window.exadLoginHistories?.[button.dataset.historyUserId] || [];
            historyPage = 1;
            historySort = null;
            historyDirection = 'asc';
            historyModal.querySelectorAll('[data-history-sort]').forEach((sortButton) => sortButton.classList.remove('active'));
            historyTitle.textContent = `{{ __('users.login_history_title', ['name' => '__NAME__']) }}`.replace('__NAME__', button.dataset.historyUserName || '');
            historySearch.value = '';
            renderHistories();
        });

        historySearch?.addEventListener('input', () => {
            historyPage = 1;
            renderHistories();
        });

        historyModal?.addEventListener('click', (event) => {
            const pageButton = event.target.closest('[data-history-page]');
            const sortButton = event.target.closest('[data-history-sort]');

            if (pageButton) {
                historyPage = Number(pageButton.dataset.historyPage);
                renderHistories();
                return;
            }

            if (sortButton) {
                const column = sortButton.dataset.historySort;

                historyDirection = historySort === column && historyDirection === 'asc' ? 'desc' : 'asc';
                historySort = column;
                historyPage = 1;
                historyModal.querySelectorAll('[data-history-sort]').forEach((button) => button.classList.remove('active'));
                sortButton.classList.add('active');
                renderHistories();
            }
        });

        @if ($errors->any())
            new bootstrap.Modal(document.getElementById('createUserModal')).show();
        @endif
    </script>
</body>
</html>
