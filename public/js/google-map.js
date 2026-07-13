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
    let tripPolylines = new Map();
    let tripFeatures = new Map();
    let selectedTripIndex = null;
    let replayMarker = null;
    let replayFrame = null;
    let replayPlaying = false;
    let replaySpeed = 1;
    let replayElapsed = 0;
    let replayLastFrame = null;
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

    const dispatchReplayState = () => {
        document.dispatchEvent(new CustomEvent('exad:trip-replay-state', {
            detail: { playing: replayPlaying, index: selectedTripIndex },
        }));
    };

    const dispatchReplayProgress = (duration = 0) => {
        document.dispatchEvent(new CustomEvent('exad:trip-replay-progress', {
            detail: {
                elapsed: Math.min(replayElapsed, duration),
                duration,
                index: selectedTripIndex,
            },
        }));
    };

    const stopTripReplay = () => {
        if (replayFrame !== null) {
            cancelAnimationFrame(replayFrame);
            replayFrame = null;
        }

        replayPlaying = false;
        replayLastFrame = null;
        dispatchReplayState();
    };

    const clearTripHistory = () => {
        stopTripReplay();
        tripPolylines.forEach((polyline) => polyline.setMap(null));
        tripPolylines = new Map();
        tripFeatures = new Map();
        selectedTripIndex = null;
        replayElapsed = 0;

        if (replayMarker) {
            replayMarker.setMap(null);
            replayMarker = null;
        }
    };

    const tripPath = (feature) => (feature?.geometry?.coordinates || [])
        .map(coordinatesToLatLng)
        .filter((point) => Number.isFinite(point.lat) && Number.isFinite(point.lng));

    const distanceBetween = (from, to) => {
        const earthRadius = 6371000;
        const latitudeDelta = (to.lat - from.lat) * Math.PI / 180;
        const longitudeDelta = (to.lng - from.lng) * Math.PI / 180;
        const latitude1 = from.lat * Math.PI / 180;
        const latitude2 = to.lat * Math.PI / 180;
        const a = Math.sin(latitudeDelta / 2) ** 2
            + Math.cos(latitude1) * Math.cos(latitude2) * Math.sin(longitudeDelta / 2) ** 2;

        return earthRadius * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    };

    const headingBetween = (from, to) => {
        const latitude1 = from.lat * Math.PI / 180;
        const latitude2 = to.lat * Math.PI / 180;
        const longitudeDelta = (to.lng - from.lng) * Math.PI / 180;
        const y = Math.sin(longitudeDelta) * Math.cos(latitude2);
        const x = Math.cos(latitude1) * Math.sin(latitude2)
            - Math.sin(latitude1) * Math.cos(latitude2) * Math.cos(longitudeDelta);

        return (Math.atan2(y, x) * 180 / Math.PI + 360) % 360;
    };

    const positionAlongPath = (path, ratio) => {
        if (path.length < 2) {
            return { position: path[0] || null, heading: 0 };
        }

        const distances = [];
        let totalDistance = 0;

        for (let index = 1; index < path.length; index += 1) {
            const distance = distanceBetween(path[index - 1], path[index]);
            distances.push(distance);
            totalDistance += distance;
        }

        const targetDistance = totalDistance * Math.min(1, Math.max(0, ratio));
        let traversedDistance = 0;

        for (let index = 0; index < distances.length; index += 1) {
            const segmentDistance = distances[index];

            if (traversedDistance + segmentDistance >= targetDistance || index === distances.length - 1) {
                const segmentRatio = segmentDistance > 0
                    ? (targetDistance - traversedDistance) / segmentDistance
                    : 0;
                const from = path[index];
                const to = path[index + 1];

                return {
                    position: {
                        lat: from.lat + ((to.lat - from.lat) * segmentRatio),
                        lng: from.lng + ((to.lng - from.lng) * segmentRatio),
                    },
                    heading: headingBetween(from, to),
                };
            }

            traversedDistance += segmentDistance;
        }

        return { position: path[path.length - 1], heading: 0 };
    };

    const updateReplayMarker = (feature, elapsed) => {
        const path = tripPath(feature);
        const duration = Math.max(1, Number(feature?.properties?.duration_seconds) || 1);
        const progress = positionAlongPath(path, elapsed / duration);

        if (!progress.position) {
            return;
        }

        const color = feature?.properties?.color || '#2563eb';
        const icon = {
            path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
            fillColor: color,
            fillOpacity: 1,
            strokeColor: '#ffffff',
            strokeOpacity: 1,
            strokeWeight: 2,
            scale: 7,
            rotation: progress.heading,
        };

        if (!replayMarker) {
            replayMarker = new google.maps.Marker({
                map,
                position: progress.position,
                icon,
                zIndex: 5000,
                title: messages.replay || 'Replay',
            });
        } else {
            replayMarker.setMap(map);
            replayMarker.setPosition(progress.position);
            replayMarker.setIcon(icon);
        }
    };

    const styleTripPolylines = () => {
        tripPolylines.forEach((polyline, index) => {
            const selected = Number(index) === Number(selectedTripIndex);
            const feature = tripFeatures.get(Number(index));

            polyline.setOptions({
                strokeColor: feature?.properties?.color || '#2563eb',
                strokeOpacity: selected ? 0.96 : 0.32,
                strokeWeight: selected ? 6 : 3,
                zIndex: selected ? 30 : 10,
            });
        });
    };

    const fitTripPath = (path) => {
        if (!path.length) {
            return;
        }

        const bounds = new google.maps.LatLngBounds();
        path.forEach((point) => bounds.extend(point));
        map.fitBounds(bounds, 100);
        google.maps.event.addListenerOnce(map, 'idle', () => {
            if (map.getZoom() > 16) {
                map.setZoom(16);
            }
        });
    };

    const selectTrip = (index, { fit = true } = {}) => {
        const numericIndex = Number(index);
        const feature = tripFeatures.get(numericIndex);

        if (!feature) {
            return;
        }

        stopTripReplay();
        selectedTripIndex = numericIndex;
        replayElapsed = 0;
        styleTripPolylines();
        updateReplayMarker(feature, 0);
        dispatchReplayProgress(Number(feature.properties?.duration_seconds) || 0);

        if (fit) {
            fitTripPath(tripPath(feature));
        }
    };

    const replayTick = (timestamp) => {
        const feature = tripFeatures.get(Number(selectedTripIndex));

        if (!replayPlaying || !feature) {
            return;
        }

        const duration = Math.max(1, Number(feature.properties?.duration_seconds) || 1);

        if (replayLastFrame !== null) {
            replayElapsed += ((timestamp - replayLastFrame) / 1000) * replaySpeed;
        }

        replayLastFrame = timestamp;

        if (replayElapsed >= duration) {
            replayElapsed = duration;
            updateReplayMarker(feature, replayElapsed);
            dispatchReplayProgress(duration);
            stopTripReplay();
            return;
        }

        updateReplayMarker(feature, replayElapsed);
        dispatchReplayProgress(duration);
        replayFrame = requestAnimationFrame(replayTick);
    };

    const toggleTripReplay = (index) => {
        if (Number(index) !== Number(selectedTripIndex)) {
            selectTrip(index);
        }

        const feature = tripFeatures.get(Number(selectedTripIndex));

        if (!feature) {
            return;
        }

        if (replayPlaying) {
            stopTripReplay();
            return;
        }

        const duration = Math.max(1, Number(feature.properties?.duration_seconds) || 1);
        if (replayElapsed >= duration) {
            replayElapsed = 0;
        }

        replayPlaying = true;
        replayLastFrame = null;
        dispatchReplayState();
        replayFrame = requestAnimationFrame(replayTick);
    };

    const drawTripHistory = (geojson) => {
        clearTripHistory();
        const bounds = new google.maps.LatLngBounds();

        (geojson.features || []).forEach((feature) => {
            const index = Number(feature.properties?.index);
            const path = tripPath(feature);

            if (!Number.isFinite(index) || path.length < 2) {
                return;
            }

            tripFeatures.set(index, feature);
            path.forEach((point) => bounds.extend(point));

            const polyline = new google.maps.Polyline({
                map,
                path,
                geodesic: true,
                strokeColor: feature.properties?.color || '#2563eb',
                strokeOpacity: 0.72,
                strokeWeight: 4,
                zIndex: 10,
            });

            polyline.addListener('click', () => {
                selectTrip(index, { fit: false });
                document.dispatchEvent(new CustomEvent('exad:trip-map-selected', {
                    detail: { index },
                }));
            });
            tripPolylines.set(index, polyline);
        });

        if (tripFeatures.size === 0) {
            return;
        }

        map.fitBounds(bounds, 100);
        selectTrip(tripFeatures.keys().next().value, { fit: false });
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
        window.exadTripReplayAvailable = true;
        document.dispatchEvent(new CustomEvent('exad:trip-replay-ready'));

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
        clearTripHistory();
    });

    document.addEventListener('exad:trip-selected', (event) => {
        selectTrip(event.detail?.index, { fit: event.detail?.fit !== false });
    });

    document.addEventListener('exad:trip-color-changed', (event) => {
        const index = Number(event.detail?.index);
        const feature = tripFeatures.get(index);

        if (!feature) {
            return;
        }

        feature.properties.color = event.detail?.color || feature.properties.color;
        styleTripPolylines();

        if (index === Number(selectedTripIndex)) {
            updateReplayMarker(feature, replayElapsed);
        }
    });

    document.addEventListener('exad:trip-replay-toggle', (event) => {
        toggleTripReplay(event.detail?.index);
    });

    document.addEventListener('exad:trip-replay-reset', (event) => {
        selectTrip(event.detail?.index, { fit: false });
    });

    document.addEventListener('exad:trip-replay-speed', (event) => {
        replaySpeed = Math.min(300, Math.max(1, Number(event.detail?.speed) || 1));
    });

    document.addEventListener('exad:trip-replay-seek', (event) => {
        const index = Number(event.detail?.index);

        if (index !== Number(selectedTripIndex)) {
            selectTrip(index, { fit: false });
        }

        const feature = tripFeatures.get(Number(selectedTripIndex));
        if (!feature) {
            return;
        }

        const duration = Math.max(1, Number(feature.properties?.duration_seconds) || 1);
        replayElapsed = duration * Math.min(1, Math.max(0, Number(event.detail?.ratio) || 0));
        updateReplayMarker(feature, replayElapsed);
        dispatchReplayProgress(duration);
    });

    if (window.google?.maps) {
        window.initExadGoogleMap();
    }
})();
