(() => {
    const config = window.exadRealtimeConfig || {};
    const labels = window.exadAlertLabels || {};
    const status = document.querySelector('[data-realtime-status]');
    const liveToast = document.querySelector('[data-alert-live-toast]');
    const liveMessage = document.querySelector('[data-alert-live-message]');
    const csrf = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content;
    let socket;
    let refreshTimer;
    let pollingTimer;
    let lastAlertId = Number(config.latestAlertId || 0);

    const setStatus = (state, message) => {
        if (!status) {
            return;
        }

        status.dataset.state = state;
        status.innerHTML = '';

        const icon = document.createElement('i');
        icon.className = state === 'connected'
            ? 'fa-solid fa-circle-check'
            : state === 'unavailable'
                ? 'fa-solid fa-circle-xmark'
                : 'fa-solid fa-circle-notch fa-spin';

        const span = document.createElement('span');
        span.textContent = message;
        status.append(icon, span);
    };

    const showLiveToast = (alert) => {
        if (!liveToast || !liveMessage) {
            return;
        }

        liveMessage.textContent = alert?.message || alert?.title || '';
        liveToast.hidden = false;
        liveToast.classList.remove('is-hiding');

        const progress = liveToast.querySelector('.app-toast-progress');
        if (progress) {
            progress.style.animation = 'none';
            progress.offsetHeight;
            progress.style.animation = '';
        }

        window.clearTimeout(liveToast.hideTimer);
        liveToast.hideTimer = window.setTimeout(() => {
            liveToast.classList.add('is-hiding');
        }, 5600);
    };

    const rememberAlert = (alert) => {
        const alertId = Number(alert?.id || 0);

        if (alertId > lastAlertId) {
            lastAlertId = alertId;
        }
    };

    const incrementNotificationBadge = (alert) => {
        if (alert?.status && alert.status !== 'new') {
            return;
        }

        const badge = document.querySelector('[data-alert-notification-count]');
        if (!badge) {
            return;
        }

        const current = badge.textContent.trim() === '99+'
            ? 99
            : Number(badge.textContent.trim() || 0);
        const next = current + 1;

        badge.textContent = next > 99 ? '99+' : String(next);
        badge.classList.remove('is-hidden');
    };

    const handleAlert = (alert) => {
        if (!alert) {
            return;
        }

        const alertId = Number(alert.id || 0);

        if (alertId > 0 && alertId <= lastAlertId) {
            return;
        }

        rememberAlert(alert);
        incrementNotificationBadge(alert);
        showLiveToast(alert);
        refreshAlertsTable();
    };

    const refreshAlertsTable = async () => {
        const container = document.querySelector('[data-datatable-container]');
        if (!container || !config.alertsIndexEndpoint || !window.location.pathname.includes('/alerts')) {
            return;
        }

        const url = new URL(config.alertsIndexEndpoint, window.location.origin);

        window.clearTimeout(refreshTimer);
        refreshTimer = window.setTimeout(async () => {
            const response = await fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            container.innerHTML = payload.html;
            document.dispatchEvent(new CustomEvent('exad:datatable-stats', { detail: payload.stats || {} }));
        }, 250);
    };

    const updateStats = (stats) => {
        Object.entries(stats || {}).forEach(([key, value]) => {
            document.querySelector(`[data-alert-stat="${key}"]`)?.replaceChildren(String(value));
        });
    };

    const subscribe = async (socketId) => {
        const response = await fetch(config.authEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                socket_id: socketId,
                channel_name: config.channel,
            }),
        });

        if (!response.ok) {
            throw new Error('Broadcast authorization failed.');
        }

        const auth = await response.json();
        socket.send(JSON.stringify({
            event: 'pusher:subscribe',
            data: {
                auth: auth.auth,
                channel: config.channel,
            },
        }));
    };

    const parsePayloadData = (data) => {
        if (typeof data !== 'string') {
            return data || {};
        }

        if (data.trim() === '') {
            return {};
        }

        try {
            return JSON.parse(data);
        } catch {
            return {};
        }
    };

    const pollRecentAlerts = async () => {
        if (!config.recentEndpoint) {
            return;
        }

        const url = new URL(config.recentEndpoint, window.location.origin);
        url.searchParams.set('after', String(lastAlertId));

        const response = await fetch(url.toString(), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            return;
        }

        const payload = await response.json();
        (payload.alerts || []).forEach(handleAlert);

        if (Number(payload.latest_id || 0) > lastAlertId) {
            lastAlertId = Number(payload.latest_id);
        }
    };

    const startPolling = () => {
        if (pollingTimer || !config.recentEndpoint) {
            return;
        }

        pollingTimer = window.setInterval(() => {
            pollRecentAlerts().catch(() => {});
        }, Number(config.pollInterval || 5000));
    };

    const handleMessage = async (event) => {
        const payload = JSON.parse(event.data);
        const data = parsePayloadData(payload.data);

        if (payload.event === 'pusher:connection_established') {
            await subscribe(data.socket_id);
            setStatus('connected', labels.connected || 'Realtime connected');
            return;
        }

        if (payload.event === 'pusher:subscription_succeeded') {
            setStatus('connected', labels.connected || 'Realtime connected');
            return;
        }

        if (payload.event === config.event || payload.event === `.${config.event}`) {
            if (config.recentEndpoint) {
                pollRecentAlerts().catch(() => handleAlert(data.alert));
                return;
            }

            handleAlert(data.alert);
        }
    };

    const connect = () => {
        if (!config.key || !config.host || !config.port) {
            setStatus('unavailable', labels.unavailable || 'Realtime unavailable');
            return;
        }

        setStatus('connecting', labels.connecting || 'Connecting realtime...');

        const protocol = config.scheme === 'https' ? 'wss' : 'ws';
        const url = `${protocol}://${config.host}:${config.port}/app/${encodeURIComponent(config.key)}?protocol=7&client=exad&version=1.0&flash=false`;

        socket = new WebSocket(url);
        socket.addEventListener('message', (event) => {
            handleMessage(event).catch(() => setStatus('unavailable', labels.unavailable || 'Realtime unavailable'));
        });
        socket.addEventListener('close', () => {
            setStatus('unavailable', labels.unavailable || 'Realtime unavailable');
            startPolling();
        });
        socket.addEventListener('error', () => {
            setStatus('unavailable', labels.unavailable || 'Realtime unavailable');
            startPolling();
        });
    };

    liveToast?.querySelector('[data-alert-live-close]')?.addEventListener('click', () => {
        liveToast.classList.add('is-hiding');
    });

    document.addEventListener('exad:datatable-stats', (event) => updateStats(event.detail));

    startPolling();
    connect();
})();
