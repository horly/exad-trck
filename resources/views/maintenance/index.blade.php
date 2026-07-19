<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('maintenance.title') }} - EXAD Tracking</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=20260528-compact-ui">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260719-database-selects">
</head>
<body class="app-font-manrope dashboard-body">
@php
    $tab = request('tab', 'planning');
    $statItems = [
        ['key' => 'active', 'icon' => 'fa-list-check'],
        ['key' => 'due', 'icon' => 'fa-triangle-exclamation'],
        ['key' => 'scheduled_cost', 'icon' => 'fa-file-invoice-dollar'],
        ['key' => 'actual_cost', 'icon' => 'fa-coins'],
    ];
    $conditionItems = [
        ['label' => 'date', 'due' => 'next_due_date', 'reminder' => 'reminder_days', 'interval' => 'interval_days', 'type' => 'date', 'unit' => __('maintenance.days')],
        ['label' => 'odometer', 'due' => 'next_due_odometer_km', 'reminder' => 'reminder_odometer_km', 'interval' => 'interval_odometer_km', 'type' => 'number', 'unit' => __('maintenance.km')],
        ['label' => 'engine_hours', 'due' => 'next_due_engine_hours', 'reminder' => 'reminder_engine_hours', 'interval' => 'interval_engine_hours', 'type' => 'number', 'unit' => __('maintenance.hours')],
    ];
@endphp
<div class="dashboard-shell">
    @include('partials.sidebar', ['active' => 'maintenance'])
    <main class="dashboard-main">
        <header class="dashboard-topbar">@include('partials.sidebar-toggle')<div><p class="eyebrow mb-1">{{ __('maintenance.eyebrow') }}</p><h1>{{ __('maintenance.title') }}</h1></div>@include('partials.topbar-actions')</header>
        <div class="maintenance-page-head">
            <nav class="maintenance-tabs">
                <a class="{{ $tab === 'planning' ? 'active' : '' }}" href="{{ route('maintenance.index') }}"><i class="fa-regular fa-calendar"></i>{{ __('maintenance.planning') }}</a>
                <a class="{{ $tab === 'history' ? 'active' : '' }}" href="{{ route('maintenance.index', ['tab' => 'history']) }}"><i class="fa-solid fa-clock-rotate-left"></i>{{ __('maintenance.history') }}</a>
                <a class="{{ $tab === 'expenses' ? 'active' : '' }}" href="{{ route('maintenance.index', ['tab' => 'expenses']) }}"><i class="fa-solid fa-chart-column"></i>{{ __('maintenance.expenses') }}</a>
            </nav>
            <button class="btn btn-primary users-primary-button" data-bs-toggle="modal" data-bs-target="#maintenanceModal" data-maintenance-create><i class="fa-solid fa-plus"></i><span>{{ __('maintenance.new') }}</span></button>
        </div>

        <section class="maintenance-stats">
            @foreach($statItems as $statItem)
                <article class="maintenance-stat maintenance-stat-{{ $statItem['key'] }}">
                    <span class="maintenance-stat-icon"><i class="fa-solid {{ $statItem['icon'] }}"></i></span>
                    <strong class="maintenance-stat-value">{{ in_array($statItem['key'], ['scheduled_cost','actual_cost']) ? number_format((float)$stats[$statItem['key']], 2, ',', ' ').' USD' : $stats[$statItem['key']] }}</strong>
                    <span class="maintenance-stat-label">{{ __('maintenance.'.$statItem['key']) }}</span>
                </article>
            @endforeach
        </section>

        @if($tab === 'planning')
            <section class="users-table-card"><div class="table-responsive"><table class="table align-middle users-table maintenance-table">
                <thead><tr><th>{{ __('maintenance.vehicle') }}</th><th>{{ __('maintenance.name') }}</th><th>{{ __('maintenance.next_due') }}</th><th>{{ __('maintenance.garage') }}</th><th>{{ __('garages.status') }}</th><th class="text-end">{{ __('maintenance.actions') }}</th></tr></thead>
                <tbody>@forelse($plans as $plan)
                    @php
                        $dueItems = collect([
                            $plan->next_due_date?->format('d/m/Y'),
                            $plan->next_due_odometer_km !== null ? number_format((float)$plan->next_due_odometer_km, 0, ',', ' ').' km' : null,
                            $plan->next_due_engine_hours !== null ? number_format((float)$plan->next_due_engine_hours, 0, ',', ' ').' h' : null,
                        ])->filter()->join(' · ');
                        $planData = $plan->only(['id','vehicle_id','garage_id','name','description','maintenance_type','estimated_cost','is_recurring','next_due_date','reminder_days','interval_days','next_due_odometer_km','reminder_odometer_km','interval_odometer_km','next_due_engine_hours','reminder_engine_hours','interval_engine_hours']);
                    @endphp
                    <tr>
                        <td><strong>{{ $plan->vehicle?->name }}</strong><span class="technical-code table-secondary-line">{{ $plan->vehicle?->registration_number }} · {{ $plan->vehicle?->fleet?->name }}</span></td>
                        <td><strong>{{ $plan->name }}</strong><span class="technical-code table-secondary-line">{{ __('maintenance.'.$plan->maintenance_type) }}{{ $plan->is_recurring ? ' · '.__('maintenance.recurring') : '' }}</span></td>
                        <td>{{ $dueItems ?: __('maintenance.no_trigger') }}</td><td>{{ $plan->garage?->name ?: '-' }}</td>
                        <td><span class="maintenance-status maintenance-status-{{ $plan->status === 'paused' ? 'paused' : $plan->due_status }}">{{ __('maintenance.status_'.($plan->status === 'paused' ? 'paused' : $plan->due_status)) }}</span></td>
                        <td class="text-end"><div class="users-actions">
                            @if($plan->status !== 'completed')<button class="icon-action maintenance-complete-action" data-bs-toggle="modal" data-bs-target="#completeMaintenanceModal" data-maintenance-complete data-plan-id="{{ $plan->id }}" data-plan-name="{{ $plan->name }}" data-action="{{ route('maintenance.complete',$plan) }}" title="{{ __('maintenance.complete') }}"><i class="fa-solid fa-check"></i></button>@endif
                            <button class="icon-action icon-action-edit" data-bs-toggle="modal" data-bs-target="#maintenanceModal" data-maintenance-edit data-plan="{{ json_encode($planData) }}" data-action="{{ route('maintenance.update',$plan) }}" title="{{ __('maintenance.edit') }}"><i class="fa-regular fa-pen-to-square"></i></button>
                            @if($plan->status !== 'completed')<form method="POST" action="{{ route('maintenance.toggle',$plan) }}">@csrf @method('PATCH')<button class="icon-action" title="{{ $plan->status === 'paused' ? __('maintenance.resume') : __('maintenance.pause') }}"><i class="fa-solid {{ $plan->status === 'paused' ? 'fa-play' : 'fa-pause' }}"></i></button></form>@endif
                            <form method="POST" action="{{ route('maintenance.destroy',$plan) }}" data-confirm-delete data-confirm-title="{{ __('maintenance.delete_confirm_title') }}" data-confirm-message="{{ __('maintenance.delete_confirm_message',['name'=>$plan->name]) }}" data-confirm-cancel="{{ __('maintenance.cancel') }}" data-confirm-submit="{{ __('maintenance.delete_confirm_submit') }}" data-confirm-processing="{{ __('maintenance.processing') }}">@csrf @method('DELETE')<button class="icon-action icon-action-delete" title="{{ __('maintenance.delete') }}"><i class="fa-regular fa-trash-can"></i></button></form>
                        </div></td>
                    </tr>
                @empty<tr><td colspan="6" class="empty-state">{{ __('maintenance.empty') }}</td></tr>@endforelse</tbody>
            </table></div></section>
        @elseif($tab === 'history')
            <section class="users-table-card"><div class="table-responsive"><table class="table align-middle users-table"><thead><tr><th>{{ __('maintenance.performed_at') }}</th><th>{{ __('maintenance.vehicle') }}</th><th>{{ __('maintenance.completed_by') }}</th><th>{{ __('maintenance.garage') }}</th><th>{{ __('maintenance.completion_cost') }}</th><th>{{ __('maintenance.attachments') }}</th></tr></thead><tbody>
                @forelse($records as $record)<tr><td>{{ $record->performed_at->format('d/m/Y') }}</td><td><strong>{{ $record->vehicle?->name }}</strong><span class="technical-code table-secondary-line">{{ $record->vehicle?->registration_number }}</span></td><td><strong>{{ $record->name }}</strong><span class="technical-code table-secondary-line">{{ $record->notes }}</span></td><td>{{ $record->garage?->name ?: '-' }}</td><td>{{ $record->actual_cost !== null ? number_format((float)$record->actual_cost,2,',',' ').' USD' : '-' }}</td><td>@foreach($record->documents as $document)<a class="maintenance-document-link" href="{{ Storage::disk($document->disk)->url($document->path) }}" target="_blank" title="{{ $document->original_name }}"><i class="fa-solid fa-paperclip"></i></a>@endforeach</td></tr>
                @empty<tr><td colspan="6" class="empty-state">{{ __('maintenance.empty') }}</td></tr>@endforelse
            </tbody></table></div></section>{{ $records->withQueryString()->links() }}
        @else
            <section class="maintenance-expenses">
                @forelse($expenseRecords->groupBy(fn($record) => $record->performed_at->format('Y-m')) as $month => $monthRecords)
                    <div class="maintenance-expense-row"><span>{{ \Carbon\Carbon::createFromFormat('Y-m',$month)->translatedFormat('F Y') }}</span><strong>{{ number_format((float)$monthRecords->sum('actual_cost'),2,',',' ') }} USD</strong><small>{{ $monthRecords->count() }} {{ __('maintenance.completed_by') }}</small></div>
                @empty<div class="empty-state">{{ __('maintenance.empty') }}</div>@endforelse
                <div class="maintenance-expense-total"><span>{{ __('maintenance.total') }}</span><strong>{{ number_format((float)$stats['actual_cost'],2,',',' ') }} USD</strong></div>
            </section>
        @endif
    </main>
</div>

@if(session('status'))<div class="app-toast app-toast-{{ session('status_type','success') }}" data-app-toast><span class="app-toast-icon"><i class="fa-solid fa-check"></i></span><span class="app-toast-message">{{ session('status') }}</span><button class="app-toast-close" data-app-toast-close><i class="fa-solid fa-xmark"></i></button><span class="app-toast-progress"></span></div>@endif

<div class="modal fade users-modal maintenance-form-modal" id="maintenanceModal" tabindex="-1" aria-labelledby="maintenanceModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered users-modal-dialog maintenance-modal-dialog">
        <div class="modal-content">
            <form class="maintenance-modal-form" method="POST" action="{{ route('maintenance.store') }}" enctype="multipart/form-data" data-maintenance-form data-loading-form data-old-vehicle-id="{{ old('vehicle_id') }}" @if($errors->getBag('default')->any()) data-maintenance-validation-errors @endif>
                @csrf
                <input type="hidden" name="_method" value="POST" data-field="method">
                <div class="modal-header">
                    <div class="form-modal-heading">
                        <span class="form-modal-heading-icon"><i class="fa-solid fa-clipboard-check"></i></span>
                        <h2 class="modal-title" id="maintenanceModalTitle" data-maintenance-title data-create-label="{{ __('maintenance.new') }}">{{ __('maintenance.new') }}</h2>
                    </div>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="{{ __('maintenance.cancel') }}"></button>
                </div>
                <div class="modal-body maintenance-modal-body">
                    <section class="maintenance-form-section">
                        <div class="maintenance-section-header"><i class="fa-solid fa-car-side"></i><h3>{{ __('maintenance.planning') }}</h3></div>
                        <div class="users-form-grid">
                            <div>
                                <label class="form-label">{{ __('maintenance.vehicle') }} *</label>
                                <div class="searchable-select @error('vehicle_id') is-invalid @enderror" data-searchable-select data-no-results="{{ __('maintenance.no_vehicle_match') }}">
                                    <select class="form-select searchable-select-native" name="vehicle_id" required data-field="vehicle_id" data-searchable-select-native><option value="">{{ __('maintenance.choose_vehicle') }}</option>@foreach($vehicles as $vehicle)<option value="{{ $vehicle->id }}" data-search="{{ $vehicle->fleet?->name }} {{ $vehicle->fleet?->code }}">{{ $vehicle->name }} · {{ $vehicle->registration_number }}</option>@endforeach</select>
                                    <button type="button" class="searchable-select-toggle" data-searchable-select-toggle aria-expanded="false"><span data-searchable-select-label>{{ __('maintenance.choose_vehicle') }}</span><i class="fa-solid fa-chevron-down"></i></button>
                                    <div class="searchable-select-panel" data-searchable-select-panel hidden><label class="searchable-select-search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" placeholder="{{ __('maintenance.search_vehicle') }}" data-searchable-select-search></label><div class="searchable-select-options" data-searchable-select-options></div><p class="searchable-select-empty" data-searchable-select-empty hidden>{{ __('maintenance.no_vehicle_match') }}</p></div>
                                </div>
                                @error('vehicle_id')<div class="invalid-feedback d-block" data-field-error="vehicle_id">{{ $message }}</div>@enderror
                            </div>
                            <div><label class="form-label">{{ __('maintenance.garage') }}</label><select class="form-select @error('garage_id') is-invalid @enderror" name="garage_id" data-field="garage_id" data-searchable-database data-search-placeholder="{{ __('ui.search_options') }}" data-no-results="{{ __('ui.no_option_match') }}" data-option-icon="fa-screwdriver-wrench"><option value="">-</option>@foreach($garages as $garage)<option value="{{ $garage->id }}" @selected((string) old('garage_id') === (string) $garage->id)>{{ $garage->name }}</option>@endforeach</select>@error('garage_id')<div class="invalid-feedback d-block" data-field-error="garage_id">{{ $message }}</div>@enderror</div>
                            <div class="users-form-full"><label class="form-label">{{ __('maintenance.name') }} *</label><input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required data-field="name">@error('name')<div class="invalid-feedback d-block" data-field-error="name">{{ $message }}</div>@enderror</div>
                            <div><label class="form-label">{{ __('maintenance.type') }}</label><select class="form-select @error('maintenance_type') is-invalid @enderror" name="maintenance_type" data-field="maintenance_type"><option value="preventive" @selected(old('maintenance_type', 'preventive') === 'preventive')>{{ __('maintenance.preventive') }}</option><option value="corrective" @selected(old('maintenance_type') === 'corrective')>{{ __('maintenance.corrective') }}</option></select>@error('maintenance_type')<div class="invalid-feedback d-block" data-field-error="maintenance_type">{{ $message }}</div>@enderror</div>
                            <div><label class="form-label">{{ __('maintenance.estimated_cost') }}</label><div class="maintenance-input-suffix"><input class="form-control @error('estimated_cost') is-invalid @enderror" type="number" min="0" step="0.01" name="estimated_cost" value="{{ old('estimated_cost') }}" data-field="estimated_cost"><span>USD</span></div>@error('estimated_cost')<div class="invalid-feedback d-block" data-field-error="estimated_cost">{{ $message }}</div>@enderror</div>
                            <div class="users-form-full"><label class="form-label">{{ __('maintenance.description') }}</label><textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3" data-field="description">{{ old('description') }}</textarea>@error('description')<div class="invalid-feedback d-block" data-field-error="description">{{ $message }}</div>@enderror</div>
                        </div>
                    </section>
                    <section class="maintenance-form-section">
                        <div class="maintenance-section-header"><i class="fa-solid fa-arrows-rotate"></i><h3>{{ __('maintenance.conditions') }}</h3></div>
                        <label class="maintenance-switch"><input type="checkbox" name="is_recurring" value="1" data-field="is_recurring" @checked(old('is_recurring'))><span aria-hidden="true"></span><strong>{{ __('maintenance.recurring') }}</strong></label>
                        @error('is_recurring')<div class="invalid-feedback d-block" data-field-error="is_recurring">{{ $message }}</div>@enderror
                        <div class="maintenance-conditions-list">
                            @foreach($conditionItems as $condition)
                                <div class="maintenance-condition">
                                    <strong><i class="fa-solid {{ $condition['label'] === 'date' ? 'fa-calendar-day' : ($condition['label'] === 'odometer' ? 'fa-gauge-high' : 'fa-clock') }}"></i>{{ __('maintenance.'.$condition['label']) }}</strong>
                                    <div class="maintenance-condition-grid">
                                        <label class="maintenance-condition-due">{{ __('maintenance.next_due') }}<div class="maintenance-input-suffix"><input class="form-control @error($condition['due']) is-invalid @enderror" type="{{ $condition['type'] }}" min="0" step="{{ $condition['type'] === 'number' ? '0.01' : '' }}" name="{{ $condition['due'] }}" value="{{ old($condition['due']) }}" data-field="{{ $condition['due'] }}">@if($condition['type'] === 'number')<span>{{ $condition['unit'] }}</span>@endif</div>@error($condition['due'])<span class="invalid-feedback d-block" data-field-error="{{ $condition['due'] }}">{{ $message }}</span>@enderror</label>
                                        <label>{{ __('maintenance.reminder') }}<div class="maintenance-input-suffix"><input class="form-control @error($condition['reminder']) is-invalid @enderror" type="number" min="0" name="{{ $condition['reminder'] }}" value="{{ old($condition['reminder'], 0) }}" data-field="{{ $condition['reminder'] }}"><span>{{ $condition['unit'] }}</span></div>@error($condition['reminder'])<span class="invalid-feedback d-block" data-field-error="{{ $condition['reminder'] }}">{{ $message }}</span>@enderror</label>
                                        <label>{{ __('maintenance.interval') }}<div class="maintenance-input-suffix"><input class="form-control @error($condition['interval']) is-invalid @enderror" type="number" min="1" name="{{ $condition['interval'] }}" value="{{ old($condition['interval']) }}" data-field="{{ $condition['interval'] }}"><span>{{ $condition['unit'] }}</span></div>@error($condition['interval'])<span class="invalid-feedback d-block" data-field-error="{{ $condition['interval'] }}">{{ $message }}</span>@enderror</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                    <section class="maintenance-form-section">
                        <div class="maintenance-section-header"><i class="fa-solid fa-paperclip"></i><h3>{{ __('maintenance.documents') }}</h3></div>
                        <input class="form-control @error('documents') is-invalid @enderror @error('documents.*') is-invalid @enderror" type="file" name="documents[]" multiple>
                        @error('documents')<div class="invalid-feedback d-block" data-field-error="documents">{{ $message }}</div>@enderror
                        @error('documents.*')<div class="invalid-feedback d-block" data-field-error="documents">{{ $message }}</div>@enderror
                        <small class="maintenance-help">{{ __('maintenance.documents_help') }}</small>
                    </section>
                </div>
                <div class="modal-footer"><button type="button" class="btn users-cancel-button" data-bs-dismiss="modal">{{ __('maintenance.cancel') }}</button><button class="btn btn-primary" data-maintenance-submit data-create-label="{{ __('maintenance.create') }}" data-save-label="{{ __('maintenance.save') }}">{{ __('maintenance.create') }}</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade users-modal" id="completeMaintenanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered users-modal-dialog"><div class="modal-content">
        <form method="POST" action="{{ old('maintenance_plan_id') ? route('maintenance.complete', old('maintenance_plan_id')) : '' }}" data-complete-form enctype="multipart/form-data" data-loading-form @if($errors->getBag('completion')->any()) data-completion-validation-errors @endif>
            @csrf @method('PATCH')
            <input type="hidden" name="maintenance_plan_id" value="{{ old('maintenance_plan_id') }}" data-complete-plan-id>
            <input type="hidden" name="maintenance_plan_name" value="{{ old('maintenance_plan_name') }}" data-complete-plan-name>
            <div class="modal-header"><div class="form-modal-heading"><span class="form-modal-heading-icon"><i class="fa-solid fa-circle-check"></i></span><h2 class="modal-title" data-complete-title>{{ old('maintenance_plan_name', __('maintenance.complete')) }}</h2></div><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><div class="users-form-grid">
                <div><label class="form-label">{{ __('maintenance.performed_at') }} *</label><input class="form-control @error('performed_at', 'completion') is-invalid @enderror" type="date" name="performed_at" value="{{ old('performed_at', now()->toDateString()) }}" required>@error('performed_at', 'completion')<div class="invalid-feedback d-block" data-field-error="performed_at">{{ $message }}</div>@enderror</div>
                <div><label class="form-label">{{ __('maintenance.garage') }}</label><select class="form-select @error('garage_id', 'completion') is-invalid @enderror" name="garage_id" data-searchable-database data-search-placeholder="{{ __('ui.search_options') }}" data-no-results="{{ __('ui.no_option_match') }}" data-option-icon="fa-screwdriver-wrench"><option value="">-</option>@foreach($garages as $garage)<option value="{{ $garage->id }}" @selected((string) old('garage_id') === (string) $garage->id)>{{ $garage->name }}</option>@endforeach</select>@error('garage_id', 'completion')<div class="invalid-feedback d-block" data-field-error="garage_id">{{ $message }}</div>@enderror</div>
                <div><label class="form-label">{{ __('maintenance.actual_odometer') }}</label><input class="form-control @error('odometer_km', 'completion') is-invalid @enderror" type="number" min="0" step="0.01" name="odometer_km" value="{{ old('odometer_km') }}">@error('odometer_km', 'completion')<div class="invalid-feedback d-block" data-field-error="odometer_km">{{ $message }}</div>@enderror</div>
                <div><label class="form-label">{{ __('maintenance.actual_engine_hours') }}</label><input class="form-control @error('engine_hours', 'completion') is-invalid @enderror" type="number" min="0" step="0.01" name="engine_hours" value="{{ old('engine_hours') }}">@error('engine_hours', 'completion')<div class="invalid-feedback d-block" data-field-error="engine_hours">{{ $message }}</div>@enderror</div>
                <div><label class="form-label">{{ __('maintenance.completion_cost') }}</label><input class="form-control @error('actual_cost', 'completion') is-invalid @enderror" type="number" min="0" step="0.01" name="actual_cost" value="{{ old('actual_cost') }}">@error('actual_cost', 'completion')<div class="invalid-feedback d-block" data-field-error="actual_cost">{{ $message }}</div>@enderror</div>
                <div><label class="form-label">{{ __('maintenance.documents') }}</label><input class="form-control @error('documents', 'completion') is-invalid @enderror @error('documents.*', 'completion') is-invalid @enderror" type="file" name="documents[]" multiple>@error('documents', 'completion')<div class="invalid-feedback d-block" data-field-error="documents">{{ $message }}</div>@enderror @error('documents.*', 'completion')<div class="invalid-feedback d-block" data-field-error="documents">{{ $message }}</div>@enderror</div>
                <div class="users-form-full"><label class="form-label">{{ __('maintenance.notes') }}</label><textarea class="form-control @error('notes', 'completion') is-invalid @enderror" name="notes" rows="4">{{ old('notes') }}</textarea>@error('notes', 'completion')<div class="invalid-feedback d-block" data-field-error="notes">{{ $message }}</div>@enderror</div>
            </div></div>
            <div class="modal-footer"><button type="button" class="btn users-cancel-button" data-bs-dismiss="modal">{{ __('maintenance.cancel') }}</button><button class="btn btn-primary">{{ __('maintenance.complete') }}</button></div>
        </form>
    </div></div>
</div>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script><script src="{{ asset('js/dashboard-sidebar.js') }}"></script><script src="{{ asset('js/confirm-delete.js') }}"></script><script src="{{ asset('js/form-loading.js') }}"></script><script src="{{ asset('js/searchable-select.js') }}?v=20260719-vehicle-search"></script>@include('partials.realtime-alerts')<script src="{{ asset('js/maintenance.js') }}?v=20260719-vehicle-search"></script>
</body></html>
