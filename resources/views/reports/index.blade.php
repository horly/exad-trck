<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('reports.title') }} - EXAD Tracking</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=20260528-compact-ui">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260709-reports">
</head>
<body class="app-font-manrope dashboard-body">
    <div class="dashboard-shell">
        @include('partials.sidebar', ['active' => 'reports'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                @include('partials.sidebar-toggle')
                <div>
                    <p class="eyebrow mb-1">{{ __('reports.eyebrow') }}</p>
                    <h1>{{ __('reports.title') }}</h1>
                    <p class="dashboard-subtitle mb-0">{{ __('reports.subtitle') }}</p>
                </div>

                @include('partials.topbar-actions')
            </header>

            <section class="reports-summary-grid" aria-label="{{ __('reports.summary') }}">
                <article class="report-kpi-card is-blue">
                    <span><i class="fa-solid fa-location-dot"></i></span>
                    <strong>{{ $summary['positions'] }}</strong>
                    <small>{{ __('reports.summary_positions') }}</small>
                </article>
                <article class="report-kpi-card is-green">
                    <span><i class="fa-solid fa-route"></i></span>
                    <strong>{{ $summary['events'] }}</strong>
                    <small>{{ __('reports.summary_events') }}</small>
                </article>
                <article class="report-kpi-card is-red">
                    <span><i class="fa-solid fa-bell"></i></span>
                    <strong>{{ $summary['alerts'] }}</strong>
                    <small>{{ __('reports.summary_alerts') }}</small>
                </article>
                <article class="report-kpi-card is-amber">
                    <span><i class="fa-solid fa-calendar-check"></i></span>
                    <strong>{{ $summary['scheduled'] }}</strong>
                    <small>{{ __('reports.summary_scheduled') }}</small>
                </article>
            </section>

            <div data-datatable-container>
                @include('reports.partials.table')
            </div>

            <section class="report-schedules-card">
                <div class="report-section-title">
                    <div>
                        <p class="eyebrow mb-1">{{ __('reports.schedules_eyebrow') }}</p>
                        <h2>{{ __('reports.schedules_title') }}</h2>
                    </div>
                    <button type="button" class="btn users-primary-button report-schedule-create-button" data-bs-toggle="modal" data-bs-target="#scheduleReportModal">
                        <i class="fa-solid fa-calendar-plus"></i>
                        <span>{{ __('reports.new_schedule') }}</span>
                    </button>
                </div>

                <div class="report-schedule-list">
                    @forelse ($scheduledReports as $scheduledReport)
                        <article class="report-schedule-item">
                            <span class="report-schedule-icon"><i class="fa-solid fa-file-lines"></i></span>
                            <div>
                                <strong>{{ $scheduledReport->name }}</strong>
                                <small>{{ __('reports.schedule_meta', [
                                    'type' => __('reports.type_'.$scheduledReport->type),
                                    'frequency' => __('reports.frequency_'.$scheduledReport->frequency),
                                    'format' => __('reports.format_'.$scheduledReport->format),
                                ]) }}</small>
                            </div>
                            <form method="POST" action="{{ route('reports.schedules.destroy', $scheduledReport) }}" data-confirm-delete data-confirm-title="{{ __('reports.delete_schedule_title') }}" data-confirm-message="{{ __('reports.delete_schedule_text', ['name' => $scheduledReport->name]) }}" data-confirm-submit="{{ __('reports.delete_schedule_confirm') }}" data-confirm-processing="{{ __('reports.processing') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn users-action-button users-action-delete" aria-label="{{ __('reports.delete_schedule') }}">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </article>
                    @empty
                        <p class="report-empty-text">{{ __('reports.no_schedules') }}</p>
                    @endforelse
                </div>
            </section>
        </main>
    </div>

    <div class="modal fade users-modal reports-modal" id="scheduleReportModal" tabindex="-1" aria-labelledby="scheduleReportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ route('reports.schedules.store') }}" data-loading-form data-loading-text="{{ __('reports.processing') }}">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title" id="scheduleReportModalLabel">{{ __('reports.schedule_modal_title') }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('reports.cancel') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="scheduleName">{{ __('reports.schedule_name') }} *</label>
                            <input id="scheduleName" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" placeholder="{{ __('reports.schedule_name_placeholder') }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="scheduleType">{{ __('reports.report_type') }} *</label>
                            <select id="scheduleType" class="form-select" name="type" required>
                                @foreach ($typeOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type', $filters['type']) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="scheduleFrequency">{{ __('reports.frequency') }} *</label>
                            <select id="scheduleFrequency" class="form-select" name="frequency" required>
                                <option value="daily">{{ __('reports.frequency_daily') }}</option>
                                <option value="weekly" selected>{{ __('reports.frequency_weekly') }}</option>
                                <option value="monthly">{{ __('reports.frequency_monthly') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="scheduleFormat">{{ __('reports.format') }} *</label>
                            <select id="scheduleFormat" class="form-select" name="format" required>
                                <option value="csv">{{ __('reports.format_csv') }}</option>
                                <option value="print">{{ __('reports.format_print') }}</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="scheduleRecipients">{{ __('reports.recipients') }}</label>
                            <input id="scheduleRecipients" class="form-control" name="recipients" value="{{ old('recipients') }}" placeholder="{{ __('reports.recipients_placeholder') }}">
                        </div>
                    </div>

                    <input type="hidden" name="period" value="{{ $filters['period'] }}">
                    <input type="hidden" name="date_from" value="{{ $filters['date_from']->toDateString() }}">
                    <input type="hidden" name="date_to" value="{{ $filters['date_to']->toDateString() }}">
                    <input type="hidden" name="fleet_id" value="{{ $filters['fleet_id'] }}">
                    <input type="hidden" name="vehicle_id" value="{{ $filters['vehicle_id'] }}">
                    <input type="hidden" name="device_id" value="{{ $filters['device_id'] }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn users-cancel-button" data-bs-dismiss="modal">{{ __('reports.cancel') }}</button>
                    <button type="submit" class="btn users-submit-button">
                        <i class="fa-solid fa-calendar-check"></i>
                        <span>{{ __('reports.schedule_save') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if (session('status'))
        @php($toastType = session('status_type', 'success'))
        <div class="app-toast app-toast-{{ $toastType }}" role="status" aria-live="polite" data-app-toast>
            <span class="app-toast-icon" aria-hidden="true">
                <i class="fa-solid {{ $toastType === 'danger' ? 'fa-trash-can' : 'fa-check' }}"></i>
            </span>
            <span class="app-toast-message">{{ session('status') }}</span>
            <button type="button" class="app-toast-close" aria-label="{{ __('reports.close_notification') }}" data-app-toast-close>
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/dashboard-sidebar.js') }}?v=20260626-responsive-sidebar-default"></script>
    <script src="{{ asset('js/dashboard-controls.js') }}?v=20260529-shared-controls"></script>
    <script src="{{ asset('js/form-loading.js') }}?v=20260529-loading"></script>
    <script src="{{ asset('js/datatable-controls.js') }}?v=20260709-reports"></script>
    <script src="{{ asset('js/confirm-delete.js') }}?v=20260602-confirm-delete"></script>
    @include('partials.realtime-alerts')
</body>
</html>
