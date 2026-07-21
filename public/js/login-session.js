const loginForm = document.querySelector('[data-login-session]');

if (loginForm) {
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            window.location.reload();
        }
    });

    loginForm.addEventListener('submit', async (event) => {
        if (loginForm.dataset.csrfReady === 'true') {
            delete loginForm.dataset.csrfReady;

            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        try {
            const response = await fetch(loginForm.dataset.csrfRefreshUrl, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Unable to refresh the authentication session.');
            }

            const payload = await response.json();
            const tokenField = loginForm.querySelector('input[name="_token"]');

            if (!tokenField || typeof payload.token !== 'string' || payload.token === '') {
                throw new Error('Invalid authentication session token.');
            }

            tokenField.value = payload.token;
            loginForm.dataset.csrfReady = 'true';
            loginForm.requestSubmit(event.submitter || undefined);
        } catch {
            window.location.replace(loginForm.dataset.expiredLoginUrl);
        }
    });
}
