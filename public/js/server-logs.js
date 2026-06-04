(function () {
    const root = document.querySelector('[data-server-logs]');

    if (!root) {
        return;
    }

    const output = root.querySelector('[data-log-output]');
    const meta = root.querySelector('[data-log-meta]');
    const state = root.querySelector('[data-log-state]');
    const linesSelect = root.querySelector('[data-log-lines]');
    const refreshButton = root.querySelector('[data-log-refresh]');
    const pauseButton = root.querySelector('[data-log-pause]');
    const tabs = Array.from(root.querySelectorAll('[data-log-key]'));
    const endpoint = root.dataset.endpoint;
    const liveLabel = root.dataset.liveLabel || 'Live';
    const pausedLabel = root.dataset.pausedLabel || 'Paused';
    const errorLabel = root.dataset.errorLabel || 'Unable to load logs';
    let selected = root.dataset.selected || 'gps-tcp';
    let paused = false;
    let timer = null;
    let controller = null;

    const scrollToBottom = () => {
        output.scrollTop = output.scrollHeight;
    };

    const setState = (label, isLive) => {
        state.classList.toggle('is-live', isLive);
        state.classList.toggle('is-paused', !isLive);
        state.querySelector('span').textContent = label;
    };

    const updateTabs = () => {
        tabs.forEach((tab) => {
            const isActive = tab.dataset.logKey === selected;
            tab.classList.toggle('active', isActive);
            tab.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    };

    const loadLogs = async () => {
        if (paused || !endpoint) {
            return;
        }

        controller?.abort();
        controller = new AbortController();

        const url = new URL(endpoint, window.location.origin);
        url.searchParams.set('log', selected);
        url.searchParams.set('lines', linesSelect.value || root.dataset.lines || '300');

        try {
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: controller.signal,
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            output.textContent = data.content || data.message || '';
            meta.textContent = data.exists
                ? `${data.size} · ${data.updated_at || ''}`
                : data.message;
            setState(liveLabel, true);
            scrollToBottom();
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            meta.textContent = errorLabel;
            setState(errorLabel, false);
        }
    };

    const schedule = () => {
        window.clearInterval(timer);
        timer = window.setInterval(loadLogs, 1500);
    };

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            selected = tab.dataset.logKey;
            updateTabs();
            loadLogs();
        });
    });

    linesSelect.addEventListener('change', loadLogs);
    refreshButton.addEventListener('click', loadLogs);

    pauseButton.addEventListener('click', () => {
        paused = !paused;
        pauseButton.querySelector('i').className = paused ? 'fa-solid fa-play' : 'fa-solid fa-pause';
        pauseButton.querySelector('span').textContent = paused
            ? pauseButton.dataset.resumeLabel || 'Reprendre'
            : pauseButton.dataset.pauseLabel || 'Pause';
        setState(paused ? pausedLabel : liveLabel, !paused);

        if (!paused) {
            loadLogs();
        }
    });

    loadLogs();
    schedule();
})();
