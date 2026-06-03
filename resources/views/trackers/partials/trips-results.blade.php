<section class="trip-results-shell">
    <div class="trip-results-header">
        <button type="button" class="trip-clear-button" data-trips-clear>
            <i class="fa-solid fa-xmark"></i>
            <span>{{ __('trackers.trips_clear') }}</span>
        </button>
        <strong>{{ $periodLabel }}</strong>
    </div>

    @forelse ($trips as $trip)
        <article class="trip-result-item">
            <div class="trip-result-line">
                <span></span>
                <span></span>
            </div>
            <div class="trip-result-body">
                <p>
                    <strong>{{ $trip['start_time'] }}</strong>
                    {{ $trip['start_address'] }}
                </p>
                <p>
                    <strong>{{ $trip['end_time'] }}</strong>
                    {{ $trip['end_address'] }}
                </p>
                <div class="trip-result-meta">
                    <span>{{ $trip['distance_label'] }}</span>
                    <span>{{ $trip['duration_label'] }}</span>
                </div>
            </div>
        </article>
    @empty
        <div class="trip-empty-state">
            <i class="fa-solid fa-route"></i>
            <strong>{{ __('trackers.trips_empty_title') }}</strong>
            <span>{{ __('trackers.trips_empty_text') }}</span>
        </div>
    @endforelse

    <footer class="trip-results-total">
        <span>{{ __('trackers.trips_total', ['count' => count($trips)]) }}</span>
        <strong>
            <i class="fa-solid fa-route"></i>
            {{ __('trackers.trip_distance_value', ['distance' => number_format($totalDistanceKm, 2, '.', '')]) }}
        </strong>
        <strong>
            <i class="fa-regular fa-clock"></i>
            {{ $totalDurationLabel }}
        </strong>
    </footer>
</section>
