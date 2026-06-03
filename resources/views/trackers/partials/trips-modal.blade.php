<div class="modal fade users-modal tracker-trips-modal" id="trackerTripsModal" tabindex="-1" aria-labelledby="trackerTripsTitle" aria-hidden="true" data-trips-error="{{ __('trackers.trips_error') }}" data-trips-loading="{{ __('trackers.loading_details') }}">
    <div class="modal-dialog modal-dialog-centered tracker-trips-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="trackerTripsTitle">
                    <i class="fa-solid fa-route"></i>
                    <span data-trips-title>{{ __('trackers.trips_title') }}</span>
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('trackers.cancel') }}"></button>
            </div>

            <div class="modal-body">
                <form class="trip-period-form" data-trips-form>
                    <input type="hidden" name="period" value="today" data-trips-period>

                    <fieldset>
                        <legend>{{ __('trackers.trips_period_title') }}</legend>

                        @foreach (['today', 'yesterday', 'week', 'current_month', 'last_month', 'custom'] as $period)
                            <label class="trip-period-option">
                                <input type="radio" name="trip_period_choice" value="{{ $period }}" @checked($period === 'today') data-trips-period-choice>
                                <span>{{ __('trackers.trip_period_' . $period) }}</span>
                            </label>
                        @endforeach
                    </fieldset>

                    <div class="trip-custom-fields" hidden data-trips-custom>
                        <label>
                            <span>{{ __('trackers.trip_start_date') }}</span>
                            <input type="date" class="form-control" name="start_date" data-trips-start>
                        </label>
                        <label>
                            <span>{{ __('trackers.trip_end_date') }}</span>
                            <input type="date" class="form-control" name="end_date" data-trips-end>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary trip-view-button" data-trips-submit>
                        {{ __('trackers.trips_view') }}
                    </button>
                </form>

                <div class="trip-results" data-trips-results>
                    <div class="tracker-details-loading">
                        <span></span>
                        {{ __('trackers.trips_waiting') }}
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn users-cancel-button" data-bs-dismiss="modal">{{ __('trackers.close') }}</button>
            </div>
        </div>
    </div>
</div>
