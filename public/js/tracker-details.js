(() => {
    const modalElement = document.getElementById('trackerDetailsModal');
    const content = modalElement?.querySelector('[data-tracker-details-content]');

    if (!modalElement || !content) {
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const loadingText = modalElement.dataset.trackerDetailsLoading || '';
    const errorText = modalElement.dataset.trackerDetailsError || '';
    const loadingHtml = `<div class="tracker-details-loading"><span></span>${loadingText}</div>`;
    const errorHtml = `<div class="tracker-details-error"><i class="fa-solid fa-triangle-exclamation"></i>${errorText}</div>`;

    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-tracker-details]');

        if (!button) {
            return;
        }

        content.innerHTML = loadingHtml;
        modal.show();

        try {
            const response = await fetch(button.dataset.detailsUrl, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const payload = await response.json();
            content.innerHTML = payload.html;
        } catch (error) {
            content.innerHTML = errorHtml;
        }
    });
})();
