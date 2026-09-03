<div class="modal fade users-modal tracker-details-modal" id="trackerDetailsModal" tabindex="-1" aria-labelledby="trackerDetailsTitle" aria-hidden="true" data-tracker-details-loading="{{ __('trackers.loading_details') }}" data-tracker-details-error="{{ __('trackers.details_error') }}">
    <div class="modal-dialog modal-dialog-centered tracker-details-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div class="tracker-details-modal-heading">
                    <span class="tracker-details-modal-icon" aria-hidden="true">
                        <i class="fa-solid fa-satellite-dish"></i>
                    </span>
                    <div>
                        <span class="tracker-details-modal-eyebrow">{{ __('trackers.details_eyebrow') }}</span>
                        <h2 class="modal-title" id="trackerDetailsTitle">{{ __('trackers.details_title') }}</h2>
                    </div>
                </div>
                <button type="button" class="tracker-details-modal-close" data-bs-dismiss="modal" aria-label="{{ __('trackers.close') }}">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body" data-tracker-details-content aria-live="polite">
                <div class="tracker-details-loading">
                    <span></span>
                    {{ __('trackers.loading_details') }}
                </div>
            </div>
            <div class="modal-footer">
                <p class="tracker-details-live-note">
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                    {{ __('trackers.details_live_note') }}
                </p>
                <button type="button" class="btn tracker-details-close-button" data-bs-dismiss="modal">
                    {{ __('trackers.close') }}
                </button>
            </div>
        </div>
    </div>
</div>
