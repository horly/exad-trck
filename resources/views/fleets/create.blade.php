@include('fleets.partials.form', [
    'title' => __('fleets.create_title'),
    'eyebrow' => __('fleets.create_eyebrow'),
    'action' => route('fleets.store'),
    'method' => 'POST',
    'fleet' => null,
])
