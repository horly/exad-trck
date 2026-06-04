(function () {
    const root = document.querySelector('[data-server-monitoring]');

    if (!root) {
        return;
    }

    const endpoint = root.dataset.endpoint;
    const unavailable = root.dataset.unavailable || '--';
    const refreshing = root.dataset.refreshing || 'Refreshing...';
    const meta = root.querySelector('[data-monitoring-meta]');
    const interfacesBody = root.querySelector('[data-network-interfaces]');

    const formatBytes = (value) => {
        if (value === null || value === undefined) {
            return unavailable;
        }

        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        let size = Number(value);
        let index = 0;

        while (size >= 1024 && index < units.length - 1) {
            size /= 1024;
            index += 1;
        }

        return `${size.toFixed(size >= 10 || index === 0 ? 0 : 1)} ${units[index]}`;
    };

    const formatRate = (value) => {
        return value === null || value === undefined ? unavailable : `${formatBytes(value)}/s`;
    };

    const formatPercent = (value) => {
        return value === null || value === undefined ? unavailable : `${value}%`;
    };

    const get = (object, path) => path.split('.').reduce((carry, key) => carry?.[key], object);

    const setText = (key, value) => {
        root.querySelectorAll(`[data-monitoring-value="${key}"]`).forEach((element) => {
            element.textContent = value;
        });
    };

    const setBar = (key, value) => {
        root.querySelectorAll(`[data-monitoring-bar="${key}"]`).forEach((element) => {
            element.style.width = `${Math.max(0, Math.min(100, Number(value || 0)))}%`;
        });
    };

    const renderInterfaces = (interfaces) => {
        if (!interfaces || interfaces.length === 0) {
            interfacesBody.innerHTML = `<tr><td colspan="5">${unavailable}</td></tr>`;
            return;
        }

        interfacesBody.innerHTML = interfaces.map((item) => `
            <tr>
                <td><strong>${item.name}</strong></td>
                <td>${formatBytes(item.rx)}</td>
                <td>${formatBytes(item.tx)}</td>
                <td>${formatRate(item.rx_rate)}</td>
                <td>${formatRate(item.tx_rate)}</td>
            </tr>
        `).join('');
    };

    const render = (data) => {
        const cpuUsage = get(data, 'cpu.usage');
        const memoryPercent = get(data, 'memory.percent');
        const diskPercent = get(data, 'disk.percent');
        const loadFive = get(data, 'load.five');
        const loadFifteen = get(data, 'load.fifteen');

        setText('cpu.usage', formatPercent(cpuUsage));
        setText('cpu.cores', `${get(data, 'cpu.cores') || unavailable} cores`);
        setText('memory.percent', formatPercent(memoryPercent));
        setText('memory.used_total', `${formatBytes(get(data, 'memory.used'))} / ${formatBytes(get(data, 'memory.total'))}`);
        setText('memory.swap', `${formatPercent(get(data, 'memory.swap_percent'))} · ${formatBytes(get(data, 'memory.swap_used'))} / ${formatBytes(get(data, 'memory.swap_total'))}`);
        setText('disk.percent', formatPercent(diskPercent));
        setText('disk.used_total', `${formatBytes(get(data, 'disk.used'))} / ${formatBytes(get(data, 'disk.total'))}`);
        setText('load.one', get(data, 'load.one') ?? unavailable);
        setText('load.five_fifteen', `${loadFive ?? unavailable} / ${loadFifteen ?? unavailable}`);
        setText('network.total_rx_rate', formatRate(get(data, 'network.total_rx_rate')));
        setText('network.total_tx_rate', formatRate(get(data, 'network.total_tx_rate')));

        ['hostname', 'os', 'uptime', 'php', 'laravel', 'environment'].forEach((key) => {
            setText(`system.${key}`, get(data, `system.${key}`) || unavailable);
        });

        setBar('cpu.usage', cpuUsage);
        setBar('memory.percent', memoryPercent);
        setBar('disk.percent', diskPercent);
        renderInterfaces(get(data, 'network.interfaces'));

        const date = data.generated_at ? new Date(data.generated_at) : new Date();
        meta.textContent = date.toLocaleTimeString();
    };

    const load = async () => {
        if (!endpoint) {
            return;
        }

        meta.textContent = refreshing;

        try {
            const response = await fetch(endpoint, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            render(await response.json());
        } catch (error) {
            meta.textContent = unavailable;
        }
    };

    load();
    window.setInterval(load, 2000);
})();
