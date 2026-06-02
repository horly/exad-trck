@include('fleets.partials.form', [
    'title' => __('fleets.edit_title'),
    'eyebrow' => __('fleets.edit_eyebrow'),
    'action' => route('fleets.update', $fleet),
    'method' => 'PUT',
    'fleet' => $fleet,
])
