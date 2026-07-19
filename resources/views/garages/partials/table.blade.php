<div class="users-toolbar">
    <form method="GET" action="{{ route('garages.index') }}" class="users-search" data-datatable-search-form>
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('garages.search') }}" data-datatable-search>
    </form>
    <span class="users-count">{{ $garages->count() }} / {{ $garages->total() }} {{ __('garages.rows') }}</span>
</div>

<section class="users-table-card">
    <div class="table-responsive">
        <table class="table align-middle users-table">
            <thead><tr>
                <th>#</th><th>{{ __('garages.name') }}</th>
                <th>{{ __('garages.responsible') }}</th><th>{{ __('garages.address') }}</th>
                <th>{{ __('garages.active_maintenance') }}</th><th>{{ __('garages.status') }}</th>
                <th class="text-end">{{ __('garages.actions') }}</th>
            </tr></thead>
            <tbody>
                @forelse ($garages as $garage)
                    @php($garageData = $garage->only(['id', 'name', 'type', 'responsible_name', 'dispatcher_name', 'phone', 'email', 'address', 'latitude', 'longitude', 'specialties', 'notes', 'status']))
                    <tr>
                        <td>{{ $garages->firstItem() + $loop->index }}</td>
                        <td><strong>{{ $garage->name }}</strong><span class="technical-code table-secondary-line">{{ __('garages.' . $garage->type) }}</span></td>
                        <td><strong>{{ $garage->responsible_name ?: '-' }}</strong><span class="technical-code table-secondary-line">{{ $garage->phone ?: $garage->email }}</span></td>
                        <td>{{ $garage->address ?: '-' }}</td>
                        <td>{{ $garage->active_maintenance_count }}</td>
                        <td><span class="status-pill status-{{ $garage->status }}">{{ __('garages.status_' . $garage->status) }}</span></td>
                        <td class="text-end"><div class="users-actions">
                            <button type="button" class="icon-action icon-action-edit" data-bs-toggle="modal" data-bs-target="#garageModal" data-garage-edit
                                data-garage="{{ json_encode($garageData) }}"
                                data-action="{{ route('garages.update', $garage) }}" aria-label="{{ __('garages.edit') }}">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </button>
                            <form method="POST" action="{{ route('garages.destroy', $garage) }}" data-confirm-delete data-confirm-title="{{ __('garages.delete_confirm_title') }}" data-confirm-message="{{ __('garages.delete_confirm_message', ['name' => $garage->name]) }}" data-confirm-cancel="{{ __('garages.cancel') }}" data-confirm-submit="{{ __('garages.delete_confirm_submit') }}" data-confirm-processing="{{ __('garages.processing') }}">
                                @csrf @method('DELETE')
                                <button class="icon-action icon-action-delete" aria-label="{{ __('garages.delete') }}"><i class="fa-regular fa-trash-can"></i></button>
                            </form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-state">{{ __('garages.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@include('partials.datatable-pagination', [
    'paginator' => $garages,
    'summary' => __('garages.pagination_summary', ['first' => $garages->firstItem() ?? 0, 'last' => $garages->lastItem() ?? 0, 'total' => $garages->total()]),
    'ariaLabel' => __('garages.pagination'), 'previousLabel' => __('garages.previous'), 'nextLabel' => __('garages.next'),
])
