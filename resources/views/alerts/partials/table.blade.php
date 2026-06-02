@php
    $sortLink = function (string $column) use ($sort, $direction, $search): string {
        $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';

        return route('alerts.index', array_filter([
            'search' => $search,
            'sort' => $column,
            'direction' => $nextDirection,
        ], fn ($value) => $value !== null && $value !== ''));
    };

    $sortIcon = fn (string $column): string => 'fa-solid fa-sort';
@endphp

<div class="users-toolbar">
    <form method="GET" action="{{ route('alerts.index') }}" class="users-search" data-datatable-search-form>
        @if ($sort !== null)
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">
        @endif
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('alerts.search') }}" data-datatable-search>
    </form>

    <span class="users-count">
        {{ __('alerts.rows_count', ['shown' => $alerts->count(), 'total' => $alerts->total()]) }}
    </span>
</div>

<section class="users-table-card">
    <div class="table-responsive">
        <table class="table align-middle users-table alerts-table">
            <thead>
                <tr>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'id' ? 'active' : '' }}" href="{{ $sortLink('id') }}" data-datatable-sort>
                            <span>{{ __('alerts.number') }}</span>
                            <i class="{{ $sortIcon('id') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'type' ? 'active' : '' }}" href="{{ $sortLink('type') }}" data-datatable-sort>
                            <span>{{ __('alerts.alert') }}</span>
                            <i class="{{ $sortIcon('type') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'severity' ? 'active' : '' }}" href="{{ $sortLink('severity') }}" data-datatable-sort>
                            <span>{{ __('alerts.severity') }}</span>
                            <i class="{{ $sortIcon('severity') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'vehicle' ? 'active' : '' }}" href="{{ $sortLink('vehicle') }}" data-datatable-sort>
                            <span>{{ __('alerts.vehicle') }}</span>
                            <i class="{{ $sortIcon('vehicle') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'fleet' ? 'active' : '' }}" href="{{ $sortLink('fleet') }}" data-datatable-sort>
                            <span>{{ __('alerts.fleet') }}</span>
                            <i class="{{ $sortIcon('fleet') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'status' ? 'active' : '' }}" href="{{ $sortLink('status') }}" data-datatable-sort>
                            <span>{{ __('alerts.status') }}</span>
                            <i class="{{ $sortIcon('status') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'occurred_at' ? 'active' : '' }}" href="{{ $sortLink('occurred_at') }}" data-datatable-sort>
                            <span>{{ __('alerts.date') }}</span>
                            <i class="{{ $sortIcon('occurred_at') }}"></i>
                        </a>
                    </th>
                    <th class="text-end">{{ __('alerts.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($alerts as $alert)
                    <tr data-alert-row="{{ $alert->id }}">
                        <td>{{ $alerts->firstItem() + $loop->index }}</td>
                        <td>
                            <strong>{{ $alert->localizedTitle() }}</strong>
                            <span class="technical-code">{{ $alert->localizedMessage() }}</span>
                        </td>
                        <td>
                            <span class="alert-badge alert-severity-{{ $alert->severity }}">
                                {{ __('alerts.severity_' . $alert->severity) }}
                            </span>
                        </td>
                        <td>
                            <strong>{{ $alert->vehicle?->name ?: __('alerts.unknown_vehicle') }}</strong>
                            <span class="technical-code">{{ $alert->vehicle?->registration_number ?: ($alert->device?->imei ?: '-') }}</span>
                        </td>
                        <td>{{ $alert->fleet?->name ?: __('alerts.unknown_fleet') }}</td>
                        <td>
                            <span class="status-pill status-{{ $alert->status }}">
                                {{ __('alerts.status_' . $alert->status) }}
                            </span>
                        </td>
                        <td>{{ $alert->occurred_at?->format('Y-m-d H:i:s') }}</td>
                        <td class="text-end">
                            @if ($alert->status === 'new')
                                <form method="POST" action="{{ route('alerts.acknowledge', $alert) }}" data-loading-form data-loading-text="{{ __('alerts.processing') }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="icon-action icon-action-history" aria-label="{{ __('alerts.acknowledge') }}" data-loading-button>
                                        <i class="fa-regular fa-circle-check"></i>
                                    </button>
                                </form>
                            @else
                                <span class="technical-code">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-state">
                            <strong>{{ __('alerts.empty') }}</strong>
                            <span>{{ __('alerts.empty_text') }}</span>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<div class="datatable-footer" data-datatable-pagination>
    <p>
        {{ __('alerts.showing', [
            'from' => $alerts->firstItem() ?? 0,
            'to' => $alerts->lastItem() ?? 0,
            'total' => $alerts->total(),
        ]) }}
    </p>

    @if ($alerts->hasPages())
        <nav class="datatable-pagination" aria-label="{{ __('alerts.title') }}">
            @if ($alerts->onFirstPage())
                <span class="disabled">{{ __('alerts.previous') }}</span>
            @else
                <a href="{{ $alerts->previousPageUrl() }}" rel="prev">{{ __('alerts.previous') }}</a>
            @endif

            @foreach ($alerts->getUrlRange(1, $alerts->lastPage()) as $page => $url)
                @if ($page === $alerts->currentPage())
                    <span class="active">{{ $page }}</span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach

            @if ($alerts->hasMorePages())
                <a href="{{ $alerts->nextPageUrl() }}" rel="next">{{ __('alerts.next') }}</a>
            @else
                <span class="disabled">{{ __('alerts.next') }}</span>
            @endif
        </nav>
    @endif
</div>
