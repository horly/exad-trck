@php
    $sortLink = function (string $column) use ($sort, $direction, $search): string {
        $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';

        return route('departments.index', array_filter([
            'search' => $search,
            'sort' => $column,
            'direction' => $nextDirection,
        ], fn ($value) => $value !== null && $value !== ''));
    };
@endphp

<div class="users-toolbar">
    <form method="GET" action="{{ route('departments.index') }}" class="users-search" data-datatable-search-form>
        @if ($sort !== null)
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">
        @endif
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('departments.search') }}" data-datatable-search>
    </form>

    <span class="users-count">{{ $departments->count() }} / {{ $departments->total() }} {{ __('departments.rows') }}</span>
</div>

<section class="users-table-card">
    <div class="table-responsive">
        <table class="table align-middle users-table">
            <thead>
                <tr>
                    @foreach ([
                        'id' => '#',
                        'name' => __('departments.name'),
                        'code' => __('departments.code'),
                        'fleet' => __('departments.fleet'),
                        'drivers' => __('departments.drivers'),
                        'status' => __('departments.status'),
                    ] as $column => $label)
                        <th>
                            <a class="datatable-sort-link {{ $sort === $column ? 'active' : '' }}" href="{{ $sortLink($column) }}" data-datatable-sort>
                                <span>{{ $label }}</span>
                                <i class="fa-solid fa-sort"></i>
                            </a>
                        </th>
                    @endforeach
                    @if ($canManageDepartments)
                        <th class="text-end">{{ __('departments.actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($departments as $department)
                    <tr>
                        <td>{{ $departments->firstItem() + $loop->index }}</td>
                        <td>
                            <strong>{{ $department->name }}</strong>
                            @if ($department->description)
                                <span class="technical-code table-secondary-line">{{ $department->description }}</span>
                            @endif
                        </td>
                        <td><span class="technical-code">{{ $department->code ?: '-' }}</span></td>
                        <td>
                            <strong>{{ $department->fleet?->name }}</strong>
                            <span class="technical-code">{{ $department->fleet?->code }}</span>
                        </td>
                        <td>{{ $department->drivers_count }}</td>
                        <td>
                            <span class="status-pill status-{{ $department->status }}">{{ __('departments.status_' . $department->status) }}</span>
                        </td>
                        @if ($canManageDepartments)
                        <td class="text-end">
                            <div class="users-actions">
                                @can('update-department', $department)
                                <button
                                    type="button"
                                    class="icon-action icon-action-edit"
                                    data-bs-toggle="modal"
                                    data-bs-target="#departmentModal"
                                    data-department-edit
                                    data-department-id="{{ $department->id }}"
                                    data-action="{{ route('departments.update', $department) }}"
                                    data-fleet-id="{{ $department->fleet_id }}"
                                    data-name="{{ $department->name }}"
                                    data-code="{{ $department->code }}"
                                    data-description="{{ $department->description }}"
                                    data-status="{{ $department->status }}"
                                    aria-label="{{ __('departments.edit') }}"
                                >
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                @endcan
                                @can('delete-department', $department)
                                @if ((int) $department->drivers_count === 0)
                                <form
                                    method="POST"
                                    action="{{ route('departments.destroy', $department) }}"
                                    data-confirm-delete
                                    data-confirm-title="{{ __('departments.delete_confirm_title') }}"
                                    data-confirm-message="{{ __('departments.delete_confirm_message', ['name' => $department->name]) }}"
                                    data-confirm-cancel="{{ __('departments.cancel') }}"
                                    data-confirm-submit="{{ __('departments.delete_confirm_submit') }}"
                                    data-confirm-processing="{{ __('departments.processing') }}"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="icon-action icon-action-delete" aria-label="{{ __('departments.delete') }}">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </form>
                                @else
                                    <button type="button" class="icon-action icon-action-delete" aria-label="{{ __('departments.delete') }}" title="{{ __('departments.delete_blocked') }}" disabled>
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                @endif
                                @endcan
                            </div>
                        </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $canManageDepartments ? 7 : 6 }}" class="empty-state">{{ __('departments.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@include('partials.datatable-pagination', [
    'paginator' => $departments,
    'summary' => __('departments.pagination_summary', [
        'first' => $departments->firstItem() ?? 0,
        'last' => $departments->lastItem() ?? 0,
        'total' => $departments->total(),
    ]),
    'ariaLabel' => __('departments.pagination'),
    'previousLabel' => __('departments.previous'),
    'nextLabel' => __('departments.next'),
])
