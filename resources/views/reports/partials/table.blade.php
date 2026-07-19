@php
    $queryData = [
        'type' => $filters['type'],
        'period' => $filters['period'],
        'date_from' => $filters['date_from']->toDateString(),
        'date_to' => $filters['date_to']->toDateString(),
        'fleet_id' => $filters['fleet_id'],
        'vehicle_id' => $filters['vehicle_id'],
        'device_id' => $filters['device_id'],
        'search' => $filters['search'],
    ];

    $sortLink = function (string $column) use ($filters, $queryData): string {
        $nextDirection = $filters['sort'] === $column && $filters['direction'] === 'asc' ? 'desc' : 'asc';

        return route('reports.index', array_filter(array_merge($queryData, [
            'sort' => $column,
            'direction' => $nextDirection,
        ]), fn ($value) => $value !== null && $value !== ''));
    };

    $sortIcon = fn (): string => 'fa-solid fa-sort';
    $exportQuery = array_filter($queryData, fn ($value) => $value !== null && $value !== '');
@endphp

<section class="report-filter-card">
    <form method="GET" action="{{ route('reports.index') }}" class="report-filters" data-datatable-search-form>
        @if ($filters['sort'] !== '')
            <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
            <input type="hidden" name="direction" value="{{ $filters['direction'] }}">
        @endif

        <div class="report-filter-grid">
            <label>
                <span>{{ __('reports.report_type') }}</span>
                <select name="type" class="form-select">
                    @foreach ($typeOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>{{ __('reports.period') }}</span>
                <select name="period" class="form-select">
                    @foreach ($periodOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['period'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>{{ __('reports.from') }}</span>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from']->toDateString() }}">
            </label>
            <label>
                <span>{{ __('reports.to') }}</span>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to']->toDateString() }}">
            </label>
            <label>
                <span>{{ __('reports.fleet') }}</span>
                <select name="fleet_id" class="form-select" data-searchable-database data-search-placeholder="{{ __('ui.search_options') }}" data-no-results="{{ __('ui.no_option_match') }}" data-option-icon="fa-warehouse">
                    <option value="">{{ __('reports.all_fleets') }}</option>
                    @foreach ($fleets as $fleet)
                        <option value="{{ $fleet->id }}" @selected((int) $filters['fleet_id'] === $fleet->id)>{{ $fleet->name }}{{ $fleet->code ? ' · '.$fleet->code : '' }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>{{ __('reports.vehicle') }}</span>
                <select name="vehicle_id" class="form-select" data-searchable-database data-search-placeholder="{{ __('ui.search_options') }}" data-no-results="{{ __('ui.no_option_match') }}" data-option-icon="fa-car-side">
                    <option value="">{{ __('reports.all_vehicles') }}</option>
                    @foreach ($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}" @selected((int) $filters['vehicle_id'] === $vehicle->id)>{{ $vehicle->name }}{{ $vehicle->registration_number ? ' · '.$vehicle->registration_number : '' }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>{{ __('reports.tracker') }}</span>
                <select name="device_id" class="form-select" data-searchable-database data-search-placeholder="{{ __('ui.search_options') }}" data-no-results="{{ __('ui.no_option_match') }}" data-option-icon="fa-satellite-dish">
                    <option value="">{{ __('reports.all_trackers') }}</option>
                    @foreach ($devices as $device)
                        <option value="{{ $device->id }}" @selected((int) $filters['device_id'] === $device->id)>{{ $device->name ?: $device->imei }} · {{ $device->imei }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="report-toolbar">
            <label class="users-search report-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="{{ __('reports.search') }}" data-datatable-search>
            </label>
            <div class="report-actions">
                <button type="submit" class="btn users-primary-button report-filter-button">
                    <i class="fa-solid fa-filter"></i>
                    <span>{{ __('reports.apply_filters') }}</span>
                </button>
                <a class="btn users-secondary-button report-export-button" href="{{ route('reports.export', array_merge($exportQuery, ['format' => 'csv'])) }}">
                    <i class="fa-solid fa-file-csv"></i>
                    <span>{{ __('reports.export_csv') }}</span>
                </a>
                <a class="btn users-secondary-button report-export-button" href="{{ route('reports.export', array_merge($exportQuery, ['format' => 'print'])) }}">
                    <i class="fa-solid fa-print"></i>
                    <span>{{ __('reports.export_print') }}</span>
                </a>
            </div>
        </div>
    </form>
</section>

<div class="users-toolbar">
    <span class="users-count ms-auto">
        {{ __('reports.rows_count', ['shown' => $rows->count(), 'total' => $rows->total()]) }}
    </span>
</div>

<section class="users-table-card">
    <div class="table-responsive">
        <table class="table align-middle users-table report-table">
            <thead>
                <tr>
                    @switch($filters['type'])
                        @case('events')
                            <th><a class="datatable-sort-link {{ $filters['sort'] === 'id' ? 'active' : '' }}" href="{{ $sortLink('id') }}" data-datatable-sort><span>{{ __('reports.number') }}</span><i class="{{ $sortIcon() }}"></i></a></th>
                            <th><a class="datatable-sort-link {{ $filters['sort'] === 'type' ? 'active' : '' }}" href="{{ $sortLink('type') }}" data-datatable-sort><span>{{ __('reports.event') }}</span><i class="{{ $sortIcon() }}"></i></a></th>
                            <th><a class="datatable-sort-link {{ $filters['sort'] === 'vehicle' ? 'active' : '' }}" href="{{ $sortLink('vehicle') }}" data-datatable-sort><span>{{ __('reports.vehicle') }}</span><i class="{{ $sortIcon() }}"></i></a></th>
                            <th><a class="datatable-sort-link {{ $filters['sort'] === 'tracker' ? 'active' : '' }}" href="{{ $sortLink('tracker') }}" data-datatable-sort><span>{{ __('reports.tracker') }}</span><i class="{{ $sortIcon() }}"></i></a></th>
                            <th><a class="datatable-sort-link {{ $filters['sort'] === 'fleet' ? 'active' : '' }}" href="{{ $sortLink('fleet') }}" data-datatable-sort><span>{{ __('reports.fleet') }}</span><i class="{{ $sortIcon() }}"></i></a></th>
                            <th><a class="datatable-sort-link {{ $filters['sort'] === 'duration' ? 'active' : '' }}" href="{{ $sortLink('duration') }}" data-datatable-sort><span>{{ __('reports.duration') }}</span><i class="{{ $sortIcon() }}"></i></a></th>
                            <th><a class="datatable-sort-link {{ $filters['sort'] === 'started_at' ? 'active' : '' }}" href="{{ $sortLink('started_at') }}" data-datatable-sort><span>{{ __('reports.date') }}</span><i class="{{ $sortIcon() }}"></i></a></th>
                            @break
                        @case('alerts')
                            <th><a class="datatable-sort-link {{ $filters['sort'] === 'id' ? 'active' : '' }}" href="{{ $sortLink('id') }}" data-datatable-sort><span>{{ __('reports.number') }}</span><i class="{{ $sortIcon() }}"></i></a></th>
                            <th><a class="datatable-sort-link {{ $filters['sort'] === 'type' ? 'active' : '' }}" href="{{ $sortLink('type') }}" data-datatable-sort><span>{{ __('reports.alert') }}</span><i class="{{ $sortIcon() }}"></i></a></th>
                            <th><a class="datatable-sort-link {{ $filters['sort'] === 'severity' ? 'active' : '' }}" href="{{ $sortLink('severity') }}" data-datatable-sort><span>{{ __('reports.severity') }}</span><i class="{{ $sortIcon() }}"></i></a></th>
                            <th><a class="datatable-sort-link {{ $filters['sort'] === 'vehicle' ? 'active' : '' }}" href="{{ $sortLink('vehicle') }}" data-datatable-sort><span>{{ __('reports.vehicle') }}</span><i class="{{ $sortIcon() }}"></i></a></th>
                            <th><a class="datatable-sort-link {{ $filters['sort'] === 'fleet' ? 'active' : '' }}" href="{{ $sortLink('fleet') }}" data-datatable-sort><span>{{ __('reports.fleet') }}</span><i class="{{ $sortIcon() }}"></i></a></th>
                            <th><a class="datatable-sort-link {{ $filters['sort'] === 'status' ? 'active' : '' }}" href="{{ $sortLink('status') }}" data-datatable-sort><span>{{ __('reports.status') }}</span><i class="{{ $sortIcon() }}"></i></a></th>
                            <th><a class="datatable-sort-link {{ $filters['sort'] === 'occurred_at' ? 'active' : '' }}" href="{{ $sortLink('occurred_at') }}" data-datatable-sort><span>{{ __('reports.date') }}</span><i class="{{ $sortIcon() }}"></i></a></th>
                            @break
                        @case('fleet_summary')
                            <th><a class="datatable-sort-link {{ $filters['sort'] === 'id' ? 'active' : '' }}" href="{{ $sortLink('id') }}" data-datatable-sort><span>{{ __('reports.number') }}</span><i class="{{ $sortIcon() }}"></i></a></th>
                            <th><a class="datatable-sort-link {{ $filters['sort'] === 'fleet' ? 'active' : '' }}" href="{{ $sortLink('fleet') }}" data-datatable-sort><span>{{ __('reports.fleet') }}</span><i class="{{ $sortIcon() }}"></i></a></th>
                            <th><a class="datatable-sort-link {{ $filters['sort'] === 'vehicles' ? 'active' : '' }}" href="{{ $sortLink('vehicles') }}" data-datatable-sort><span>{{ __('reports.vehicles') }}</span><i class="{{ $sortIcon() }}"></i></a></th>
                            <th><a class="datatable-sort-link {{ $filters['sort'] === 'trackers' ? 'active' : '' }}" href="{{ $sortLink('trackers') }}" data-datatable-sort><span>{{ __('reports.trackers') }}</span><i class="{{ $sortIcon() }}"></i></a></th>
                            <th>{{ __('reports.online') }}</th>
                            <th>{{ __('reports.offline') }}</th>
                            <th><a class="datatable-sort-link {{ $filters['sort'] === 'status' ? 'active' : '' }}" href="{{ $sortLink('status') }}" data-datatable-sort><span>{{ __('reports.status') }}</span><i class="{{ $sortIcon() }}"></i></a></th>
                            @break
                        @default
                            <th><a class="datatable-sort-link {{ $filters['sort'] === 'id' ? 'active' : '' }}" href="{{ $sortLink('id') }}" data-datatable-sort><span>{{ __('reports.number') }}</span><i class="{{ $sortIcon() }}"></i></a></th>
                            <th><a class="datatable-sort-link {{ $filters['sort'] === 'tracker' ? 'active' : '' }}" href="{{ $sortLink('tracker') }}" data-datatable-sort><span>{{ __('reports.tracker') }}</span><i class="{{ $sortIcon() }}"></i></a></th>
                            <th><a class="datatable-sort-link {{ $filters['sort'] === 'vehicle' ? 'active' : '' }}" href="{{ $sortLink('vehicle') }}" data-datatable-sort><span>{{ __('reports.vehicle') }}</span><i class="{{ $sortIcon() }}"></i></a></th>
                            <th><a class="datatable-sort-link {{ $filters['sort'] === 'fleet' ? 'active' : '' }}" href="{{ $sortLink('fleet') }}" data-datatable-sort><span>{{ __('reports.fleet') }}</span><i class="{{ $sortIcon() }}"></i></a></th>
                            <th><a class="datatable-sort-link {{ $filters['sort'] === 'speed' ? 'active' : '' }}" href="{{ $sortLink('speed') }}" data-datatable-sort><span>{{ __('reports.speed') }}</span><i class="{{ $sortIcon() }}"></i></a></th>
                            <th>{{ __('reports.address') }}</th>
                            <th><a class="datatable-sort-link {{ $filters['sort'] === 'server_time' ? 'active' : '' }}" href="{{ $sortLink('server_time') }}" data-datatable-sort><span>{{ __('reports.date') }}</span><i class="{{ $sortIcon() }}"></i></a></th>
                    @endswitch
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    @switch($filters['type'])
                        @case('events')
                            <tr>
                                <td>{{ $rows->firstItem() + $loop->index }}</td>
                                <td><strong>{{ $row->localizedTitle() }}</strong><span class="technical-code">{{ $row->localizedMessage() }}</span></td>
                                <td><strong>{{ $row->vehicle?->name ?: '-' }}</strong><span class="technical-code">{{ $row->vehicle?->registration_number ?: '-' }}</span></td>
                                <td><strong>{{ $row->device?->name ?: ($row->device?->imei ?: '-') }}</strong><span class="technical-code">{{ $row->device?->model ?: '-' }}</span></td>
                                <td><strong>{{ $row->fleet?->name ?: '-' }}</strong><span class="technical-code">{{ $row->fleet?->code ?: '-' }}</span></td>
                                <td>{{ $row->durationLabel() ?: '-' }}</td>
                                <td>{{ $row->started_at?->format('Y-m-d H:i:s') ?: '-' }}</td>
                            </tr>
                            @break
                        @case('alerts')
                            <tr>
                                <td>{{ $rows->firstItem() + $loop->index }}</td>
                                <td><strong>{{ $row->localizedTitle() }}</strong><span class="technical-code">{{ $row->localizedMessage() }}</span></td>
                                <td><span class="report-pill report-pill-{{ $row->severity }}">{{ __('reports.severity_'.$row->severity) }}</span></td>
                                <td><strong>{{ $row->vehicle?->name ?: '-' }}</strong><span class="technical-code">{{ $row->vehicle?->registration_number ?: '-' }}</span></td>
                                <td><strong>{{ $row->fleet?->name ?: '-' }}</strong><span class="technical-code">{{ $row->fleet?->code ?: '-' }}</span></td>
                                <td><span class="report-pill report-pill-muted">{{ __('reports.status_'.$row->status) }}</span></td>
                                <td>{{ $row->occurred_at?->format('Y-m-d H:i:s') ?: '-' }}</td>
                            </tr>
                            @break
                        @case('fleet_summary')
                            <tr>
                                <td>{{ $rows->firstItem() + $loop->index }}</td>
                                <td><strong>{{ $row->name }}</strong><span class="technical-code">{{ $row->code ?: '-' }}</span></td>
                                <td>{{ $row->vehicles_count }}</td>
                                <td>{{ $row->devices_count }}</td>
                                <td><span class="report-pill report-pill-success">{{ $row->online_devices_count }}</span></td>
                                <td><span class="report-pill report-pill-danger">{{ $row->offline_devices_count }}</span></td>
                                <td><span class="report-pill report-pill-muted">{{ __('reports.fleet_status_'.$row->status) }}</span></td>
                            </tr>
                            @break
                        @default
                            <tr>
                                <td>{{ $rows->firstItem() + $loop->index }}</td>
                                <td><span class="report-identity-cell"><strong>{{ $row->device?->name ?: $row->imei }}</strong><span class="technical-code">{{ $row->device?->imei ?: $row->imei }}</span></span></td>
                                <td><span class="report-identity-cell"><strong>{{ $row->device?->vehicle?->name ?: '-' }}</strong><span class="technical-code">{{ $row->device?->vehicle?->registration_number ?: '-' }}</span></span></td>
                                <td><span class="report-identity-cell"><strong>{{ $row->device?->fleet?->name ?: '-' }}</strong><span class="technical-code">{{ $row->device?->fleet?->code ?: '-' }}</span></span></td>
                                <td>{{ __('reports.speed_value', ['value' => $row->speed ?? 0]) }}</td>
                                <td>{{ $row->address ?: __('reports.address_unavailable') }}</td>
                                <td>{{ $row->server_time?->format('Y-m-d H:i:s') ?: '-' }}</td>
                            </tr>
                    @endswitch
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="empty-state">
                            <strong>{{ __('reports.empty') }}</strong>
                            <span>{{ __('reports.empty_text') }}</span>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@include('partials.datatable-pagination', [
    'paginator' => $rows,
    'summary' => __('reports.showing', [
        'from' => $rows->firstItem() ?? 0,
        'to' => $rows->lastItem() ?? 0,
        'total' => $rows->total(),
    ]),
    'ariaLabel' => __('reports.pagination'),
    'previousLabel' => __('reports.previous'),
    'nextLabel' => __('reports.next'),
])


