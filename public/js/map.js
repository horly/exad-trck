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
    const showAllInput = document.querySelector('[data-map-show-all]');
    const resultsPanel = document.querySelector('[data-map-results]');
    const resultsList = document.querySelector('[data-map-results-list]');
    const resultsCount = document.querySelector('[data-map-results-count]');
    const panelToggle = document.querySelector('[data-map-panel-toggle]');
    const panelClose = document.querySelector('[data-map-panel-close]');
    const lastUpdate = document.querySelector('[data-map-last-update]');
    const counters = Array.from(document.querySelectorAll('[data-map-count]'));
    const messages = config.messages || {};

    let map;
    let serverGeojson = { type: 'FeatureCollection', features: [] };
    let latestGeojson = { type: 'FeatureCollection', features: [] };
    let selectedDeviceId = null;
    let selectedMarker = null;
    let refreshTimer;
    let searchTimer;

    const statusColors = {
        online: '#10b981',
        moving: '#229bd8',
        parking: '#22a7df',
        stationaryRunning: '#229bd8',
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
        'case',
        ['==', ['get', 'is_parking'], true],
        statusColors.parking,
        ['==', ['get', 'is_stationary_running'], true],
        statusColors.stationaryRunning,
        [
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
        ],
    ];

    const markerState = (properties) => {
        if (properties.is_moving) {
            return 'moving';
        }

        if (properties.is_parking) {
            return 'parking';
        }

        if (properties.is_stationary_running) {
            return 'stationary-running';
        }

        return properties.status || 'online';
    };

    const markerGlyph = (properties) => {
        if (properties.is_moving) {
            return '';
        }

        if (properties.is_parking) {
            return 'P';
        }

        return '';
    };

    const vehicleMarkerHtml = (properties, isSelected = false) => `
        <span
            class="map-vehicle-marker state-${escapeHtml(markerState(properties))}${isSelected ? ' is-selected' : ''}"
            style="--marker-angle: ${Number(properties.angle || 0)}deg"
        >
            <span class="map-vehicle-marker__icon">${escapeHtml(markerGlyph(properties))}</span>
            <span class="map-vehicle-marker__label">${escapeHtml(properties.vehicle)} ${escapeHtml(properties.registration)}</span>
        </span>
    `;

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
                <span class="map-popup-dot status-${escapeHtml(markerState(properties))}"></span>
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

    const openMapboxPopup = (feature) => {
        new mapboxgl.Popup({
            closeButton: true,
            closeOnClick: true,
            maxWidth: '320px',
        })
            .setLngLat(feature.geometry.coordinates)
            .setHTML(popupHtml(feature.properties))
            .addTo(map);
    };

    const displayedGeojson = () => {
        if (showAllInput.checked) {
            return serverGeojson;
        }

        if (selectedDeviceId === null) {
            return { type: 'FeatureCollection', features: [] };
        }

        return {
            type: 'FeatureCollection',
            features: serverGeojson.features.filter((feature) => String(feature.properties.id) === String(selectedDeviceId)),
        };
    };

    const renderSearchResults = (geojson) => {
        const hasSearch = searchInput.value.trim() !== '';

        resultsPanel.hidden = !hasSearch;
        resultsList.innerHTML = '';

        if (!hasSearch) {
            resultsCount.textContent = '0';
            return;
        }

        const features = geojson.features || [];
        resultsCount.textContent = String(features.length);

        if (!features.length) {
            resultsList.innerHTML = `<p class="map-result-empty">${escapeHtml(messages.noResults || 'No result found.')}</p>`;
            return;
        }

        features.slice(0, 12).forEach((feature) => {
            const properties = feature.properties;
            const item = document.createElement('button');
            item.type = 'button';
            item.className = `map-result-item${String(properties.id) === String(selectedDeviceId) ? ' is-selected' : ''}`;
            item.setAttribute('aria-label', `${messages.selectVehicle || 'Select vehicle'} ${properties.vehicle}`);
            item.innerHTML = `
                <span class="map-result-icon state-${escapeHtml(markerState(properties))}">${escapeHtml(markerGlyph(properties))}</span>
                <span class="map-result-body">
                    <strong class="map-result-title">${escapeHtml(properties.vehicle)} ${escapeHtml(properties.registration)}</strong>
                    <span class="map-result-meta">${escapeHtml(properties.imei)} · ${escapeHtml(properties.fleet)} · ${escapeHtml(properties.status_label)}</span>
                </span>
            `;
            item.addEventListener('click', () => {
                selectedDeviceId = properties.id;
                showAllInput.checked = false;
                renderCurrentMap({ fit: true });
            });
            resultsList.appendChild(item);
        });
    };

    const renderSelectedMarker = () => {
        if (selectedMarker) {
            selectedMarker.remove();
            selectedMarker = null;
        }

        const feature = latestGeojson.features.find((feature) => String(feature.properties.id) === String(selectedDeviceId));

        if (!feature) {
            return;
        }

        const element = document.createElement('div');
        element.className = 'mapbox-vehicle-marker';
        element.innerHTML = vehicleMarkerHtml(feature.properties, true);
        element.addEventListener('click', () => openMapboxPopup(feature));

        selectedMarker = new mapboxgl.Marker({
            element,
            anchor: 'center',
        })
            .setLngLat(feature.geometry.coordinates)
            .addTo(map);
    };

    const setMapData = (geojson) => {
        latestGeojson = geojson || { type: 'FeatureCollection', features: [] };
        const source = map.getSource('devices');
        const trailsSource = map.getSource('device-trails');

        if (source) {
            source.setData(latestGeojson);
        }

        if (trailsSource) {
            trailsSource.setData(buildTrailGeojson(latestGeojson));
        }

        renderSelectedMarker();

        const hasIntentionalDisplay = showAllInput.checked || selectedDeviceId !== null;
        emptyState.hidden = !hasIntentionalDisplay || latestGeojson.features.length > 0;
    };

    const renderCurrentMap = ({ fit = false } = {}) => {
        renderSearchResults(serverGeojson);
        setMapData(displayedGeojson());

        if (fit) {
            fitToFeatures();
        }
    };

    const buildTrailGeojson = (geojson) => ({
        type: 'FeatureCollection',
        features: (geojson.features || [])
            .filter((feature) => feature.properties?.is_moving && Array.isArray(feature.properties.trail) && feature.properties.trail.length > 1)
            .map((feature) => ({
                type: 'Feature',
                geometry: {
                    type: 'LineString',
                    coordinates: feature.properties.trail,
                },
                properties: {
                    id: feature.properties.id,
                },
            })),
    });

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
            serverGeojson = payload.geojson || { type: 'FeatureCollection', features: [] };
            renderCurrentMap({ fit });
            lastUpdate.textContent = new Date().toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
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

        map.addSource('device-trails', {
            type: 'geojson',
            data: buildTrailGeojson(latestGeojson),
            lineMetrics: true,
        });

        map.addLayer({
            id: 'device-trails',
            type: 'line',
            source: 'device-trails',
            layout: {
                'line-cap': 'round',
                'line-join': 'round',
            },
            paint: {
                'line-width': 5,
                'line-gradient': [
                    'interpolate',
                    ['linear'],
                    ['line-progress'],
                    0,
                    'rgba(34, 155, 216, 0.12)',
                    0.55,
                    'rgba(34, 155, 216, 0.45)',
                    1,
                    'rgba(34, 155, 216, 0.88)',
                ],
            },
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
            filter: [
                'all',
                ['!', ['has', 'point_count']],
                ['!=', ['get', 'is_stationary_running'], true],
                ['!=', ['get', 'is_moving'], true],
            ],
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
            filter: [
                'all',
                ['!', ['has', 'point_count']],
                ['!=', ['get', 'is_stationary_running'], true],
                ['!=', ['get', 'is_moving'], true],
            ],
            paint: {
                'circle-radius': 7,
                'circle-color': statusColorExpression,
                'circle-stroke-color': '#ffffff',
                'circle-stroke-width': 3,
            },
        });

        map.addLayer({
            id: 'device-parking-symbols',
            type: 'symbol',
            source: 'devices',
            filter: ['all', ['!', ['has', 'point_count']], ['==', ['get', 'is_parking'], true]],
            layout: {
                'text-field': 'P',
                'text-font': ['Open Sans Bold', 'Arial Unicode MS Bold'],
                'text-size': 12,
                'text-allow-overlap': true,
                'text-ignore-placement': true,
            },
            paint: {
                'text-color': '#ffffff',
            },
        });

        map.addLayer({
            id: 'device-stationary-symbols',
            type: 'symbol',
            source: 'devices',
            filter: ['all', ['!', ['has', 'point_count']], ['==', ['get', 'is_stationary_running'], true]],
            layout: {
                'text-field': '■',
                'text-font': ['Open Sans Bold', 'Arial Unicode MS Bold'],
                'text-size': 22,
                'text-allow-overlap': true,
                'text-ignore-placement': true,
            },
            paint: {
                'text-color': statusColors.stationaryRunning,
                'text-halo-color': '#ffffff',
                'text-halo-width': 2,
            },
        });

        map.addLayer({
            id: 'device-moving-arrows',
            type: 'symbol',
            source: 'devices',
            filter: ['all', ['!', ['has', 'point_count']], ['==', ['get', 'is_moving'], true]],
            layout: {
                'text-field': '▲',
                'text-font': ['Open Sans Bold', 'Arial Unicode MS Bold'],
                'text-size': 24,
                'text-rotate': ['get', 'angle'],
                'text-rotation-alignment': 'map',
                'text-allow-overlap': true,
                'text-ignore-placement': true,
            },
            paint: {
                'text-color': statusColors.moving,
                'text-halo-color': '#ffffff',
                'text-halo-width': 2,
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

        const openDevicePopup = (event) => {
            const feature = event.features[0];

            selectedDeviceId = feature.properties.id;
            renderCurrentMap();
            openMapboxPopup(feature);
        };

        map.on('click', 'devices', openDevicePopup);
        map.on('click', 'device-stationary-symbols', openDevicePopup);
        map.on('click', 'device-moving-arrows', openDevicePopup);

        ['clusters', 'devices', 'device-stationary-symbols', 'device-moving-arrows'].forEach((layer) => {
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
        loadDevices();
        scheduleAutoRefresh();
    });

    refreshButton.addEventListener('click', () => loadDevices());
    fitButton.addEventListener('click', fitToFeatures);
    autoInput.addEventListener('change', scheduleAutoRefresh);
    showAllInput.addEventListener('change', () => {
        if (!showAllInput.checked) {
            selectedDeviceId = null;
        }

        renderCurrentMap({ fit: showAllInput.checked });
    });
    panelToggle.addEventListener('click', () => shell.classList.remove('is-panel-collapsed'));
    panelClose.addEventListener('click', () => shell.classList.add('is-panel-collapsed'));
    statusFilter.addEventListener('change', () => {
        selectedDeviceId = null;
        loadDevices({ fit: showAllInput.checked });
    });
    fleetFilter.addEventListener('change', () => {
        selectedDeviceId = null;
        loadDevices({ fit: showAllInput.checked });
    });
    searchInput.addEventListener('input', () => {
        selectedDeviceId = null;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadDevices({ fit: showAllInput.checked }), 280);
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
