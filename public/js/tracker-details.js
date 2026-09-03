(() => {
    const modalElement = document.getElementById('trackerDetailsModal');
    const content = modalElement?.querySelector('[data-tracker-details-content]');

    if (!modalElement || !content) {
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const loadingText = modalElement.dataset.trackerDetailsLoading || '';
    const errorText = modalElement.dataset.trackerDetailsError || '';
    const loadingHtml = `<div class="tracker-details-loading" role="status"><span></span>${loadingText}</div>`;
    const errorHtml = `<div class="tracker-details-error" role="alert"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>${errorText}</div>`;
    let activeRequest = null;
    let requestId = 0;

    modalElement.addEventListener('hidden.bs.modal', () => {
        activeRequest?.abort();
        activeRequest = null;
        requestId += 1;
        content.setAttribute('aria-busy', 'false');
    });

    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-tracker-details]');

        if (!button) {
            return;
        }

        activeRequest?.abort();
        activeRequest = new AbortController();
        const currentRequestId = ++requestId;

        content.setAttribute('aria-busy', 'true');
        content.innerHTML = loadingHtml;
        modal.show();

        try {
            const response = await fetch(button.dataset.detailsUrl, {
                signal: activeRequest.signal,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const payload = await response.json();

            if (currentRequestId !== requestId) {
                return;
            }

            content.innerHTML = payload.html;
        } catch (error) {
            if (error.name === 'AbortError' || currentRequestId !== requestId) {
                return;
            }

            content.innerHTML = errorHtml;
        } finally {
            if (currentRequestId === requestId) {
                activeRequest = null;
                content.setAttribute('aria-busy', 'false');
            }
        }
    });
})();
