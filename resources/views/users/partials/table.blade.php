@php
    $sortLink = function (string $column) use ($sort, $direction, $search): string {
        $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';

        return route('users.index', array_filter([
            'search' => $search,
            'sort' => $column,
            'direction' => $nextDirection,
        ], fn ($value) => $value !== null && $value !== ''));
    };

    $sortIcon = fn (string $column): string => 'fa-solid fa-sort';
@endphp

<div class="users-toolbar">
    <form method="GET" action="{{ route('users.index') }}" class="users-search" data-datatable-search-form>
        @if ($sort !== null)
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">
        @endif
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('users.search') }}" data-datatable-search>
    </form>

    <span class="users-count">
        {{ $users->count() }} / {{ $users->total() }} {{ __('users.rows') }}
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
                            <span>{{ __('users.name') }}</span>
                            <i class="{{ $sortIcon('name') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'email' ? 'active' : '' }}" href="{{ $sortLink('email') }}" data-datatable-sort>
                            <span>{{ __('users.email') }}</span>
                            <i class="{{ $sortIcon('email') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'role' ? 'active' : '' }}" href="{{ $sortLink('role') }}" data-datatable-sort>
                            <span>{{ __('users.role') }}</span>
                            <i class="{{ $sortIcon('role') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'phone' ? 'active' : '' }}" href="{{ $sortLink('phone') }}" data-datatable-sort>
                            <span>{{ __('users.phone') }}</span>
                            <i class="{{ $sortIcon('phone') }}"></i>
                        </a>
                    </th>
                    <th class="text-end">{{ __('users.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $users->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="users-identity">
                                <span class="users-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                <strong>{{ $user->name }}</strong>
                            </div>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="role-badge role-{{ $user->role->value }}">
                                {{ strtoupper($user->role->value) }}
                            </span>
                        </td>
                        <td>{{ $user->phone ?: '-' }}</td>
                        <td class="text-end">
                            <div class="users-actions">
                                <button
                                    class="icon-action icon-action-history"
                                    type="button"
                                    aria-label="{{ __('users.history') }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#loginHistoryModal"
                                    data-history-user
                                    data-history-user-id="{{ $user->id }}"
                                    data-history-user-name="{{ $user->name }}"
                                >
                                    <i class="fa-regular fa-clock"></i>
                                </button>

                                @unless ($user->isSuperadmin())
                                    <button
                                        class="icon-action icon-action-edit"
                                        type="button"
                                        aria-label="{{ __('users.edit') }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#createUserModal"
                                        data-user-edit
                                        data-update-url="{{ route('users.update', $user) }}"
                                        data-name="{{ $user->name }}"
                                        data-email="{{ $user->email }}"
                                        data-role="{{ $user->role->value }}"
                                        data-phone="{{ $user->phone }}"
                                        data-address="{{ $user->address }}"
                                    >
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </button>
                                    <form
                                        method="POST"
                                        action="{{ route('users.destroy', $user) }}"
                                        data-confirm-delete
                                        data-confirm-title="{{ __('users.delete_confirm_title') }}"
                                        data-confirm-message="{{ __('users.delete_confirm_message', ['name' => $user->name]) }}"
                                        data-confirm-cancel="{{ __('users.cancel') }}"
                                        data-confirm-submit="{{ __('users.delete_confirm_submit') }}"
                                        data-confirm-processing="{{ __('users.processing') }}"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button class="icon-action icon-action-delete" type="submit" aria-label="{{ __('users.delete') }}">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state">{{ __('users.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<div class="datatable-footer" data-datatable-pagination>
    <p>
        {{ __('users.pagination_summary', [
            'first' => $users->firstItem() ?? 0,
            'last' => $users->lastItem() ?? 0,
            'total' => $users->total(),
        ]) }}
    </p>

    @if ($users->hasPages())
        <nav class="datatable-pagination" aria-label="{{ __('users.pagination') }}">
            @if ($users->onFirstPage())
                <span class="disabled">{{ __('users.previous') }}</span>
            @else
                <a href="{{ $users->previousPageUrl() }}" rel="prev">{{ __('users.previous') }}</a>
            @endif

            @foreach ($users->getUrlRange(1, $users->lastPage()) as $page => $url)
                @if ($page === $users->currentPage())
                    <span class="active">{{ $page }}</span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach

            @if ($users->hasMorePages())
                <a href="{{ $users->nextPageUrl() }}" rel="next">{{ __('users.next') }}</a>
            @else
                <span class="disabled">{{ __('users.next') }}</span>
            @endif
        </nav>
    @endif
</div>
