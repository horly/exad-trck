(() => {
    const config = window.exadMapConfig || {};
    const shell = document.querySelector('[data-map-shell]');
    const mapElement = document.getElementById('trackingMap');

    if (!shell || !mapElement) {
        return;
    }

    const emptyState = document.querySelector('[data-map-empty]');
    const statusFilter = document.querySelector('[data-map-status]');
    const fleetFilter = document.querySelector('[data-map-fleet]');
    const searchInput = document.querySelector('[data-map-search]');
    const refreshButton = document.querySelector('[data-map-refresh]');
    const fitButton = document.querySelector('[data-map-fit]');
    const autoInput = document.querySelector('[data-map-auto]');
    const lastUpdate = document.querySelector('[data-map-last-update]');
    const counters = Array.from(document.querySelectorAll('[data-map-count]'));
    const messages = config.messages || {};

    let map;
    let latestGeojson = { type: 'FeatureCollection', features: [] };
    let refreshTimer;
    let searchTimer;

    const statusColors = {
        online: '#10b981',
        inactive: '#ef4444',
        offline: '#f59e0b',
        maintenance: '#8b5cf6',
    };

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const showMapMessage = (message) => {
        emptyState.hidden = false;
        emptyState.querySelector('strong').textContent = message;
        emptyState.querySelector('span').textContent = '';
    };

    if (!config.token) {
        showMapMessage(messages.tokenMissing || 'Missing Mapbox token.');
        return;
    }

    if (!window.mapboxgl) {
        showMapMessage(messages.mapUnavailable || 'Mapbox GL JS is not loaded.');
        return;
    }

    const statusColorExpression = [
        'match',
        ['get', 'status'],
        'online',
        statusColors.online,
        'inactive',
        statusColors.inactive,
        'offline',
        statusColors.offline,
        'maintenance',
        statusColors.maintenance,
        '#64748b',
    ];

    const updateCounters = (summary = {}) => {
        counters.forEach((counter) => {
            const key = counter.dataset.mapCount;
            counter.textContent = Number(summary[key] || 0).toLocaleString();
        });
    };

    const queryParams = () => {
        const params = new URLSearchParams();

        if (statusFilter.value) {
            params.set('status', statusFilter.value);
        }

        if (fleetFilter.value) {
            params.set('fleet_id', fleetFilter.value);
        }

        if (searchInput.value.trim()) {
            params.set('search', searchInput.value.trim());
        }

        return params.toString();
    };

    const fitToFeatures = () => {
        if (!latestGeojson.features.length || !map) {
            return;
        }

        const bounds = new mapboxgl.LngLatBounds();
        latestGeojson.features.forEach((feature) => bounds.extend(feature.geometry.coordinates));
        map.fitBounds(bounds, {
            padding: {
                top: 80,
                right: 80,
                bottom: 80,
                left: 430,
            },
            maxZoom: 15,
            duration: 700,
        });
    };

    const popupHtml = (properties) => `
        <div class="map-popup">
            <div class="map-popup-header">
                <span class="map-popup-dot status-${escapeHtml(properties.status)}"></span>
                <div>
                    <strong class="map-popup-title">${escapeHtml(properties.vehicle)}</strong>
                    <span class="map-popup-subtitle">${escapeHtml(properties.status_label)} · ${escapeHtml(properties.imei)}</span>
                </div>
            </div>
            <div class="map-popup-grid">
                <div class="map-popup-row">
                    <span>${escapeHtml(messages.registration)}</span>
                    <strong>${escapeHtml(properties.registration)}</strong>
                </div>
                <div class="map-popup-row">
                    <span>${escapeHtml(messages.tracker)}</span>
                    <strong>${escapeHtml(properties.brand)} · ${escapeHtml(properties.model)}</strong>
                </div>
                <div class="map-popup-row">
                    <span>${escapeHtml(messages.fleet)}</span>
                    <strong>${escapeHtml(properties.fleet)} · ${escapeHtml(properties.fleet_code)}</strong>
                </div>
                <div class="map-popup-row">
                    <span>${escapeHtml(messages.speed)}</span>
                    <strong>${Number(properties.speed || 0)} ${escapeHtml(messages.kmh)}</strong>
                </div>
                <div class="map-popup-row">
                    <span>${escapeHtml(messages.lastSignal)}</span>
                    <strong>${escapeHtml(properties.last_signal)}</strong>
                </div>
            </div>
            <div class="map-popup-actions">
                <button
                    type="button"
                    class="map-popup-action-button"
                    data-tracker-details
                    data-details-url="${escapeHtml(properties.details_url)}"
                >
                    <i class="fa-regular fa-clock"></i>
                    <span>${escapeHtml(messages.details)}</span>
                </button>
                <button
                    type="button"
                    class="map-popup-action-button"
                    data-trips-open
                    data-trips-url="${escapeHtml(properties.trips_url)}"
                    data-trips-name="${escapeHtml(properties.vehicle)}"
                >
                    <i class="fa-solid fa-route"></i>
                    <span>${escapeHtml(messages.trips)}</span>
                </button>
            </div>
        </div>
    `;

    const setMapData = (geojson) => {
        latestGeojson = geojson || { type: 'FeatureCollection', features: [] };
        const source = map.getSource('devices');

        if (source) {
            source.setData(latestGeojson);
        }

        emptyState.hidden = latestGeojson.features.length > 0;
    };

    const loadDevices = async ({ fit = false } = {}) => {
        const url = `${config.devicesUrl}${queryParams() ? `?${queryParams()}` : ''}`;
        shell.classList.add('is-loading');

        try {
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const payload = await response.json();
            updateCounters(payload.summary || {});
            setMapData(payload.geojson);
            lastUpdate.textContent = new Date().toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });

            if (fit) {
                fitToFeatures();
            }
        } catch (error) {
            console.error(error);
        } finally {
            shell.classList.remove('is-loading');
        }
    };

    const addLayers = () => {
        map.addSource('devices', {
            type: 'geojson',
            data: latestGeojson,
            cluster: true,
            clusterMaxZoom: 14,
            clusterRadius: 52,
        });

        map.addLayer({
            id: 'clusters',
            type: 'circle',
            source: 'devices',
            filter: ['has', 'point_count'],
            paint: {
                'circle-color': '#171064',
                'circle-radius': ['step', ['get', 'point_count'], 24, 10, 30, 30, 38],
                'circle-stroke-width': 4,
                'circle-stroke-color': 'rgba(255,255,255,0.88)',
            },
        });

        map.addLayer({
            id: 'cluster-count',
            type: 'symbol',
            source: 'devices',
            filter: ['has', 'point_count'],
            layout: {
                'text-field': ['get', 'point_count_abbreviated'],
                'text-font': ['Open Sans Bold', 'Arial Unicode MS Bold'],
                'text-size': 13,
            },
            paint: {
                'text-color': '#ffffff',
            },
        });

        map.addLayer({
            id: 'device-halo',
            type: 'circle',
            source: 'devices',
            filter: ['!', ['has', 'point_count']],
            paint: {
                'circle-radius': 15,
                'circle-color': statusColorExpression,
                'circle-opacity': 0.18,
            },
        });

        map.addLayer({
            id: 'devices',
            type: 'circle',
            source: 'devices',
            filter: ['!', ['has', 'point_count']],
            paint: {
                'circle-radius': 7,
                'circle-color': statusColorExpression,
                'circle-stroke-color': '#ffffff',
                'circle-stroke-width': 3,
            },
        });

        map.addLayer({
            id: 'device-labels',
            type: 'symbol',
            source: 'devices',
            filter: ['!', ['has', 'point_count']],
            layout: {
                'text-field': ['get', 'vehicle'],
                'text-font': ['Open Sans Semibold', 'Arial Unicode MS Bold'],
                'text-size': 11,
                'text-offset': [0, 1.55],
                'text-anchor': 'top',
                'text-allow-overlap': false,
            },
            paint: {
                'text-color': '#071225',
                'text-halo-color': '#ffffff',
                'text-halo-width': 1.2,
            },
        });

        map.addSource('trip-history', {
            type: 'geojson',
            data: { type: 'FeatureCollection', features: [] },
        });

        map.addLayer({
            id: 'trip-history-line',
            type: 'line',
            source: 'trip-history',
            paint: {
                'line-color': '#171064',
                'line-width': 5,
                'line-opacity': 0.86,
            },
        });

        map.on('click', 'clusters', (event) => {
            const features = map.queryRenderedFeatures(event.point, { layers: ['clusters'] });
            const clusterId = features[0].properties.cluster_id;

            map.getSource('devices').getClusterExpansionZoom(clusterId, (error, zoom) => {
                if (error) {
                    return;
                }

                map.easeTo({
                    center: features[0].geometry.coordinates,
                    zoom,
                });
            });
        });

        map.on('click', 'devices', (event) => {
            const feature = event.features[0];

            new mapboxgl.Popup({
                closeButton: true,
                closeOnClick: true,
                maxWidth: '320px',
            })
                .setLngLat(feature.geometry.coordinates)
                .setHTML(popupHtml(feature.properties))
                .addTo(map);
        });

        ['clusters', 'devices'].forEach((layer) => {
            map.on('mouseenter', layer, () => {
                map.getCanvas().style.cursor = 'pointer';
            });

            map.on('mouseleave', layer, () => {
                map.getCanvas().style.cursor = '';
            });
        });
    };

    const scheduleAutoRefresh = () => {
        clearInterval(refreshTimer);

        if (!autoInput.checked) {
            return;
        }

        refreshTimer = setInterval(() => loadDevices(), 10000);
    };

    mapboxgl.accessToken = config.token;
    map = new mapboxgl.Map({
        container: mapElement,
        style: 'mapbox://styles/mapbox/streets-v12',
        center: config.center || [15.312, -4.325],
        zoom: config.zoom || 11,
        pitch: 42,
        bearing: -12,
        attributionControl: false,
    });

    map.addControl(new mapboxgl.NavigationControl({ visualizePitch: true }), 'bottom-right');
    map.addControl(new mapboxgl.FullscreenControl(), 'bottom-right');
    map.addControl(new mapboxgl.AttributionControl({ compact: true }), 'bottom-left');

    map.on('load', () => {
        addLayers();
        loadDevices({ fit: true });
        scheduleAutoRefresh();
    });

    refreshButton.addEventListener('click', () => loadDevices());
    fitButton.addEventListener('click', fitToFeatures);
    autoInput.addEventListener('change', scheduleAutoRefresh);
    statusFilter.addEventListener('change', () => loadDevices({ fit: true }));
    fleetFilter.addEventListener('change', () => loadDevices({ fit: true }));
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadDevices({ fit: true }), 280);
    });

    document.addEventListener('exad:trips-loaded', (event) => {
        const source = map.getSource('trip-history');
        const geojson = event.detail?.geojson || { type: 'FeatureCollection', features: [] };

        if (!source) {
            return;
        }

        source.setData(geojson);

        const coordinates = geojson.features.flatMap((feature) => feature.geometry?.coordinates || []);
        if (!coordinates.length) {
            return;
        }

        const bounds = new mapboxgl.LngLatBounds();
        coordinates.forEach((coordinate) => bounds.extend(coordinate));
        map.fitBounds(bounds, {
            padding: {
                top: 70,
                right: 420,
                bottom: 70,
                left: 80,
            },
            maxZoom: 15,
            duration: 700,
        });
    });

    document.addEventListener('exad:trips-cleared', () => {
        map.getSource('trip-history')?.setData({ type: 'FeatureCollection', features: [] });
    });
})();
