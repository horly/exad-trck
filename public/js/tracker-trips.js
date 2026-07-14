(() => {
    const modalElement = document.getElementById('trackerTripsModal');

    if (!modalElement) {
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const title = modalElement.querySelector('[data-trips-title]');
    const form = modalElement.querySelector('[data-trips-form]');
    const results = modalElement.querySelector('[data-trips-results]');
    const periodInput = modalElement.querySelector('[data-trips-period]');
    const periodChoices = Array.from(modalElement.querySelectorAll('[data-trips-period-choice]'));
    const customFields = modalElement.querySelector('[data-trips-custom]');
    const submitButton = modalElement.querySelector('[data-trips-submit]');
    const waitingHtml = results.innerHTML;
    const defaultTitle = title.textContent;
    let currentUrl = '';
    let tripFeatures = new Map();
    let selectedTripIndex = null;

    const loadingHtml = `<div class="tracker-details-loading"><span></span>${modalElement.dataset.tripsLoading || submitButton.textContent}</div>`;
    const errorHtml = `<div class="tracker-details-error"><i class="fa-solid fa-triangle-exclamation"></i><span>${modalElement.dataset.tripsError || ''}</span></div>`;

    const openTripsPanel = () => {
        document.body.classList.add('trip-panel-open');
        modal.show();
        loadTrips();
    };

    const closeSourceThenOpen = () => {
        document.dispatchEvent(new CustomEvent('exad:close-map-popup'));

        const detailsElement = document.getElementById('trackerDetailsModal');

        if (!detailsElement?.classList.contains('show')) {
            openTripsPanel();
            return;
        }

        detailsElement.addEventListener('hidden.bs.modal', openTripsPanel, { once: true });
        bootstrap.Modal.getOrCreateInstance(detailsElement).hide();
    };

    const setLoading = (isLoading) => {
        submitButton.disabled = isLoading;
        submitButton.classList.toggle('is-loading', isLoading);
    };

    const selectedPeriod = () => periodChoices.find((choice) => choice.checked)?.value || 'today';

    const syncPeriod = () => {
        periodInput.value = selectedPeriod();
        customFields.hidden = periodInput.value !== 'custom';
    };

    const requestUrl = () => {
        const params = new URLSearchParams(new FormData(form));
        params.set('period', periodInput.value || selectedPeriod());
        params.delete('trip_period_choice');

        return `${currentUrl}?${params.toString()}`;
    };

    const formatDuration = (seconds) => {
        const safeSeconds = Math.max(0, Math.round(Number(seconds) || 0));
        const hours = Math.floor(safeSeconds / 3600);
        const minutes = Math.floor((safeSeconds % 3600) / 60);
        const remainingSeconds = safeSeconds % 60;

        return hours > 0
            ? `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`
            : `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
    };

    const replayElements = () => ({
        toggle: results.querySelector('[data-trip-replay-toggle]'),
        reset: results.querySelector('[data-trip-replay-reset]'),
        speed: results.querySelector('[data-trip-replay-speed]'),
        progress: results.querySelector('[data-trip-replay-progress]'),
        time: results.querySelector('[data-trip-replay-time]'),
        icon: results.querySelector('[data-trip-replay-icon]'),
        label: results.querySelector('[data-trip-replay-label]'),
    });

    const syncReplayAvailability = () => {
        const controls = replayElements();
        const enabled = Boolean(window.exadTripReplayAvailable && selectedTripIndex !== null);

        [controls.toggle, controls.reset, controls.speed, controls.progress].forEach((control) => {
            if (control) {
                control.disabled = !enabled;
            }
        });
    };

    const updateReplayProgress = (elapsed = 0, duration = null) => {
        const controls = replayElements();
        const selectedFeature = tripFeatures.get(Number(selectedTripIndex));
        const totalDuration = duration ?? selectedFeature?.properties?.duration_seconds ?? 0;
        const ratio = totalDuration > 0 ? Math.min(1, Math.max(0, elapsed / totalDuration)) : 0;

        if (controls.progress) {
            controls.progress.value = String(Math.round(ratio * 1000));
        }

        if (controls.time) {
            controls.time.textContent = `${formatDuration(elapsed)} / ${formatDuration(totalDuration)}`;
        }
    };

    const activateTrip = (index, { dispatch = true, fit = true } = {}) => {
        const numericIndex = Number(index);

        if (!tripFeatures.has(numericIndex)) {
            return;
        }

        selectedTripIndex = numericIndex;
        results.querySelectorAll('[data-trip-card]').forEach((card) => {
            const isSelected = Number(card.dataset.tripIndex) === numericIndex;
            card.classList.toggle('is-selected', isSelected);
            card.querySelector('[data-trip-select]')?.setAttribute('aria-pressed', String(isSelected));
        });

        updateReplayProgress(0);
        syncReplayAvailability();

        if (dispatch) {
            document.dispatchEvent(new CustomEvent('exad:trip-selected', {
                detail: { index: numericIndex, fit },
            }));
        }
    };

    const loadTrips = async () => {
        if (!currentUrl) {
            return;
        }

        syncPeriod();
        setLoading(true);
        results.innerHTML = loadingHtml;
        selectedTripIndex = null;
        tripFeatures = new Map();

        try {
            const response = await fetch(requestUrl(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const payload = await response.json();
            const features = payload.geojson?.features || [];
            tripFeatures = new Map(features.map((feature) => [Number(feature.properties?.index), feature]));
            results.innerHTML = payload.html;
            document.dispatchEvent(new CustomEvent('exad:trips-loaded', {
                detail: {
                    geojson: payload.geojson,
                    summary: payload.summary,
                },
            }));

            if (features.length) {
                requestAnimationFrame(() => activateTrip(features[0].properties.index, { fit: false }));
            }
        } catch (error) {
            console.error(error);
            results.innerHTML = errorHtml;
        } finally {
            setLoading(false);
        }
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-trips-open]');

        if (!trigger) {
            return;
        }

        event.preventDefault();

        currentUrl = trigger.dataset.tripsUrl || '';
        title.textContent = trigger.dataset.tripsName
            ? `${defaultTitle} - ${trigger.dataset.tripsName}`
            : defaultTitle;
        form.reset();
        periodChoices[0].checked = true;
        syncPeriod();
        results.innerHTML = waitingHtml;
        closeSourceThenOpen();
    });

    document.addEventListener('click', (event) => {
        const clearButton = event.target.closest('[data-trips-clear]');
        const tripButton = event.target.closest('[data-trip-select]');
        const replayButton = event.target.closest('[data-trip-replay-toggle]');
        const resetButton = event.target.closest('[data-trip-replay-reset]');

        if (clearButton) {
            results.innerHTML = waitingHtml;
            selectedTripIndex = null;
            tripFeatures = new Map();
            document.dispatchEvent(new CustomEvent('exad:trips-cleared'));
            return;
        }

        if (tripButton) {
            activateTrip(tripButton.dataset.tripSelect);
            return;
        }

        if (replayButton && selectedTripIndex !== null) {
            modalElement.classList.add('is-replay-mode');
            document.body.classList.add('trip-replay-active');
            document.dispatchEvent(new CustomEvent('exad:trip-replay-toggle', {
                detail: { index: selectedTripIndex },
            }));
            return;
        }

        if (resetButton && selectedTripIndex !== null) {
            modalElement.classList.remove('is-replay-mode');
            document.body.classList.remove('trip-replay-active');
            document.dispatchEvent(new CustomEvent('exad:trip-replay-reset', {
                detail: { index: selectedTripIndex },
            }));
        }
    });

    document.addEventListener('input', (event) => {
        const colorInput = event.target.closest('[data-trip-color]');
        const progressInput = event.target.closest('[data-trip-replay-progress]');

        if (colorInput) {
            const index = Number(colorInput.dataset.tripColor);
            const color = colorInput.value;
            results.querySelector(`[data-trip-card][data-trip-index="${index}"]`)?.style.setProperty('--trip-color', color);
            document.dispatchEvent(new CustomEvent('exad:trip-color-changed', {
                detail: { index, color },
            }));
            return;
        }

        if (progressInput && selectedTripIndex !== null) {
            document.dispatchEvent(new CustomEvent('exad:trip-replay-seek', {
                detail: {
                    index: selectedTripIndex,
                    ratio: Number(progressInput.value) / 1000,
                },
            }));
        }
    });

    document.addEventListener('change', (event) => {
        const speedInput = event.target.closest('[data-trip-replay-speed]');

        if (!speedInput || selectedTripIndex === null) {
            return;
        }

        document.dispatchEvent(new CustomEvent('exad:trip-replay-speed', {
            detail: {
                index: selectedTripIndex,
                speed: Number(speedInput.value),
            },
        }));
    });

    document.addEventListener('exad:trip-replay-ready', syncReplayAvailability);
    document.addEventListener('exad:trip-map-selected', (event) => {
        activateTrip(event.detail?.index, { dispatch: false, fit: false });
    });
    document.addEventListener('exad:trip-replay-progress', (event) => {
        updateReplayProgress(event.detail?.elapsed, event.detail?.duration);
    });
    document.addEventListener('exad:trip-replay-state', (event) => {
        const controls = replayElements();
        const isPlaying = Boolean(event.detail?.playing);
        const playLabel = controls.toggle?.dataset.playLabel || '';
        const pauseLabel = controls.toggle?.dataset.pauseLabel || '';

        controls.icon?.classList.toggle('fa-play', !isPlaying);
        controls.icon?.classList.toggle('fa-pause', isPlaying);
        if (controls.label) {
            controls.label.textContent = isPlaying ? pauseLabel : playLabel;
        }
        controls.toggle?.classList.toggle('is-playing', isPlaying);
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        loadTrips();
    });

    periodChoices.forEach((choice) => {
        choice.addEventListener('change', syncPeriod);
    });

    modalElement.addEventListener('hidden.bs.modal', () => {
        modalElement.classList.remove('is-replay-mode');
        document.body.classList.remove('trip-panel-open');
        document.body.classList.remove('trip-replay-active');
        document.dispatchEvent(new CustomEvent('exad:trips-cleared'));
        selectedTripIndex = null;
        tripFeatures = new Map();
    });
})();
