const inactivityRoot = document.querySelector('[data-inactivity-timeout]');
const inactivityLogoutForm = document.querySelector('[data-inactivity-logout]');

if (inactivityRoot && inactivityLogoutForm) {
    const timeout = Number(inactivityRoot.dataset.inactivityTimeout);
    const userId = inactivityRoot.dataset.inactivityUser;
    const loginUrl = inactivityRoot.dataset.inactivityLoginUrl;
    const storageKey = `exad-last-activity-${userId}`;
    const activityEvents = ['pointerdown', 'keydown', 'wheel', 'touchstart'];
    let inactivityTimer;
    let lastStoredAt = 0;
    let loggingOut = false;

    function storedActivity() {
        const value = Number(localStorage.getItem(storageKey));

        return Number.isFinite(value) && value > 0 ? value : Date.now();
    }

    function scheduleInactivityLogout() {
        window.clearTimeout(inactivityTimer);

        const remaining = Math.max(0, timeout - (Date.now() - storedActivity()));
        inactivityTimer = window.setTimeout(logoutAfterInactivity, remaining);
    }

    function recordActivity() {
        const now = Date.now();

        if (now - lastStoredAt < 1000) {
            return;
        }

        lastStoredAt = now;
        localStorage.setItem(storageKey, String(now));
        scheduleInactivityLogout();
    }

    async function logoutAfterInactivity() {
        if (Date.now() - storedActivity() < timeout) {
            scheduleInactivityLogout();

            return;
        }

        if (loggingOut) {
            return;
        }

        loggingOut = true;

        try {
            await fetch(inactivityLogoutForm.action, {
                method: 'POST',
                body: new FormData(inactivityLogoutForm),
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                },
            });
        } finally {
            localStorage.removeItem(storageKey);
            window.location.replace(loginUrl);
        }
    }

    activityEvents.forEach((eventName) => {
        window.addEventListener(eventName, recordActivity, { passive: true });
    });

    window.addEventListener('storage', (event) => {
        if (event.key !== storageKey) {
            return;
        }

        if (event.newValue === null) {
            window.location.replace(loginUrl);

            return;
        }

        scheduleInactivityLogout();
    });

    recordActivity();
}
