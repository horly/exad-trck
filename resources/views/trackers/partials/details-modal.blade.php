<div class="modal fade users-modal tracker-details-modal" id="trackerDetailsModal" tabindex="-1" aria-labelledby="trackerDetailsTitle" aria-hidden="true" data-tracker-details-loading="{{ __('trackers.loading_details') }}" data-tracker-details-error="{{ __('trackers.details_error') }}">
    <div class="modal-dialog modal-dialog-centered tracker-details-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="trackerDetailsTitle">
                    <i class="fa-regular fa-clock"></i>
                    {{ __('trackers.details_title') }}
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('trackers.cancel') }}"></button>
            </div>
            <div class="modal-body" data-tracker-details-content>
                <div class="tracker-details-loading">
                    <span></span>
                    {{ __('trackers.loading_details') }}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn users-cancel-button" data-bs-dismiss="modal">{{ __('trackers.close') }}</button>
            </div>
        </div>
    </div>
</div>
