@php
    $sortLink = function (string $column) use ($sort, $direction, $search): string {
        $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';

        return route('alert-rules.index', array_filter([
            'search' => $search,
            'sort' => $column,
            'direction' => $nextDirection,
        ], fn ($value) => $value !== null && $value !== ''));
    };

    $sortIcon = fn (string $column): string => 'fa-solid fa-sort';
@endphp

<div class="users-toolbar">
    <form method="GET" action="{{ route('alert-rules.index') }}" class="users-search" data-datatable-search-form>
        @if ($sort !== null)
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">
        @endif
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('alert_rules.search') }}" data-datatable-search>
    </form>

    <span class="users-count">
        {{ __('alert_rules.rows_count', ['shown' => $rules->count(), 'total' => $rules->total()]) }}
    </span>
</div>

<section class="users-table-card">
    <div class="table-responsive">
        <table class="table align-middle users-table alert-rules-table">
            <thead>
                <tr>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'id' ? 'active' : '' }}" href="{{ $sortLink('id') }}" data-datatable-sort>
                            <span>{{ __('alert_rules.number') }}</span>
                            <i class="{{ $sortIcon('id') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'name' ? 'active' : '' }}" href="{{ $sortLink('name') }}" data-datatable-sort>
                            <span>{{ __('alert_rules.rule') }}</span>
                            <i class="{{ $sortIcon('name') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'category' ? 'active' : '' }}" href="{{ $sortLink('category') }}" data-datatable-sort>
                            <span>{{ __('alert_rules.category') }}</span>
                            <i class="{{ $sortIcon('category') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'severity' ? 'active' : '' }}" href="{{ $sortLink('severity') }}" data-datatable-sort>
                            <span>{{ __('alert_rules.severity') }}</span>
                            <i class="{{ $sortIcon('severity') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'scope' ? 'active' : '' }}" href="{{ $sortLink('scope') }}" data-datatable-sort>
                            <span>{{ __('alert_rules.scope') }}</span>
                            <i class="{{ $sortIcon('scope') }}"></i>
                        </a>
                    </th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'threshold' ? 'active' : '' }}" href="{{ $sortLink('threshold') }}" data-datatable-sort>
                            <span>{{ __('alert_rules.threshold') }}</span>
                            <i class="{{ $sortIcon('threshold') }}"></i>
                        </a>
                    </th>
                    <th>{{ __('alert_rules.channels') }}</th>
                    <th>
                        <a class="datatable-sort-link {{ $sort === 'status' ? 'active' : '' }}" href="{{ $sortLink('status') }}" data-datatable-sort>
                            <span>{{ __('alert_rules.status') }}</span>
                            <i class="{{ $sortIcon('status') }}"></i>
                        </a>
                    </th>
                    <th class="text-end">{{ __('alert_rules.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rules as $rule)
                    @php
                        $channels = collect($rule->channels ?? ['platform'])
                            ->map(fn (string $channel): string => __('alert_rules.channel_'.$channel))
                            ->implode(', ');
                    @endphp
                    <tr>
                        <td>{{ $rules->firstItem() + $loop->index }}</td>
                        <td>
                            <strong>{{ $rule->name }}</strong>
                            <span class="technical-code">{{ __('alert_rules.type_'.$rule->type) }}</span>
                        </td>
                        <td>
                            <span class="alert-rule-category alert-rule-category-{{ $rule->category }}">
                                <i class="fa-solid {{ $rule->category === 'equipment' ? 'fa-satellite-dish' : 'fa-car-side' }}"></i>
                                {{ __('alert_rules.category_'.$rule->category) }}
                            </span>
                        </td>
                        <td>
                            <span class="alert-badge alert-severity-{{ $rule->severity }}">
                                {{ __('alert_rules.severity_'.$rule->severity) }}
                            </span>
                        </td>
                        <td>
                            <strong>{{ $rule->scopeLabel() }}</strong>
                            <span class="technical-code">{{ __('alert_rules.scope_'.$rule->scope_type) }}</span>
                        </td>
                        <td>{{ $rule->thresholdLabel() }}</td>
                        <td>
                            <span class="technical-code">{{ $channels }}</span>
                            <span class="technical-code">{{ $rule->scheduleLabel() }}</span>
                        </td>
                        <td>
                            <span class="status-pill status-{{ $rule->is_active ? 'active' : 'inactive' }}">
                                {{ $rule->is_active ? __('alert_rules.active') : __('alert_rules.inactive') }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-2">
                                <button
                                    type="button"
                                    class="icon-action icon-action-edit"
                                    aria-label="{{ __('alert_rules.edit_rule') }}"
                                    data-rule-edit
                                    data-action="{{ route('alert-rules.update', $rule) }}"
                                    data-name="{{ $rule->name }}"
                                    data-type="{{ $rule->type }}"
                                    data-category="{{ $rule->category }}"
                                    data-severity="{{ $rule->severity }}"
                                    data-scope-type="{{ $rule->scope_type }}"
                                    data-fleet-id="{{ $rule->fleet_id }}"
                                    data-vehicle-id="{{ $rule->vehicle_id }}"
                                    data-device-id="{{ $rule->device_id }}"
                                    data-threshold-value="{{ $rule->threshold_value }}"
                                    data-threshold-unit="{{ $rule->threshold_unit }}"
                                    data-channels="{{ implode(',', $rule->channels ?? []) }}"
                                    data-schedule-days="{{ implode(',', $rule->schedule_days ?? []) }}"
                                    data-starts-at="{{ $rule->starts_at }}"
                                    data-ends-at="{{ $rule->ends_at }}"
                                    data-is-active="{{ $rule->is_active ? '1' : '0' }}"
                                >
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>

                                <form
                                    method="POST"
                                    action="{{ route('alert-rules.destroy', $rule) }}"
                                    data-confirm-delete
                                    data-confirm-title="{{ __('alert_rules.delete_confirm_title') }}"
                                    data-confirm-message="{{ __('alert_rules.delete_confirm_message', ['name' => $rule->name]) }}"
                                    data-confirm-cancel="{{ __('alert_rules.cancel') }}"
                                    data-confirm-submit="{{ __('alert_rules.delete_confirm_submit') }}"
                                    data-confirm-processing="{{ __('alert_rules.processing') }}"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="icon-action icon-action-delete" aria-label="{{ __('alert_rules.delete_confirm_title') }}">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="empty-state">
                            <strong>{{ __('alert_rules.empty') }}</strong>
                            <span>{{ __('alert_rules.empty_text') }}</span>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@include('partials.datatable-pagination', [
    'paginator' => $rules,
    'summary' => __('alert_rules.showing', [
        'from' => $rules->firstItem() ?? 0,
        'to' => $rules->lastItem() ?? 0,
        'total' => $rules->total(),
    ]),
    'ariaLabel' => __('alert_rules.title'),
    'previousLabel' => __('alert_rules.previous'),
    'nextLabel' => __('alert_rules.next'),
])
