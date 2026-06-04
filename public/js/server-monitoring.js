(function () {
    const root = document.querySelector('[data-server-monitoring]');

    if (!root) {
        return;
    }

    const endpoint = root.dataset.endpoint;
    const unavailable = root.dataset.unavailable || '--';
    const labels = {
        cpu: root.dataset.labelCpu || 'CPU',
        ram: root.dataset.labelRam || 'RAM',
        disk: root.dataset.labelDisk || 'Disk',
        load: root.dataset.labelLoad || 'Load',
        download: root.dataset.labelDownload || 'Inbound',
        upload: root.dataset.labelUpload || 'Outbound',
    };
    const interfacesBody = root.querySelector('[data-network-interfaces]');
    const historyLimit = 30;
    const history = {
        categories: [],
        cpu: [],
        ram: [],
        download: [],
        upload: [],
        load: [],
    };
    const charts = {};

    const formatBytes = (value) => {
        if (value === null || value === undefined || Number.isNaN(Number(value))) {
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

    const numberOrNull = (value) => {
        if (value === null || value === undefined || value === '') {
            return null;
        }

        const number = Number(value);

        return Number.isFinite(number) ? number : null;
    };

    const numberOrZero = (value) => numberOrNull(value) ?? 0;

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

    const chartTheme = () => {
        const dark = document.body.classList.contains('dashboard-dark');

        return {
            mode: dark ? 'dark' : 'light',
            foreColor: dark ? '#dbeafe' : '#64748b',
            grid: dark ? 'rgba(96, 165, 250, 0.12)' : '#e5edf7',
            panel: dark ? 'transparent' : 'transparent',
        };
    };

    const lineOptions = (series, colors) => {
        const theme = chartTheme();

        return {
            chart: {
                type: 'area',
                height: 275,
                toolbar: { show: false },
                animations: { enabled: true, easing: 'easeinout', speed: 420 },
                background: theme.panel,
                foreColor: theme.foreColor,
                fontFamily: '"Manrope", system-ui, sans-serif',
            },
            series,
            colors,
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 3 },
            fill: {
                type: 'gradient',
                gradient: { shadeIntensity: 0.9, opacityFrom: 0.28, opacityTo: 0.03 },
            },
            grid: { borderColor: theme.grid, strokeDashArray: 5 },
            legend: { show: true, fontWeight: 800 },
            markers: { size: 0 },
            xaxis: {
                categories: history.categories,
                labels: { show: false },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: { labels: { formatter: (value) => `${Math.round(value)}` } },
            tooltip: { theme: theme.mode },
        };
    };

    const createCharts = () => {
        if (!window.ApexCharts) {
            return;
        }

        const cpuMemory = root.querySelector('[data-monitoring-chart="cpu-memory"]');
        const network = root.querySelector('[data-monitoring-chart="network"]');
        const load = root.querySelector('[data-monitoring-chart="load"]');
        const disk = root.querySelector('[data-monitoring-chart="disk"]');

        if (cpuMemory) {
            charts.cpuMemory = new ApexCharts(cpuMemory, lineOptions([
                { name: labels.cpu, data: history.cpu },
                { name: labels.ram, data: history.ram },
            ], ['#2563eb', '#10b981']));
            charts.cpuMemory.render();
        }

        if (network) {
            charts.network = new ApexCharts(network, lineOptions([
                { name: labels.download, data: history.download },
                { name: labels.upload, data: history.upload },
            ], ['#0ea5e9', '#7c3aed']));
            charts.network.render();
        }

        if (load) {
            charts.load = new ApexCharts(load, lineOptions([
                { name: labels.load, data: history.load },
            ], ['#f43f5e']));
            charts.load.render();
        }

        if (disk) {
            const theme = chartTheme();
            charts.disk = new ApexCharts(disk, {
                chart: {
                    type: 'radialBar',
                    height: 275,
                    toolbar: { show: false },
                    background: theme.panel,
                    foreColor: theme.foreColor,
                    fontFamily: '"Manrope", system-ui, sans-serif',
                },
                series: [0],
                colors: ['#1d4ed8'],
                labels: [labels.disk],
                plotOptions: {
                    radialBar: {
                        hollow: { size: '62%' },
                        track: { background: theme.grid },
                        dataLabels: {
                            name: { fontSize: '13px', fontWeight: 900 },
                            value: { fontSize: '28px', fontWeight: 900, formatter: (value) => `${Math.round(value)}%` },
                        },
                    },
                },
                stroke: { lineCap: 'round' },
            });
            charts.disk.render();
        }
    };

    const updateCharts = (data) => {
        if (!window.ApexCharts) {
            return;
        }

        const label = new Date(data.generated_at || Date.now()).toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });

        history.categories.push(label);
        history.cpu.push(numberOrZero(get(data, 'cpu.usage')));
        history.ram.push(numberOrZero(get(data, 'memory.percent')));
        history.download.push(numberOrZero(get(data, 'network.total_rx_rate')));
        history.upload.push(numberOrZero(get(data, 'network.total_tx_rate')));
        history.load.push(Number(numberOrZero(get(data, 'load.one')).toFixed(2)));

        Object.keys(history).forEach((key) => {
            if (history[key].length > historyLimit) {
                history[key].shift();
            }
        });

        charts.cpuMemory?.updateOptions({ xaxis: { categories: history.categories } }, false, false);
        charts.cpuMemory?.updateSeries([
            { name: labels.cpu, data: history.cpu },
            { name: labels.ram, data: history.ram },
        ], true);

        charts.network?.updateOptions({ xaxis: { categories: history.categories } }, false, false);
        charts.network?.updateSeries([
            { name: labels.download, data: history.download },
            { name: labels.upload, data: history.upload },
        ], true);

        charts.load?.updateOptions({ xaxis: { categories: history.categories } }, false, false);
        charts.load?.updateSeries([{ name: labels.load, data: history.load }], true);
        charts.disk?.updateSeries([numberOrZero(get(data, 'disk.percent'))]);
    };

    const renderInterfaces = (interfaces) => {
        if (!interfacesBody) {
            return;
        }

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
        const cpuCores = get(data, 'cpu.cores');
        const memoryPercent = get(data, 'memory.percent');
        const diskPercent = get(data, 'disk.percent');
        const loadFive = get(data, 'load.five');
        const loadFifteen = get(data, 'load.fifteen');

        setText('cpu.usage', formatPercent(cpuUsage));
        setText('cpu.cores', cpuCores ? `${cpuCores} cores` : unavailable);
        setText('memory.percent', formatPercent(memoryPercent));
        setText('memory.used_total', `${formatBytes(get(data, 'memory.used'))} / ${formatBytes(get(data, 'memory.total'))}`);
        setText('memory.swap', `${formatPercent(get(data, 'memory.swap_percent'))} - ${formatBytes(get(data, 'memory.swap_used'))} / ${formatBytes(get(data, 'memory.swap_total'))}`);
        setText('disk.percent', formatPercent(diskPercent));
        setText('disk.used_total', `${formatBytes(get(data, 'disk.used'))} / ${formatBytes(get(data, 'disk.total'))}`);
        setText('load.one', numberOrNull(get(data, 'load.one')) ?? unavailable);
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
        updateCharts(data);
    };

    const load = async () => {
        if (!endpoint) {
            return;
        }

        try {
            const response = await fetch(endpoint, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                cache: 'no-store',
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            render(await response.json());
        } catch (error) {
            renderInterfaces([]);
        }
    };

    createCharts();
    load();
    window.setInterval(load, 2000);
})();
