(function () {
    const dataElement = document.getElementById('dashboardChartData');

    if (!dataElement || typeof ApexCharts === 'undefined') {
        return;
    }

    const payload = JSON.parse(dataElement.textContent || '{}');
    const text = payload.labels || {};
    const charts = [];

    const isDark = () => document.body.classList.contains('dashboard-dark');
    const palette = () => ({
        ink: isDark() ? '#eef5ff' : '#0b1220',
        muted: isDark() ? '#9bb4d8' : '#64748b',
        grid: isDark() ? '#1d3b67' : '#dce6f4',
        surface: isDark() ? '#0b1c37' : '#ffffff',
        primary: '#1f4ed8',
        primaryDark: '#171064',
        cyan: '#0ea5e9',
        green: '#10b981',
        amber: '#f59e0b',
        red: '#ef4444',
        purple: '#7c3aed',
    });

    const baseOptions = () => {
        const colors = palette();

        return {
            chart: {
                fontFamily: 'inherit',
                foreColor: colors.muted,
                toolbar: { show: false },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 650,
                },
            },
            dataLabels: { enabled: false },
            grid: {
                borderColor: colors.grid,
                strokeDashArray: 5,
                padding: { left: 8, right: 12 },
            },
            legend: {
                fontWeight: 750,
                labels: { colors: colors.muted },
                markers: { radius: 8 },
            },
            noData: {
                text: payload.emptyText || 'No data',
                align: 'center',
                verticalAlign: 'middle',
                style: {
                    color: colors.muted,
                    fontSize: '13px',
                    fontWeight: 800,
                },
            },
            tooltip: {
                theme: isDark() ? 'dark' : 'light',
            },
        };
    };

    const mount = (selector, optionsFactory) => {
        const element = document.querySelector(selector);

        if (!element) {
            return;
        }

        const chart = new ApexCharts(element, optionsFactory());
        chart.render();
        charts.push({ chart, optionsFactory });
    };

    let worldMapInstance = null;
    let worldMapResizeHandler = null;

    const renderWorldMap = () => {
        const container = document.querySelector('[data-dashboard-world-map]');
        const canvas = container ? container.querySelector('.world-map-canvas') : null;

        if (!container || !canvas || typeof Datamap === 'undefined') {
            return;
        }

        const clusters = (payload.map && Array.isArray(payload.map.clusters))
            ? payload.map.clusters
            : [];

        container.classList.toggle('is-empty', clusters.length === 0);
        canvas.innerHTML = '';

        if (worldMapResizeHandler) {
            window.removeEventListener('resize', worldMapResizeHandler);
            worldMapResizeHandler = null;
        }

        const colors = palette();
        const bubbles = clusters.map((cluster) => {
            const total = Number(cluster.total || 0);

            return {
                name: cluster.label,
                country: cluster.country,
                latitude: Number(cluster.latitude),
                longitude: Number(cluster.longitude),
                radius: Math.min(30, Math.max(9, 8 + Math.sqrt(total) * 5)),
                total,
                online: Number(cluster.online || 0),
                moving: Number(cluster.moving || 0),
                url: cluster.url || '',
                fillKey: Number(cluster.moving || 0) > 0
                    ? 'moving'
                    : (Number(cluster.online || 0) > 0 ? 'online' : 'offline'),
            };
        });

        const geographyFill = isDark() ? '#10294a' : '#d7e7f7';
        const geographyBorder = isDark() ? '#2b4c78' : '#a9c4e3';
        const geographyHover = isDark() ? '#194979' : '#b9d5ff';

        worldMapInstance = new Datamap({
            element: canvas,
            scope: 'world',
            projection: 'mercator',
            responsive: true,
            geographyConfig: {
                borderColor: geographyBorder,
                borderOpacity: 1,
                borderWidth: 0.8,
                highlightOnHover: false,
                highlightBorderColor: geographyBorder,
                highlightBorderWidth: 0.8,
                highlightFillColor: geographyHover,
                popupOnHover: false,
            },
            fills: {
                defaultFill: geographyFill,
                online: colors.green,
                moving: colors.cyan,
                offline: colors.red,
            },
        });

        worldMapInstance.bubbles(bubbles, {
            borderColor: '#ffffff',
            borderWidth: 3,
            fillOpacity: 0.9,
            highlightFillColor: colors.primary,
            highlightBorderColor: '#ffffff',
            highlightBorderWidth: 3,
            popupTemplate: (geography, data) => `
                <div class="hoverinfo world-map-hover">
                    <strong>${data.name}</strong>
                    <span>${data.country} - ${data.total} ${text.trackers || 'Traceurs'}</span>
                    <span>${data.online || 0} ${text.online || 'En ligne'} - ${data.moving || 0} ${text.moving || 'En mouvement'}</span>
                </div>
            `,
        });

        if (worldMapInstance.svg) {
            worldMapInstance.svg
                .attr('role', 'img')
                .attr('aria-label', text.worldMap || 'Carte mondiale des traceurs');

            worldMapInstance.svg
                .selectAll('.datamaps-subunit')
                .style('fill', geographyFill)
                .style('stroke', geographyBorder)
                .style('stroke-width', '0.8px')
                .on('mouseover.exad', function () {
                    d3.select(this)
                        .style('fill', geographyHover)
                        .style('stroke', geographyBorder)
                        .style('stroke-width', '0.8px');
                })
                .on('mouseout.exad', function () {
                    d3.select(this)
                        .style('fill', geographyFill)
                        .style('stroke', geographyBorder)
                        .style('stroke-width', '0.8px');
                });

            worldMapInstance.svg
                .selectAll('.datamaps-bubble')
                .attr('role', 'button')
                .attr('tabindex', 0)
                .attr('aria-label', (data) => `${data.name} - ${data.total} ${text.trackers || 'Traceurs'}`)
                .style('cursor', 'pointer')
                .on('click.exad', (data) => {
                    if (data && data.url) {
                        window.location.href = data.url;
                    }
                })
                .on('keydown.exad', (data) => {
                    const event = d3.event;

                    if (!data || !data.url || !event || ![13, 32].includes(event.keyCode)) {
                        return;
                    }

                    event.preventDefault();
                    window.location.href = data.url;
                });
        }

        worldMapResizeHandler = () => {
            if (worldMapInstance && typeof worldMapInstance.resize === 'function') {
                worldMapInstance.resize();
            }
        };
        window.addEventListener('resize', worldMapResizeHandler);
    };
    mount('[data-dashboard-chart="trend"]', () => {
        const colors = palette();
        const trend = payload.trend || {};

        return {
            ...baseOptions(),
            chart: {
                ...baseOptions().chart,
                type: 'line',
                height: 330,
                zoom: { enabled: false },
            },
            colors: [colors.primary, colors.green],
            series: [
                {
                    name: text.positions || 'Positions',
                    type: 'area',
                    data: trend.positions || [],
                },
                {
                    name: text.averageSpeed || 'Average speed',
                    type: 'line',
                    data: trend.speed || [],
                },
            ],
            stroke: {
                curve: 'smooth',
                width: [4, 3],
            },
            fill: {
                type: ['gradient', 'solid'],
                gradient: {
                    opacityFrom: 0.28,
                    opacityTo: 0.03,
                },
            },
            markers: {
                size: 0,
                hover: { size: 6 },
            },
            xaxis: {
                categories: trend.labels || [],
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: { style: { fontWeight: 700 } },
            },
            yaxis: [
                {
                    min: 0,
                    forceNiceScale: true,
                    labels: { style: { fontWeight: 700 } },
                },
                {
                    opposite: true,
                    min: 0,
                    forceNiceScale: true,
                    labels: { style: { fontWeight: 700 } },
                },
            ],
        };
    });

    mount('[data-dashboard-chart="status"]', () => {
        const colors = palette();
        const status = payload.status || {};

        return {
            ...baseOptions(),
            chart: {
                ...baseOptions().chart,
                type: 'donut',
                height: 330,
            },
            colors: [colors.green, '#94a3b8', colors.red, colors.amber],
            labels: status.labels || [],
            series: status.series || [],
            plotOptions: {
                pie: {
                    donut: {
                        size: '68%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: text.total || 'Total',
                                color: colors.muted,
                                fontWeight: 800,
                            },
                            value: {
                                color: colors.ink,
                                fontWeight: 850,
                            },
                        },
                    },
                },
            },
            stroke: {
                width: 5,
                colors: [colors.surface],
            },
            legend: {
                ...baseOptions().legend,
                position: 'bottom',
            },
        };
    });

    mount('[data-dashboard-chart="health"]', () => {
        const colors = palette();
        const health = payload.health || {};

        return {
            ...baseOptions(),
            chart: {
                ...baseOptions().chart,
                type: 'radialBar',
                height: 330,
            },
            colors: [colors.green, colors.cyan, colors.primary],
            labels: health.labels || [],
            series: health.series || [],
            plotOptions: {
                radialBar: {
                    hollow: { size: '38%' },
                    track: {
                        background: isDark() ? '#102849' : '#eef4fb',
                    },
                    dataLabels: {
                        name: {
                            color: colors.muted,
                            fontWeight: 800,
                        },
                        value: {
                            color: colors.ink,
                            fontWeight: 850,
                            formatter: (value) => `${Math.round(value)}%`,
                        },
                        total: {
                            show: true,
                            label: text.score || 'Score',
                            color: colors.muted,
                            fontWeight: 800,
                            formatter: (context) => {
                                const values = context.config.series || [];
                                const average = values.length
                                    ? values.reduce((sum, value) => sum + Number(value || 0), 0) / values.length
                                    : 0;

                                return `${Math.round(average)}%`;
                            },
                        },
                    },
                },
            },
            legend: { show: false },
        };
    });

    mount('[data-dashboard-chart="fleet"]', () => {
        const colors = palette();
        const fleet = payload.fleet || {};

        return {
            ...baseOptions(),
            chart: {
                ...baseOptions().chart,
                type: 'bar',
                height: 330,
            },
            colors: [colors.primary],
            series: [
                {
                    name: text.trackers || 'Trackers',
                    data: fleet.series || [],
                },
            ],
            plotOptions: {
                bar: {
                    borderRadius: 8,
                    barHeight: '48%',
                    horizontal: true,
                },
            },
            xaxis: {
                categories: fleet.labels || [],
                min: 0,
                forceNiceScale: true,
                labels: { style: { fontWeight: 700 } },
            },
            yaxis: {
                labels: { style: { fontWeight: 800 } },
            },
        };
    });

    const observer = new MutationObserver(() => {
        charts.forEach(({ chart, optionsFactory }) => chart.updateOptions(optionsFactory(), false, true));
        renderWorldMap();
    });

    observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
    renderWorldMap();
})();
