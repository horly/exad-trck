@php
    $canManageDrivers = $canManageDrivers ?? auth()->user()->isSuperadmin();
    $sortLink = function (string $column) use ($sort, $direction, $search): string {
        $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';

        return route('drivers.index', array_filter([
            'search' => $search,
            'sort' => $column,
            'direction' => $nextDirection,
        ], fn ($value) => $value !== null && $value !== ''));
    };
@endphp

<div class="users-toolbar">
    <form method="GET" action="{{ route('drivers.index') }}" class="users-search" data-datatable-search-form>
        @if ($sort !== null)
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">
        @endif
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('drivers.search') }}" data-datatable-search>
    </form>

    <span class="users-count">{{ $drivers->count() }} / {{ $drivers->total() }} {{ __('drivers.rows') }}</span>
</div>

<section class="users-table-card">
    <div class="table-responsive">
        <table class="table align-middle users-table drivers-table">
            <thead>
                <tr>
                    @foreach ([
                        'id' => '#',
                        'name' => __('drivers.driver'),
                        'employee_id' => __('drivers.employee_id'),
                        'fleet' => __('drivers.fleet'),
                        'department' => __('drivers.department'),
                    ] as $column => $label)
                        <th>
                            <a class="datatable-sort-link {{ $sort === $column ? 'active' : '' }}" href="{{ $sortLink($column) }}" data-datatable-sort>
                                <span>{{ $label }}</span>
                                <i class="fa-solid fa-sort"></i>
                            </a>
                        </th>
                    @endforeach
                    @if ($canManageDrivers)
                        <th>{{ __('drivers.badge') }}</th>
                    @endif
                    <th>{{ __('drivers.vehicles') }}</th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'status' ? 'active' : '' }}" href="{{ $sortLink('status') }}" data-datatable-sort>
                            <span>{{ __('drivers.status') }}</span>
                            <i class="fa-solid fa-sort"></i>
                        </a>
                    </th>
                    @if ($canManageDrivers)
                        <th class="text-end">{{ __('drivers.actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($drivers as $driver)
                    <tr>
                        <td>{{ $drivers->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="driver-table-identity">
                                <span class="driver-avatar">
                                    @if ($driver->photo_path)
                                        <img src="{{ asset('storage/' . $driver->photo_path) }}" alt="">
                                    @else
                                        {{ mb_strtoupper(mb_substr($driver->first_name, 0, 1)) }}
                                    @endif
                                </span>
                                <span>
                                    <strong>{{ $driver->full_name }}</strong>
                                    <small>{{ $driver->phone ?: $driver->email }}</small>
                                </span>
                            </div>
                        </td>
                        <td><span class="technical-code">{{ $driver->employee_id ?: '-' }}</span></td>
                        <td>
                            <strong>{{ $driver->fleet?->name }}</strong>
                            <span class="technical-code">{{ $driver->fleet?->code }}</span>
                        </td>
                        <td>{{ $driver->department?->name ?: '-' }}</td>
                        @if ($canManageDrivers)
                            <td>
                                @if ($driver->primaryIdentifier)
                                    <span class="driver-badge-code"><i class="fa-solid fa-key"></i>{{ $driver->primaryIdentifier->uid }}</span>
                                @else
                                    <span class="technical-code">-</span>
                                @endif
                            </td>
                        @endif
                        <td>
                            <div class="driver-vehicle-summary">
                                @forelse ($driver->vehicles->take(2) as $vehicle)
                                    <span>{{ $vehicle->name }} <small>{{ $vehicle->registration_number }}</small></span>
                                @empty
                                    <span class="technical-code">-</span>
                                @endforelse
                                @if ($driver->vehicles->count() > 2)
                                    <small>+{{ $driver->vehicles->count() - 2 }}</small>
                                @endif
                            </div>
                        </td>
                        <td><span class="status-pill status-{{ $driver->status }}">{{ __('drivers.status_' . $driver->status) }}</span></td>
                        @if ($canManageDrivers)
                            <td class="text-end">
                                <div class="users-actions">
                                <button
                                    type="button"
                                    class="icon-action icon-action-edit"
                                    data-bs-toggle="modal"
                                    data-bs-target="#driverModal"
                                    data-driver-edit
                                    data-driver-id="{{ $driver->id }}"
                                    data-action="{{ route('drivers.update', $driver) }}"
                                    data-driver="{{ json_encode([
                                        "fleet_id" => $driver->fleet_id,
                                        "department_id" => $driver->department_id,
                                        "first_name" => $driver->first_name,
                                        "middle_name" => $driver->middle_name,
                                        "last_name" => $driver->last_name,
                                        "employee_id" => $driver->employee_id,
                                        "social_security_number" => $driver->social_security_number,
                                        "identifier_type" => $driver->primaryIdentifier?->type ?? "rfid",
                                        "rfid_uid" => $driver->primaryIdentifier?->uid,
                                        "vehicle_ids" => $driver->vehicles->pluck("id")->values(),
                                        "phone" => $driver->phone,
                                        "email" => $driver->email,
                                        "address" => $driver->address,
                                        "location_latitude" => $driver->location_latitude,
                                        "location_longitude" => $driver->location_longitude,
                                        "location_radius_meters" => $driver->location_radius_meters,
                                        "license_number" => $driver->license_number,
                                        "license_type" => $driver->license_type,
                                        "license_issued_at" => $driver->license_issued_at?->format("Y-m-d"),
                                        "license_expires_at" => $driver->license_expires_at?->format("Y-m-d"),
                                        "tags" => implode(", ", $driver->tags ?? []),
                                        "status" => $driver->status,
                                        "photo_url" => $driver->photo_path ? asset("storage/" . $driver->photo_path) : null,
                                    ], JSON_UNESCAPED_UNICODE) }}"
                                    aria-label="{{ __('drivers.edit') }}"
                                >
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                <form
                                    method="POST"
                                    action="{{ route('drivers.destroy', $driver) }}"
                                    data-confirm-delete
                                    data-confirm-title="{{ __('drivers.delete_confirm_title') }}"
                                    data-confirm-message="{{ __('drivers.delete_confirm_message', ['name' => $driver->full_name]) }}"
                                    data-confirm-cancel="{{ __('drivers.cancel') }}"
                                    data-confirm-submit="{{ __('drivers.delete_confirm_submit') }}"
                                    data-confirm-processing="{{ __('drivers.processing') }}"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="icon-action icon-action-delete" aria-label="{{ __('drivers.delete') }}">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </form>
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $canManageDrivers ? 9 : 7 }}" class="empty-state">{{ __('drivers.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@include('partials.datatable-pagination', [
    'paginator' => $drivers,
    'summary' => __('drivers.pagination_summary', [
        'first' => $drivers->firstItem() ?? 0,
        'last' => $drivers->lastItem() ?? 0,
        'total' => $drivers->total(),
    ]),
    'ariaLabel' => __('drivers.pagination'),
    'previousLabel' => __('drivers.previous'),
    'nextLabel' => __('drivers.next'),
])
