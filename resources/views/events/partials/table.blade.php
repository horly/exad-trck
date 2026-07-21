@php
    $sortLink = function (string $column) use ($sort, $direction, $search, $deviceId): string {
        $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';

        return route('events.index', array_filter([
            'search' => $search,
            'device' => $deviceId,
            'sort' => $column,
            'direction' => $nextDirection,
        ], fn ($value) => $value !== null && $value !== ''));
    };

    $sortIcon = fn (string $column): string => 'fa-solid fa-sort';
@endphp

<div class="users-toolbar">
    <form method="GET" action="{{ route('events.index') }}" class="users-search" data-datatable-search-form>
        @if ($deviceId)
            <input type="hidden" name="device" value="{{ $deviceId }}">
        @endif
        @if ($sort !== null)
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">
        @endif
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('events.search') }}" data-datatable-search>
    </form>

    <span class="users-count">
        {{ __('events.rows_count', ['shown' => $events->count(), 'total' => $events->total()]) }}
    </span>
</div>

<section class="users-table-card">
    <div class="table-responsive">
        <table class="table align-middle users-table">
            <thead>
                <tr>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'id' ? 'active' : '' }}" href="{{ $sortLink('id') }}" data-datatable-sort>
                            <span>{{ __('events.number') }}</span>
                            <i class="{{ $sortIcon('id') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'type' ? 'active' : '' }}" href="{{ $sortLink('type') }}" data-datatable-sort>
                            <span>{{ __('events.event') }}</span>
                            <i class="{{ $sortIcon('type') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'vehicle' ? 'active' : '' }}" href="{{ $sortLink('vehicle') }}" data-datatable-sort>
                            <span>{{ __('events.vehicle') }}</span>
                            <i class="{{ $sortIcon('vehicle') }}"></i>
                        </a>
                    </th>
                    @if ($showTechnicalDetails)
                        <th>
                            <a class="datatable-sort-link {{ $sort === 'tracker' ? 'active' : '' }}" href="{{ $sortLink('tracker') }}" data-datatable-sort>
                                <span>{{ __('events.tracker') }}</span>
                                <i class="{{ $sortIcon('tracker') }}"></i>
                            </a>
                        </th>
                    @endif
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'fleet' ? 'active' : '' }}" href="{{ $sortLink('fleet') }}" data-datatable-sort>
                            <span>{{ __('events.fleet') }}</span>
                            <i class="{{ $sortIcon('fleet') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'duration' ? 'active' : '' }}" href="{{ $sortLink('duration') }}" data-datatable-sort>
                            <span>{{ __('events.duration') }}</span>
                            <i class="{{ $sortIcon('duration') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'started_at' ? 'active' : '' }}" href="{{ $sortLink('started_at') }}" data-datatable-sort>
                            <span>{{ __('events.date') }}</span>
                            <i class="{{ $sortIcon('started_at') }}"></i>
                        </a>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($events as $event)
                    <tr>
                        <td>{{ $events->firstItem() + $loop->index }}</td>
                        <td>
                            <strong>{{ $event->localizedTitle() }}</strong>
                            <span class="technical-code">{{ $event->localizedMessage() }}</span>
                        </td>
                        <td>
                            <strong>{{ $event->vehicle?->name ?: __('events.unknown_vehicle') }}</strong>
                            <span class="technical-code">{{ $event->vehicle?->registration_number ?: '-' }}</span>
                        </td>
                        @if ($showTechnicalDetails)
                            <td>
                                <strong>{{ $event->device?->name ?: ($event->device?->imei ?: __('events.unknown_tracker')) }}</strong>
                                <span class="technical-code">{{ $event->device?->model ?: $event->device?->imei }}</span>
                            </td>
                        @endif
                        <td>
                            <strong>{{ $event->fleet?->name ?: __('events.unknown_fleet') }}</strong>
                            <span class="technical-code">{{ $event->fleet?->code ?: '-' }}</span>
                        </td>
                        <td>{{ $event->durationLabel() ?: '-' }}</td>
                        <td>{{ $event->started_at?->format('Y-m-d H:i:s') ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showTechnicalDetails ? 7 : 6 }}" class="empty-state">
                            <strong>{{ __('events.empty') }}</strong>
                            <span>{{ __('events.empty_text') }}</span>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@include('partials.datatable-pagination', [
    'paginator' => $events,
    'summary' => __('events.showing', [
        'from' => $events->firstItem() ?? 0,
        'to' => $events->lastItem() ?? 0,
        'total' => $events->total(),
    ]),
    'ariaLabel' => __('events.title'),
    'previousLabel' => __('events.previous'),
    'nextLabel' => __('events.next'),
])
