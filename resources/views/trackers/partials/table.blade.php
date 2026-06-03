@php
    $sortLink = function (string $column) use ($sort, $direction, $search): string {
        $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';

        return route('trackers.index', array_filter([
            'search' => $search,
            'sort' => $column,
            'direction' => $nextDirection,
        ], fn ($value) => $value !== null && $value !== ''));
    };

    $sortIcon = fn (string $column): string => 'fa-solid fa-sort';
@endphp

<div class="users-toolbar">
    <form method="GET" action="{{ route('trackers.index') }}" class="users-search" data-datatable-search-form>
        @if ($sort !== null)
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">
        @endif
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('trackers.search') }}" data-datatable-search>
    </form>

    <span class="users-count">
        {{ $devices->count() }} / {{ $devices->total() }} {{ __('trackers.rows') }}
    </span>
</div>

<section class="users-table-card">
    <div class="table-responsive">
        <table class="table align-middle users-table">
            <thead>
                <tr>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'id' ? 'active' : '' }}" href="{{ $sortLink('id') }}" data-datatable-sort>
                            <span>#</span>
                            <i class="{{ $sortIcon('id') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'name' ? 'active' : '' }}" href="{{ $sortLink('name') }}" data-datatable-sort>
                            <span>{{ __('trackers.tracker') }}</span>
                            <i class="{{ $sortIcon('name') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'imei' ? 'active' : '' }}" href="{{ $sortLink('imei') }}" data-datatable-sort>
                            <span>{{ __('trackers.imei') }}</span>
                            <i class="{{ $sortIcon('imei') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'vehicle' ? 'active' : '' }}" href="{{ $sortLink('vehicle') }}" data-datatable-sort>
                            <span>{{ __('trackers.vehicle') }}</span>
                            <i class="{{ $sortIcon('vehicle') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'fleet' ? 'active' : '' }}" href="{{ $sortLink('fleet') }}" data-datatable-sort>
                            <span>{{ __('trackers.fleet') }}</span>
                            <i class="{{ $sortIcon('fleet') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'status' ? 'active' : '' }}" href="{{ $sortLink('status') }}" data-datatable-sort>
                            <span>{{ __('trackers.status') }}</span>
                            <i class="{{ $sortIcon('status') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'last_seen_at' ? 'active' : '' }}" href="{{ $sortLink('last_seen_at') }}" data-datatable-sort>
                            <span>{{ __('trackers.last_seen') }}</span>
                            <i class="{{ $sortIcon('last_seen_at') }}"></i>
                        </a>
                    </th>
                    @if ($canManageDevices)
                        <th class="text-end">{{ __('trackers.actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($devices as $device)
                    <tr>
                        <td>{{ $devices->firstItem() + $loop->index }}</td>
                        <td>
                            <strong>{{ $device->name ?: __('dashboard.device_fallback', ['imei' => $device->imei]) }}</strong>
                            <span class="technical-code">
                                {{ $device->brand ? __('trackers.brand_' . $device->brand) : '-' }}
                                @if ($device->model)
                                    · {{ $device->model }}
                                @endif
                            </span>
                        </td>
                        <td><span class="technical-code">{{ $device->imei }}</span></td>
                        <td>
                            @if ($device->vehicle)
                                <strong>{{ $device->vehicle->name }}</strong>
                                <span class="technical-code">{{ $device->vehicle->registration_number }}</span>
                            @else
                                <span class="technical-code">{{ __('trackers.no_vehicle') }}</span>
                            @endif
                        </td>
                        <td>
                            @if ($device->fleet)
                                <strong>{{ $device->fleet->name }}</strong>
                                <span class="technical-code">{{ $device->fleet->code }}</span>
                            @else
                                <span class="technical-code">{{ __('trackers.no_fleet') }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="status-pill status-{{ $device->status }}">
                                {{ __('trackers.status_' . $device->status) }}
                            </span>
                        </td>
                        <td>
                            @if ($device->last_seen_at)
                                {{ $device->last_seen_at->diffForHumans() }}
                            @else
                                <span class="signal-missing">{{ __('trackers.no_signal') }}</span>
                            @endif
                        </td>
                        @if ($canManageDevices)
                            <td class="text-end">
                                <div class="users-actions">
                                    <button
                                        type="button"
                                        class="icon-action icon-action-history"
                                        data-tracker-details
                                        data-details-url="{{ route('trackers.details', $device) }}"
                                        aria-label="{{ __('trackers.details') }}"
                                    >
                                        <i class="fa-regular fa-clock"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="icon-action icon-action-history"
                                        data-trips-open
                                        data-trips-url="{{ route('trackers.trips', $device) }}"
                                        data-trips-name="{{ $device->vehicle?->name ?: ($device->name ?: $device->imei) }}"
                                        aria-label="{{ __('trackers.trips') }}"
                                    >
                                        <i class="fa-solid fa-route"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="icon-action icon-action-edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#trackerModal"
                                        data-tracker-edit
                                        data-action="{{ route('trackers.update', $device) }}"
                                        data-vehicle-id="{{ $device->vehicle_id }}"
                                        data-name="{{ $device->name }}"
                                        data-imei="{{ $device->imei }}"
                                        data-brand="{{ $device->brand }}"
                                        data-model="{{ $device->model }}"
                                        data-sim-number="{{ $device->sim_number }}"
                                        data-operator-name="{{ $device->operator_name }}"
                                        data-protocol="{{ $device->protocol }}"
                                        aria-label="{{ __('trackers.edit') }}"
                                    >
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </button>
                                    <form
                                        method="POST"
                                        action="{{ route('trackers.destroy', $device) }}"
                                        data-confirm-delete
                                        data-confirm-title="{{ __('trackers.delete_confirm_title') }}"
                                        data-confirm-message="{{ __('trackers.delete_confirm_message', ['name' => $device->name ?: $device->imei]) }}"
                                        data-confirm-cancel="{{ __('trackers.cancel') }}"
                                        data-confirm-submit="{{ __('trackers.delete_confirm_submit') }}"
                                        data-confirm-processing="{{ __('trackers.processing') }}"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-action icon-action-delete" aria-label="{{ __('trackers.delete') }}">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canManageDevices ? 8 : 7 }}" class="empty-state">{{ __('trackers.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<div class="datatable-footer" data-datatable-pagination>
    <p>
        {{ __('trackers.pagination_summary', [
            'first' => $devices->firstItem() ?? 0,
            'last' => $devices->lastItem() ?? 0,
            'total' => $devices->total(),
        ]) }}
    </p>

    @if ($devices->hasPages())
        <nav class="datatable-pagination" aria-label="{{ __('trackers.pagination') }}">
            @if ($devices->onFirstPage())
                <span class="disabled">{{ __('trackers.previous') }}</span>
            @else
                <a href="{{ $devices->previousPageUrl() }}" rel="prev">{{ __('trackers.previous') }}</a>
            @endif

            @foreach ($devices->getUrlRange(1, $devices->lastPage()) as $page => $url)
                @if ($page === $devices->currentPage())
                    <span class="active">{{ $page }}</span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach

            @if ($devices->hasMorePages())
                <a href="{{ $devices->nextPageUrl() }}" rel="next">{{ __('trackers.next') }}</a>
            @else
                <span class="disabled">{{ __('trackers.next') }}</span>
            @endif
        </nav>
    @endif
</div>
