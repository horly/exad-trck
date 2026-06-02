@php
    $sortLink = function (string $column) use ($sort, $direction, $search): string {
        $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';

        return route('fleets.index', array_filter([
            'search' => $search,
            'sort' => $column,
            'direction' => $nextDirection,
        ], fn ($value) => $value !== null && $value !== ''));
    };

    $sortIcon = fn (string $column): string => 'fa-solid fa-sort';
@endphp

<div class="users-toolbar">
    <form method="GET" action="{{ route('fleets.index') }}" class="users-search" data-datatable-search-form>
        @if ($sort !== null)
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">
        @endif
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('fleets.search') }}" data-datatable-search>
    </form>

    <span class="users-count">
        {{ $fleets->count() }} / {{ $fleets->total() }} {{ __('fleets.rows') }}
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
                            <span>{{ __('fleets.fleet') }}</span>
                            <i class="{{ $sortIcon('name') }}"></i>
                        </a>
                    </th>
                    <th>{{ __('fleets.managers') }}</th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'vehicles' ? 'active' : '' }}" href="{{ $sortLink('vehicles') }}" data-datatable-sort>
                            <span>{{ __('fleets.vehicles') }}</span>
                            <i class="{{ $sortIcon('vehicles') }}"></i>
                        </a>
                    </th>
                    <th>{{ __('fleets.premium') }}</th>
                    <th>{{ __('fleets.basic') }}</th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'status' ? 'active' : '' }}" href="{{ $sortLink('status') }}" data-datatable-sort>
                            <span>{{ __('fleets.status') }}</span>
                            <i class="{{ $sortIcon('status') }}"></i>
                        </a>
                    </th>
                    @if ($canManageFleets)
                        <th class="text-end">{{ __('fleets.actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($fleets as $fleet)
                    <tr>
                        <td>{{ $fleets->firstItem() + $loop->index }}</td>
                        <td>
                            <strong>{{ $fleet->name }}</strong>
                            <span class="technical-code">{{ $fleet->code }} · {{ $fleet->description ?: __('fleets.no_description') }}</span>
                        </td>
                        <td>
                            @forelse ($fleet->users->where('pivot.permission', 'manager')->take(2) as $manager)
                                <span class="technical-code d-block">{{ $manager->name }}</span>
                            @empty
                                <span class="technical-code">{{ __('fleets.no_manager') }}</span>
                            @endforelse
                        </td>
                        <td>{{ $fleet->vehicles_count }}</td>
                        <td>{{ $fleet->premium_vehicles_count }}</td>
                        <td>{{ $fleet->basic_vehicles_count }}</td>
                        <td>
                            <span class="status-pill status-{{ $fleet->status }}">
                                {{ $fleet->status === 'active' ? __('fleets.active') : __('fleets.inactive') }}
                            </span>
                        </td>
                        @if ($canManageFleets)
                            <td class="text-end">
                                <div class="users-actions">
                                    <button
                                        type="button"
                                        class="icon-action icon-action-edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#fleetModal"
                                        data-fleet-edit
                                        data-action="{{ route('fleets.update', $fleet) }}"
                                        data-name="{{ $fleet->name }}"
                                        data-code="{{ $fleet->code }}"
                                        data-description="{{ $fleet->description }}"
                                        data-status="{{ $fleet->status }}"
                                        data-admin="{{ $fleet->users->where('pivot.permission', 'manager')->first()?->id }}"
                                        aria-label="{{ __('fleets.edit') }}"
                                    >
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </button>
                                    <form
                                        method="POST"
                                        action="{{ route('fleets.destroy', $fleet) }}"
                                        data-confirm-delete
                                        data-confirm-title="{{ __('fleets.delete_confirm_title') }}"
                                        data-confirm-message="{{ __('fleets.delete_confirm_message', ['name' => $fleet->name]) }}"
                                        data-confirm-cancel="{{ __('fleets.cancel') }}"
                                        data-confirm-submit="{{ __('fleets.delete_confirm_submit') }}"
                                        data-confirm-processing="{{ __('fleets.processing') }}"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-action icon-action-delete" aria-label="{{ __('fleets.delete') }}">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canManageFleets ? 8 : 7 }}" class="empty-state">
                            {{ __('fleets.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<div class="datatable-footer" data-datatable-pagination>
    <p>
        {{ __('fleets.pagination_summary', [
            'first' => $fleets->firstItem() ?? 0,
            'last' => $fleets->lastItem() ?? 0,
            'total' => $fleets->total(),
        ]) }}
    </p>

    @if ($fleets->hasPages())
        <nav class="datatable-pagination" aria-label="{{ __('fleets.pagination') }}">
            @if ($fleets->onFirstPage())
                <span class="disabled">{{ __('fleets.previous') }}</span>
            @else
                <a href="{{ $fleets->previousPageUrl() }}" rel="prev">{{ __('fleets.previous') }}</a>
            @endif

            @foreach ($fleets->getUrlRange(1, $fleets->lastPage()) as $page => $url)
                @if ($page === $fleets->currentPage())
                    <span class="active">{{ $page }}</span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach

            @if ($fleets->hasMorePages())
                <a href="{{ $fleets->nextPageUrl() }}" rel="next">{{ __('fleets.next') }}</a>
            @else
                <span class="disabled">{{ __('fleets.next') }}</span>
            @endif
        </nav>
    @endif
</div>
