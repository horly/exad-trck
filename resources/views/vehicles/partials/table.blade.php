@php
    $sortLink = function (string $column) use ($sort, $direction, $search): string {
        $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';

        return route('vehicles.index', array_filter([
            'search' => $search,
            'sort' => $column,
            'direction' => $nextDirection,
        ], fn ($value) => $value !== null && $value !== ''));
    };

    $sortIcon = fn (string $column): string => 'fa-solid fa-sort';
@endphp

<div class="users-toolbar">
    <form method="GET" action="{{ route('vehicles.index') }}" class="users-search" data-datatable-search-form>
        @if ($sort !== null)
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">
        @endif
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('vehicles.search') }}" data-datatable-search>
    </form>

    <span class="users-count">
        {{ $vehicles->count() }} / {{ $vehicles->total() }} {{ __('vehicles.rows') }}
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
                            <span>{{ __('vehicles.vehicle') }}</span>
                            <i class="{{ $sortIcon('name') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'registration_number' ? 'active' : '' }}" href="{{ $sortLink('registration_number') }}" data-datatable-sort>
                            <span>{{ __('vehicles.registration_number') }}</span>
                            <i class="{{ $sortIcon('registration_number') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'fleet' ? 'active' : '' }}" href="{{ $sortLink('fleet') }}" data-datatable-sort>
                            <span>{{ __('vehicles.fleet') }}</span>
                            <i class="{{ $sortIcon('fleet') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'vehicle_type' ? 'active' : '' }}" href="{{ $sortLink('vehicle_type') }}" data-datatable-sort>
                            <span>{{ __('vehicles.type') }}</span>
                            <i class="{{ $sortIcon('vehicle_type') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'subscription_plan' ? 'active' : '' }}" href="{{ $sortLink('subscription_plan') }}" data-datatable-sort>
                            <span>{{ __('vehicles.subscription_plan') }}</span>
                            <i class="{{ $sortIcon('subscription_plan') }}"></i>
                        </a>
                    </th>
                    <th>{{ __('vehicles.device') }}</th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'status' ? 'active' : '' }}" href="{{ $sortLink('status') }}" data-datatable-sort>
                            <span>{{ __('vehicles.status') }}</span>
                            <i class="{{ $sortIcon('status') }}"></i>
                        </a>
                    </th>
                    @if ($canManageVehicles)
                        <th class="text-end">{{ __('vehicles.actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($vehicles as $vehicle)
                    <tr>
                        <td>{{ $vehicles->firstItem() + $loop->index }}</td>
                        <td>
                            <strong>{{ $vehicle->name }}</strong>
                            <span class="technical-code">{{ trim(($vehicle->brand ?: '') . ' ' . ($vehicle->model ?: '')) ?: __('vehicles.no_model') }}</span>
                        </td>
                        <td>{{ $vehicle->registration_number }}</td>
                        <td>
                            <strong>{{ $vehicle->fleet?->name }}</strong>
                            <span class="technical-code">{{ $vehicle->fleet?->code }}</span>
                        </td>
                        <td>{{ __('vehicles.type_' . $vehicle->vehicle_type) }}</td>
                        <td>
                            <span class="role-badge role-{{ $vehicle->subscription_plan }}">
                                {{ __('vehicles.plan_' . $vehicle->subscription_plan) }}
                            </span>
                        </td>
                        <td>
                            @if ($vehicle->device)
                                <span class="technical-code">{{ $vehicle->device->imei }}</span>
                            @else
                                <span class="technical-code">{{ __('vehicles.no_device') }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="status-pill status-{{ $vehicle->status }}">
                                {{ __('vehicles.status_' . $vehicle->status) }}
                            </span>
                        </td>
                        @if ($canManageVehicles)
                            <td class="text-end">
                                <div class="users-actions">
                                    <button
                                        type="button"
                                        class="icon-action icon-action-edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#vehicleModal"
                                        data-vehicle-edit
                                        data-action="{{ route('vehicles.update', $vehicle) }}"
                                        data-fleet-id="{{ $vehicle->fleet_id }}"
                                        data-name="{{ $vehicle->name }}"
                                        data-registration-number="{{ $vehicle->registration_number }}"
                                        data-brand="{{ $vehicle->brand }}"
                                        data-model="{{ $vehicle->model }}"
                                        data-color="{{ $vehicle->color }}"
                                        data-year="{{ $vehicle->year }}"
                                        data-vehicle-type="{{ $vehicle->vehicle_type }}"
                                        data-subscription-plan="{{ $vehicle->subscription_plan }}"
                                        data-status="{{ $vehicle->status }}"
                                        aria-label="{{ __('vehicles.edit') }}"
                                    >
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </button>
                                    <form
                                        method="POST"
                                        action="{{ route('vehicles.destroy', $vehicle) }}"
                                        data-confirm-delete
                                        data-confirm-title="{{ __('vehicles.delete_confirm_title') }}"
                                        data-confirm-message="{{ __('vehicles.delete_confirm_message', ['name' => $vehicle->name]) }}"
                                        data-confirm-cancel="{{ __('vehicles.cancel') }}"
                                        data-confirm-submit="{{ __('vehicles.delete_confirm_submit') }}"
                                        data-confirm-processing="{{ __('vehicles.processing') }}"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-action icon-action-delete" aria-label="{{ __('vehicles.delete') }}">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canManageVehicles ? 9 : 8 }}" class="empty-state">{{ __('vehicles.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<div class="datatable-footer" data-datatable-pagination>
    <p>
        {{ __('vehicles.pagination_summary', [
            'first' => $vehicles->firstItem() ?? 0,
            'last' => $vehicles->lastItem() ?? 0,
            'total' => $vehicles->total(),
        ]) }}
    </p>

    @if ($vehicles->hasPages())
        <nav class="datatable-pagination" aria-label="{{ __('vehicles.pagination') }}">
            @if ($vehicles->onFirstPage())
                <span class="disabled">{{ __('vehicles.previous') }}</span>
            @else
                <a href="{{ $vehicles->previousPageUrl() }}" rel="prev">{{ __('vehicles.previous') }}</a>
            @endif

            @foreach ($vehicles->getUrlRange(1, $vehicles->lastPage()) as $page => $url)
                @if ($page === $vehicles->currentPage())
                    <span class="active">{{ $page }}</span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach

            @if ($vehicles->hasMorePages())
                <a href="{{ $vehicles->nextPageUrl() }}" rel="next">{{ __('vehicles.next') }}</a>
            @else
                <span class="disabled">{{ __('vehicles.next') }}</span>
            @endif
        </nav>
    @endif
</div>
