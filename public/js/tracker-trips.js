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

    const loadingHtml = `<div class="tracker-details-loading"><span></span>${modalElement.dataset.tripsLoading || submitButton.textContent}</div>`;
    const errorHtml = `<div class="tracker-details-error"><i class="fa-solid fa-triangle-exclamation"></i><span>${modalElement.dataset.tripsError || ''}</span></div>`;

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

    const loadTrips = async () => {
        if (!currentUrl) {
            return;
        }

        syncPeriod();
        setLoading(true);
        results.innerHTML = loadingHtml;

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
            results.innerHTML = payload.html;
            document.dispatchEvent(new CustomEvent('exad:trips-loaded', {
                detail: {
                    geojson: payload.geojson,
                    summary: payload.summary,
                },
            }));
        } catch (error) {
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

        currentUrl = trigger.dataset.tripsUrl || '';
        title.textContent = trigger.dataset.tripsName
            ? `${defaultTitle} - ${trigger.dataset.tripsName}`
            : defaultTitle;
        form.reset();
        periodChoices[0].checked = true;
        syncPeriod();
        results.innerHTML = waitingHtml;
        modal.show();
        loadTrips();
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-trips-clear]')) {
            return;
        }

        results.innerHTML = waitingHtml;
        document.dispatchEvent(new CustomEvent('exad:trips-cleared'));
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        loadTrips();
    });

    periodChoices.forEach((choice) => {
        choice.addEventListener('change', syncPeriod);
    });
})();
