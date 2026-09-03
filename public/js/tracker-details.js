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
    let activeDetailsUrl = null;

    async function loadDetails(url, showModal = true) {
        activeRequest?.abort();
        activeRequest = new AbortController();
        const currentRequestId = ++requestId;

        content.setAttribute('aria-busy', 'true');
        content.innerHTML = loadingHtml;

        if (showModal) {
            modal.show();
        }

        try {
            const response = await fetch(url, {
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

            if (currentRequestId === requestId) {
                content.innerHTML = payload.html;
                activeDetailsUrl = url;
            }
        } catch (error) {
            if (error.name !== 'AbortError' && currentRequestId === requestId) {
                content.innerHTML = errorHtml;
            }
        } finally {
            if (currentRequestId === requestId) {
                activeRequest = null;
                content.setAttribute('aria-busy', 'false');
            }
        }
    }

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

        await loadDetails(button.dataset.detailsUrl);
    });

    content.addEventListener('click', async (event) => {
        const trigger = event.target.closest('[data-engine-control-trigger]');

        if (!trigger || trigger.disabled) {
            return;
        }

        const action = trigger.dataset.action;
        const confirmTitle = trigger.dataset.confirmTitle || trigger.textContent.trim();
        const confirmText = trigger.dataset.confirmText || '';
        const swal = window.Swal;
        const confirmation = swal
            ? await swal.fire({
                title: confirmTitle,
                text: confirmText,
                icon: action === 'immobilize' ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonText: trigger.dataset.confirmButton,
                cancelButtonText: trigger.dataset.cancelButton,
                confirmButtonColor: action === 'immobilize' ? '#b42338' : '#15936a',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
                allowOutsideClick: false,
                customClass: { popup: 'exad-engine-swal' },
            })
            : { isConfirmed: window.confirm([confirmTitle, confirmText].filter(Boolean).join('\n\n')) };

        if (!confirmation.isConfirmed || !action || !trigger.dataset.url) {
            return;
        }

        trigger.disabled = true;

        try {
            const response = await fetch(trigger.dataset.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': trigger.dataset.csrf || '',
                },
                body: JSON.stringify({ action, confirmation: true }),
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                const messages = Object.values(payload.errors || {}).flat();
                throw new Error(messages[0] || payload.message || errorText);
            }

            if (swal) {
                await swal.fire({
                    title: trigger.dataset.successTitle,
                    text: payload.message || '',
                    icon: 'success',
                    confirmButtonColor: '#2449c7',
                    customClass: { popup: 'exad-engine-swal' },
                });
            }

            await loadDetails(trigger.dataset.refreshUrl || activeDetailsUrl, false);
        } catch (error) {
            if (swal) {
                await swal.fire({
                    title: trigger.dataset.errorTitle,
                    text: error.message || errorText,
                    icon: 'error',
                    confirmButtonColor: '#2449c7',
                    customClass: { popup: 'exad-engine-swal' },
                });
            } else {
                window.alert(error.message || errorText);
            }

            trigger.disabled = false;
        }
    });
})();
