<section class="trip-results-shell" data-trip-results-shell>
    <div class="trip-results-header">
        <div>
            <span class="trip-results-header-icon"><i class="fa-regular fa-calendar"></i></span>
            <strong>{{ $periodLabel }}</strong>
        </div>
        <button type="button" class="trip-clear-button" data-trips-clear>
            <i class="fa-solid fa-xmark"></i>
            <span>{{ __('trackers.trips_clear') }}</span>
        </button>
    </div>

    @if (count($trips) > 0)
        <div class="trip-replay-toolbar" data-trip-replay>
            <div class="trip-replay-heading">
                <span class="trip-replay-icon"><i class="fa-solid fa-circle-play"></i></span>
                <div>
                    <strong>{{ __('trackers.trip_replay_title') }}</strong>
                    <span>{{ __('trackers.trip_replay_map_hint') }}</span>
                </div>
            </div>

            <div class="trip-replay-controls">
                <button
                    type="button"
                    class="trip-replay-button is-primary"
                    data-trip-replay-toggle
                    data-play-label="{{ __('trackers.trip_play') }}"
                    data-pause-label="{{ __('trackers.trip_pause') }}"
                    disabled
                >
                    <i class="fa-solid fa-play" data-trip-replay-icon></i>
                    <span data-trip-replay-label>{{ __('trackers.trip_play') }}</span>
                </button>

                <label class="trip-speed-control">
                    <span>{{ __('trackers.trip_speed') }}</span>
                    <select data-trip-replay-speed disabled>
                        @foreach ([1, 3, 10, 30, 100, 300] as $speed)
                            <option value="{{ $speed }}">x{{ $speed }}</option>
                        @endforeach
                    </select>
                </label>

                <button type="button" class="trip-replay-button" data-trip-replay-reset disabled>
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>{{ __('trackers.trip_reset') }}</span>
                </button>
            </div>

            <div class="trip-replay-progress">
                <input
                    type="range"
                    min="0"
                    max="1000"
                    value="0"
                    aria-label="{{ __('trackers.trip_progress') }}"
                    data-trip-replay-progress
                    disabled
                >
                <span data-trip-replay-time>00:00 / 00:00</span>
            </div>
        </div>

        <div class="trip-results-list">
            @foreach ($trips as $trip)
                <article
                    class="trip-result-item"
                    style="--trip-color: {{ $trip['color'] }}"
                    data-trip-card
                    data-trip-index="{{ $trip['index'] }}"
                >
                    <button
                        type="button"
                        class="trip-result-select"
                        aria-label="{{ __('trackers.trip_select', ['index' => $trip['index']]) }}"
                        aria-pressed="false"
                        data-trip-select="{{ $trip['index'] }}"
                    >
                        <span class="trip-result-line" aria-hidden="true">
                            <span></span>
                        </span>

                        <span class="trip-result-body">
                            <span class="trip-result-title">
                                <span>
                                    <strong>{{ __('trackers.trip_number', ['index' => $trip['index']]) }}</strong>
                                    <small>{{ $trip['date'] }}</small>
                                </span>
                                <i class="fa-solid fa-chevron-right"></i>
                            </span>

                            <span class="trip-result-stop">
                                <strong>{{ $trip['start_time'] }}</strong>
                                <span>{{ $trip['start_address'] }}</span>
                            </span>
                            <span class="trip-result-stop">
                                <strong>{{ $trip['end_time'] }}</strong>
                                <span>{{ $trip['end_address'] }}</span>
                            </span>

                            <span class="trip-result-meta">
                                <span><i class="fa-solid fa-route"></i>{{ $trip['distance_label'] }}</span>
                                <span><i class="fa-regular fa-clock"></i>{{ $trip['duration_label'] }}</span>
                                <span><i class="fa-solid fa-location-dot"></i>{{ __('trackers.trip_points', ['count' => $trip['point_count']]) }}</span>
                            </span>

                            <span class="trip-result-speeds">
                                <span>
                                    {{ __('trackers.trip_average_speed') }}
                                    <strong>{{ __('trackers.trip_speed_value', ['speed' => number_format($trip['average_speed_kmh'], 1, ',', '')]) }}</strong>
                                </span>
                                <span>
                                    {{ __('trackers.trip_max_speed') }}
                                    <strong>{{ __('trackers.trip_speed_value', ['speed' => number_format($trip['max_speed_kmh'], 1, ',', '')]) }}</strong>
                                </span>
                            </span>
                        </span>
                    </button>

                    <label class="trip-color-control" title="{{ __('trackers.trip_color', ['index' => $trip['index']]) }}">
                        <i class="fa-solid fa-palette"></i>
                        <input
                            type="color"
                            value="{{ $trip['color'] }}"
                            aria-label="{{ __('trackers.trip_color', ['index' => $trip['index']]) }}"
                            data-trip-color="{{ $trip['index'] }}"
                        >
                    </label>
                </article>
            @endforeach
        </div>
    @else
        <div class="trip-empty-state">
            <i class="fa-solid fa-route"></i>
            <strong>{{ __('trackers.trips_empty_title') }}</strong>
            <span>{{ __('trackers.trips_empty_text') }}</span>
        </div>
    @endif

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
