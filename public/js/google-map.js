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
    let infoWindow;
    let refreshTimer;
    let searchTimer;
    let markerRegistry = new Map();
    let trailPolylines = [];
    let tripPolyline = null;
    let selectedDeviceId = null;
    let serverGeojson = { type: 'FeatureCollection', features: [] };
    let latestGeojson = { type: 'FeatureCollection', features: [] };
    let VehicleOverlay;
    const MARKER_ANIMATION_MS = 5000;
    const SELECTED_DEVICE_ZOOM = 17;

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

    const normalizeSearchValue = (value) => {
        const textValue = String(value || '')
            .replace(/\s+/g, ' ')
            .trim();

        if (!textValue) {
            return '';
        }

        const parts = textValue.split(' ');
        const compactedLetters = parts.length > 1 && parts.every((part) => part.length === 1)
            ? parts.join('')
            : textValue;

        return compactedLetters
            .toLocaleLowerCase('fr-FR')
            .split(/([\s'-]+)/)
            .map((part) => {
                if (!part || /^[\s'-]+$/.test(part)) {
                    return part;
                }

                return part.charAt(0).toLocaleUpperCase('fr-FR') + part.slice(1);
            })
            .join('');
    };

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

        const searchValue = normalizeSearchValue(searchInput.value);

        if (searchValue) {
            params.set('search', searchValue);
        }

        return params.toString();
    };

    const hydrateFiltersFromUrl = () => {
        const params = new URLSearchParams(window.location.search);

        if (params.has('status')) {
            statusFilter.value = params.get('status') || '';
        }

        if (params.has('fleet_id')) {
            fleetFilter.value = params.get('fleet_id') || '';
        }

        if (params.has('search')) {
            searchInput.value = normalizeSearchValue(params.get('search') || '');
        }

        if (params.get('show') === '1' || searchInput.value.trim() !== '') {
            showAllInput.checked = true;
        }
    };

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

        if (properties.is_stationary_running) {
            return '';
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

    const popupHtml = (properties) => `
        <div class="map-popup">
            <div class="map-popup-header">
                <span class="map-popup-dot status-${escapeHtml(properties.status || markerState(properties))}"></span>
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
        markerRegistry.forEach((marker) => marker.setMap(null));
        markerRegistry = new Map();
        trailPolylines.forEach((polyline) => polyline.setMap(null));
        trailPolylines = [];
    };

    const clearTrails = () => {
        trailPolylines.forEach((polyline) => polyline.setMap(null));
        trailPolylines = [];
    };

    const sameLatLng = (first, second) => Math.abs(first.lat() - second.lat()) < 0.0000001
        && Math.abs(first.lng() - second.lng()) < 0.0000001;

    const easeInOut = (progress) => progress < 0.5
        ? 2 * progress * progress
        : 1 - Math.pow(-2 * progress + 2, 2) / 2;

    const keepPositionVisible = (position) => {
        if (!map?.getBounds()?.contains(position)) {
            map.panTo(position);
        }
    };

    const coordinatePathToLatLng = (coordinates = []) => coordinates
        .filter((coordinate) => Array.isArray(coordinate) && coordinate.length >= 2)
        .map((coordinate) => {
            const latLng = coordinatesToLatLng(coordinate);

            return new google.maps.LatLng(latLng.lat, latLng.lng);
        });

    const latLngDistance = (first, second) => Math.hypot(
        first.lat() - second.lat(),
        first.lng() - second.lng(),
    );

    const pushUniquePoint = (path, point) => {
        if (!point) {
            return;
        }

        if (!path.length || !sameLatLng(path[path.length - 1], point)) {
            path.push(point);
        }
    };

    const closestPathSegment = (path, position) => {
        if (!Array.isArray(path) || path.length < 2 || !position) {
            return { startIndex: 0, nextIndex: 1 };
        }

        const targetLat = position.lat();
        const targetLng = position.lng();

        return path.slice(1).reduce((closest, point, index) => {
            const previous = path[index];
            const deltaLat = point.lat() - previous.lat();
            const deltaLng = point.lng() - previous.lng();
            const segmentLength = (deltaLat * deltaLat) + (deltaLng * deltaLng);
            const projection = segmentLength === 0
                ? 0
                : (((targetLat - previous.lat()) * deltaLat) + ((targetLng - previous.lng()) * deltaLng)) / segmentLength;
            const clampedProjection = Math.min(Math.max(projection, 0), 1);
            const projectedLat = previous.lat() + (deltaLat * clampedProjection);
            const projectedLng = previous.lng() + (deltaLng * clampedProjection);
            const distance = Math.hypot(projectedLat - targetLat, projectedLng - targetLng);

            if (distance < closest.distance) {
                return {
                    startIndex: index,
                    nextIndex: index + 1,
                    distance,
                };
            }

            return closest;
        }, { startIndex: 0, nextIndex: 1, distance: Number.POSITIVE_INFINITY });
    };

    const interpolatePath = (path, progress) => {
        if (!Array.isArray(path) || path.length < 2) {
            return {
                position: path?.[0] || null,
                passedPoints: path ? [...path] : [],
            };
        }

        const distances = path.slice(1).map((point, index) => latLngDistance(path[index], point));
        const totalDistance = distances.reduce((total, distance) => total + distance, 0);

        if (totalDistance === 0) {
            return {
                position: path[path.length - 1],
                passedPoints: [...path],
            };
        }

        const targetDistance = totalDistance * Math.min(Math.max(progress, 0), 1);
        let traversedDistance = 0;
        const passedPoints = [path[0]];

        for (let index = 1; index < path.length; index += 1) {
            const segmentDistance = distances[index - 1];

            if (traversedDistance + segmentDistance < targetDistance) {
                passedPoints.push(path[index]);
                traversedDistance += segmentDistance;
                continue;
            }

            const segmentProgress = segmentDistance === 0
                ? 1
                : (targetDistance - traversedDistance) / segmentDistance;
            const previous = path[index - 1];
            const next = path[index];
            const lat = previous.lat() + ((next.lat() - previous.lat()) * segmentProgress);
            const lng = previous.lng() + ((next.lng() - previous.lng()) * segmentProgress);

            return {
                position: new google.maps.LatLng(lat, lng),
                passedPoints,
            };
        }

        return {
            position: path[path.length - 1],
            passedPoints: path.slice(0, -1),
        };
    };

    const movementTrailContext = (coordinates = [], currentPosition, targetPosition, hasExistingMarker) => {
        const serverPath = coordinatePathToLatLng(coordinates);

        if (serverPath.length < 2) {
            return null;
        }

        if (!hasExistingMarker) {
            return {
                basePath: serverPath,
                animationPath: null,
            };
        }

        const segment = closestPathSegment(serverPath, currentPosition);
        const basePath = serverPath.slice(0, Math.max(segment.startIndex + 1, 1));
        pushUniquePoint(basePath, currentPosition);

        const animationPath = [currentPosition];
        serverPath.slice(Math.max(segment.nextIndex, 1)).forEach((point) => pushUniquePoint(animationPath, point));
        pushUniquePoint(animationPath, targetPosition);

        return {
            basePath,
            animationPath,
        };
    };

    const progressiveTrailPath = (basePath, animationPath, progress) => {
        if (!animationPath || animationPath.length < 2) {
            return basePath;
        }

        const interpolated = interpolatePath(animationPath, progress);
        const path = [...basePath];

        (interpolated.passedPoints || []).slice(1).forEach((point) => pushUniquePoint(path, point));
        if (interpolated.position && !sameLatLng(path[path.length - 1], interpolated.position)) {
            path.push(interpolated.position);
        }

        return path;
    };

    const drawMovementTrail = (path = []) => {
        if (!Array.isArray(path) || path.length < 2) {
            return null;
        }

        const polyline = new google.maps.Polyline({
            map,
            path,
            geodesic: true,
            strokeColor: '#229bd8',
            strokeOpacity: 0.48,
            strokeWeight: 4,
        });

        trailPolylines.push(polyline);

        return polyline;
    };

    const defineVehicleOverlay = () => {
        VehicleOverlay = class extends google.maps.OverlayView {
            constructor(position, properties, isSelected, onClick) {
                super();
                this.position = position;
                this.properties = properties;
                this.isSelected = isSelected;
                this.onClick = onClick;
                this.element = null;
                this.animationFrame = null;
            }

            onAdd() {
                this.element = document.createElement('div');
                this.element.className = 'map-google-vehicle-marker';
                this.element.innerHTML = vehicleMarkerHtml(this.properties, this.isSelected);
                this.element.addEventListener('click', this.onClick);
                this.getPanes().overlayMouseTarget.appendChild(this.element);
            }

            draw() {
                if (!this.element) {
                    return;
                }

                const point = this.getProjection().fromLatLngToDivPixel(this.position);
                this.element.style.left = `${point.x}px`;
                this.element.style.top = `${point.y}px`;
            }

            refresh(properties, isSelected, onClick) {
                this.properties = properties;
                this.isSelected = isSelected;
                this.element?.removeEventListener('click', this.onClick);
                this.onClick = onClick;
                if (this.element) {
                    this.element.innerHTML = vehicleMarkerHtml(this.properties, this.isSelected);
                    this.element.addEventListener('click', this.onClick);
                }
            }

            setPosition(position, animate = false, onFrame = null, animationPath = null) {
                if (this.animationFrame) {
                    cancelAnimationFrame(this.animationFrame);
                    this.animationFrame = null;
                }

                if (!animate || !this.element || sameLatLng(this.position, position)) {
                    this.position = position;
                    this.draw();
                    onFrame?.(position);
                    return;
                }

                const start = this.position;
                const startedAt = performance.now();
                const path = Array.isArray(animationPath) && animationPath.length > 1
                    ? animationPath
                    : [start, position];

                const step = (now) => {
                    const progress = Math.min((now - startedAt) / MARKER_ANIMATION_MS, 1);
                    const eased = easeInOut(progress);
                    const interpolated = interpolatePath(path, eased);

                    this.position = interpolated.position || position;
                    this.draw();
                    onFrame?.(this.position, eased);

                    if (progress < 1) {
                        this.animationFrame = requestAnimationFrame(step);
                        return;
                    }

                    this.animationFrame = null;
                };

                this.animationFrame = requestAnimationFrame(step);
            }

            onRemove() {
                if (this.animationFrame) {
                    cancelAnimationFrame(this.animationFrame);
                    this.animationFrame = null;
                }

                if (this.element) {
                    this.element.removeEventListener('click', this.onClick);
                    this.element.remove();
                    this.element = null;
                }
            }
        };
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
                renderMarkers(displayedGeojson());
                renderSearchResults(serverGeojson);
                fitToFeatures();
            });
            resultsList.appendChild(item);
        });
    };

    const fitToFeatures = () => {
        if (!latestGeojson.features.length || !map) {
            return;
        }

        if (latestGeojson.features.length === 1) {
            const position = coordinatesToLatLng(latestGeojson.features[0].geometry.coordinates);
            map.panTo(position);
            map.setZoom(SELECTED_DEVICE_ZOOM);
            return;
        }

        const bounds = new google.maps.LatLngBounds();
        latestGeojson.features.forEach((feature) => bounds.extend(coordinatesToLatLng(feature.geometry.coordinates)));
        map.fitBounds(bounds, 80);
    };

    const renderMarkers = (geojson) => {
        clearTrails();
        latestGeojson = geojson || { type: 'FeatureCollection', features: [] };
        const visibleIds = new Set(latestGeojson.features.map((feature) => String(feature.properties.id)));

        markerRegistry.forEach((marker, id) => {
            if (!visibleIds.has(id)) {
                marker.setMap(null);
                markerRegistry.delete(id);
            }
        });

        latestGeojson.features.forEach((feature) => {
            const id = String(feature.properties.id);
            const latLng = coordinatesToLatLng(feature.geometry.coordinates);
            const position = new google.maps.LatLng(latLng.lat, latLng.lng);
            const isSelected = id === String(selectedDeviceId);
            const marker = markerRegistry.get(id);
            const trailContext = feature.properties.is_moving
                ? movementTrailContext(feature.properties.trail, marker?.position || position, position, Boolean(marker))
                : null;
            const trailPolyline = trailContext ? drawMovementTrail(trailContext.basePath) : null;
            const onClick = () => {
                selectedDeviceId = feature.properties.id;
                renderMarkers(displayedGeojson());
                renderSearchResults(serverGeojson);
                infoWindow.setContent(popupHtml(feature.properties));
                infoWindow.setPosition(markerRegistry.get(id)?.position || position);
                infoWindow.open({ map });
                keepPositionVisible(markerRegistry.get(id)?.position || position);
            };
            const onFrame = (currentPosition, progress = 1) => {
                if (feature.properties.is_moving && trailPolyline && trailContext) {
                    trailPolyline.setPath(progressiveTrailPath(
                        trailContext.basePath,
                        trailContext.animationPath,
                        progress,
                    ));
                }

                if (isSelected) {
                    keepPositionVisible(currentPosition);

                    if (infoWindow.getMap()) {
                        infoWindow.setPosition(currentPosition);
                    }
                }
            };

            if (marker) {
                marker.refresh(feature.properties, isSelected, onClick);
                marker.setPosition(position, feature.properties.is_moving, onFrame, trailContext?.animationPath || null);
                return;
            }

            const vehicleMarker = new VehicleOverlay(position, feature.properties, isSelected, onClick);
            vehicleMarker.setMap(map);
            markerRegistry.set(id, vehicleMarker);

            if (isSelected) {
                keepPositionVisible(position);
            }
        });

        const hasIntentionalDisplay = showAllInput.checked || selectedDeviceId !== null;
        emptyState.hidden = !hasIntentionalDisplay || latestGeojson.features.length > 0;
    };

    const renderCurrentMap = ({ fit = false } = {}) => {
        renderSearchResults(serverGeojson);
        renderMarkers(displayedGeojson());

        if (fit) {
            fitToFeatures();
        }
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

    hydrateFiltersFromUrl();

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
        defineVehicleOverlay();
        infoWindow = new google.maps.InfoWindow({ maxWidth: 340 });

        loadDevices({ fit: showAllInput.checked });
        scheduleAutoRefresh();
    };

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
