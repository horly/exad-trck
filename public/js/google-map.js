(() => {
    const config = window.exadMapConfig || {};
    const shell = document.querySelector('[data-map-shell]');
    const mapElement = document.getElementById('trackingMap');

    if (!shell || !mapElement || config.provider !== 'google') {
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
    let infoWindow;
    let refreshTimer;
    let searchTimer;
    let markers = [];
    let tripPolyline = null;
    let latestGeojson = { type: 'FeatureCollection', features: [] };

    const statusColors = {
        online: '#10b981',
        parking: '#22a7df',
        stationaryRunning: '#f59e0b',
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

    const coordinatesToLatLng = (coordinates) => ({
        lat: Number(coordinates?.[1] || 0),
        lng: Number(coordinates?.[0] || 0),
    });

    const showMapMessage = (message) => {
        emptyState.hidden = false;
        emptyState.querySelector('strong').textContent = message;
        emptyState.querySelector('span').textContent = '';
    };

    if (!config.googleApiKey) {
        showMapMessage(messages.googleKeyMissing || 'Missing Google Maps API key.');
        return;
    }

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

    const markerIcon = (properties) => {
        const isStationaryRunning = properties.is_stationary_running;

        return {
            path: isStationaryRunning ? 'M -8 -8 L 8 -8 L 8 8 L -8 8 Z' : google.maps.SymbolPath.CIRCLE,
            fillColor: properties.is_parking
                ? statusColors.parking
                : (isStationaryRunning ? statusColors.stationaryRunning : (statusColors[properties.status] || '#64748b')),
            fillOpacity: 1,
            strokeColor: '#ffffff',
            strokeWeight: 3,
            scale: isStationaryRunning ? 1 : (properties.is_parking ? 13 : 9),
        };
    };

    const popupHtml = (properties) => `
        <div class="map-popup">
            <div class="map-popup-header">
                <span class="map-popup-dot status-${escapeHtml(properties.is_parking ? 'parking' : (properties.is_stationary_running ? 'stationary-running' : properties.status))}"></span>
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

    const clearMarkers = () => {
        markers.forEach((marker) => marker.setMap(null));
        markers = [];
    };

    const fitToFeatures = () => {
        if (!latestGeojson.features.length || !map) {
            return;
        }

        const bounds = new google.maps.LatLngBounds();
        latestGeojson.features.forEach((feature) => bounds.extend(coordinatesToLatLng(feature.geometry.coordinates)));
        map.fitBounds(bounds, 80);
    };

    const renderMarkers = (geojson) => {
        clearMarkers();
        latestGeojson = geojson || { type: 'FeatureCollection', features: [] };

        latestGeojson.features.forEach((feature) => {
            const marker = new google.maps.Marker({
                map,
                position: coordinatesToLatLng(feature.geometry.coordinates),
                title: feature.properties.vehicle,
                icon: markerIcon(feature.properties),
                label: feature.properties.is_parking ? {
                    text: 'P',
                    color: '#ffffff',
                    fontWeight: '900',
                    fontSize: '13px',
                } : undefined,
                optimized: true,
            });

            marker.addListener('click', () => {
                infoWindow.setContent(popupHtml(feature.properties));
                infoWindow.open({ map, anchor: marker });
            });

            markers.push(marker);
        });

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
                cache: 'no-store',
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const payload = await response.json();
            updateCounters(payload.summary || {});
            renderMarkers(payload.geojson);
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

    const scheduleAutoRefresh = () => {
        clearInterval(refreshTimer);

        if (!autoInput.checked) {
            return;
        }

        refreshTimer = setInterval(() => loadDevices(), 10000);
    };

    const drawTripHistory = (geojson) => {
        if (tripPolyline) {
            tripPolyline.setMap(null);
            tripPolyline = null;
        }

        const coordinates = (geojson.features || []).flatMap((feature) => feature.geometry?.coordinates || []);

        if (!coordinates.length) {
            return;
        }

        const path = coordinates.map(coordinatesToLatLng);
        tripPolyline = new google.maps.Polyline({
            map,
            path,
            geodesic: true,
            strokeColor: '#171064',
            strokeOpacity: 0.88,
            strokeWeight: 5,
        });

        const bounds = new google.maps.LatLngBounds();
        path.forEach((point) => bounds.extend(point));
        map.fitBounds(bounds, 80);
    };

    window.initExadGoogleMap = () => {
        if (!window.google?.maps) {
            showMapMessage(messages.googleUnavailable || 'Google Maps JavaScript API is not loaded.');
            return;
        }

        const center = coordinatesToLatLng(config.center || [15.312, -4.325]);
        map = new google.maps.Map(mapElement, {
            center,
            zoom: config.zoom || 11,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            mapTypeControl: true,
            streetViewControl: true,
            fullscreenControl: true,
            zoomControl: true,
            scaleControl: true,
            clickableIcons: true,
            gestureHandling: 'greedy',
        });
        infoWindow = new google.maps.InfoWindow({ maxWidth: 340 });

        loadDevices({ fit: true });
        scheduleAutoRefresh();
    };

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
        drawTripHistory(event.detail?.geojson || { type: 'FeatureCollection', features: [] });
    });

    document.addEventListener('exad:trips-cleared', () => {
        if (tripPolyline) {
            tripPolyline.setMap(null);
            tripPolyline = null;
        }
    });

    if (window.google?.maps) {
        window.initExadGoogleMap();
    }
})();
