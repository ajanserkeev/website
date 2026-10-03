{{-- Leaflet and the Alpine components of the admin maps (route builder, location picker). Loaded once per page. --}}
@once
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <style>
        .tu-map { height: 480px; border-radius: 0.75rem; overflow: hidden; border: 1px solid var(--gray-200); z-index: 0; }
        .dark .tu-map { border-color: var(--gray-700); }
        .tu-map-small { height: 340px; }
        .tu-toolbar { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; margin-bottom: 0.75rem; }
        .tu-hint { font-size: 0.8125rem; color: var(--gray-500); margin-top: 0.5rem; }
        .tu-error { font-size: 0.875rem; color: var(--danger-600); margin: 0.5rem 0; }
        .tu-stats { display: flex; flex-wrap: wrap; gap: 1.25rem; margin: 0.75rem 0; font-size: 0.875rem; }
        .tu-stats b { display: block; font-size: 1.125rem; }
        .tu-list { margin-top: 0.75rem; display: grid; gap: 0.375rem; }
        .tu-row { display: grid; grid-template-columns: 2rem 1fr auto; gap: 0.5rem; align-items: center; }
        .tu-seg { display: flex; align-items: center; gap: 0.5rem; padding-left: 2.5rem; font-size: 0.8125rem; color: var(--gray-500); }
        .tu-num { display: inline-flex; width: 1.75rem; height: 1.75rem; border-radius: 9999px; align-items: center; justify-content: center;
                  background: #2d5d8c; color: #fff; font-weight: 700; font-size: 0.8125rem; border: 2px solid #fff; box-shadow: 0 1px 4px rgba(0,0,0,.4); }
        .tu-pin { background: transparent; border: 0; }
        .tu-profile { width: 100%; height: 90px; margin-top: 0.5rem; }
        .tu-legend { display: inline-flex; align-items: center; gap: 0.375rem; font-size: 0.8125rem; }
        .tu-legend i { display: inline-block; width: 1.25rem; height: 0.25rem; border-radius: 2px; }
    </style>
    <script>
        (() => {
            const MODE_STYLE = {
                drive: { color: '#2d5d8c', label: 'Авто' },
                hike: { color: '#11804a', label: 'Пешком' },
                horse: { color: '#b07d00', label: 'Верхом' },
                line: { color: '#c4122f', label: 'По прямой' },
            };

            const whenLeaflet = (callback) => {
                if (window.L) return callback();
                const timer = setInterval(() => { if (window.L) { clearInterval(timer); callback(); } }, 50);
            };

            /**
             * Base map with relief, satellite and OSM layers; Kyrgyzstan in view.
             * onFirstShow runs once the map gets a size: maps in a hidden tab cannot fit bounds before that.
             */
            const createMap = (element, onFirstShow = () => {}) => {
                const map = L.map(element, { zoomControl: true, worldCopyJump: false }).setView([41.45, 74.8], 7);
                const layers = {
                    'Рельеф': L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
                        maxZoom: 17, attribution: '© OpenStreetMap, SRTM | © OpenTopoMap (CC-BY-SA)' }),
                    'Спутник': L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                        maxZoom: 18, attribution: 'Tiles © Esri' }),
                    'OSM': L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19, attribution: '© OpenStreetMap contributors' }),
                };
                layers['Рельеф'].addTo(map);
                L.control.layers(layers, {}, { position: 'topright' }).addTo(map);
                L.control.scale({ imperial: false }).addTo(map);
                let shown = false;
                const show = () => {
                    if (!element.clientWidth) return;
                    map.invalidateSize();
                    if (!shown) { shown = true; onFirstShow(); }
                };
                new ResizeObserver(show).observe(element);
                setTimeout(show);
                return map;
            };

            const pin = (label, color = '#2d5d8c') => L.divIcon({
                className: 'tu-pin',
                html: `<span class="tu-num" style="background:${color}">${label}</span>`,
                iconSize: [28, 28],
                iconAnchor: [14, 14],
            });

            const round = (n) => Math.round(n * 1e6) / 1e6;

            /** Heights of a point from Open-Meteo (Copernicus DEM), null when unavailable. */
            const altitudeOf = async (lat, lng) => {
                try {
                    const r = await fetch(`https://api.open-meteo.com/v1/elevation?latitude=${lat}&longitude=${lng}`);
                    const body = await r.json();
                    return Array.isArray(body.elevation) ? Math.round(body.elevation[0]) : null;
                } catch { return null; }
            };

            window.tuRouteBuilder = ({ state, places }) => {
                // Leaflet objects stay outside Alpine's reactive proxies.
                const ctx = { map: null, route: null, markers: null, timer: null };

                return {
                    state,
                    places,
                    modeStyle: MODE_STYLE,
                    waypoints: [],
                    modes: [],
                    newMode: 'drive',
                    busy: false,
                    error: null,

                    init() {
                        this.loadFromState();
                        whenLeaflet(() => this.setupMap());
                    },

                    loadFromState() {
                        const props = this.state?.properties ?? {};
                        this.waypoints = (props.waypoints ?? []).map((w) => ({ lat: +w.lat, lng: +w.lng, name: w.name ?? '' }));
                        this.modes = (this.state?.features ?? []).map((f) => f.properties?.mode ?? 'drive');
                        if (this.modes.length !== Math.max(0, this.waypoints.length - 1)) {
                            this.modes = this.waypoints.slice(1).map(() => 'drive');
                        }
                    },

                    setupMap() {
                        ctx.map = createMap(this.$refs.map, () => this.draw(true));
                        const placeLayer = L.layerGroup().addTo(ctx.map);
                        this.places.forEach((p) => {
                            L.circleMarker([p.lat, p.lng], { radius: 6, color: '#fff', weight: 2, fillColor: '#536074', fillOpacity: 0.9 })
                                .bindTooltip(`${p.name} — клик добавит в маршрут`)
                                .on('click', (e) => { L.DomEvent.stop(e); this.add(p.lat, p.lng, p.name); })
                                .addTo(placeLayer);
                        });
                        ctx.route = L.layerGroup().addTo(ctx.map);
                        ctx.markers = L.layerGroup().addTo(ctx.map);
                        ctx.map.on('click', (e) => this.add(e.latlng.lat, e.latlng.lng, ''));
                    },

                    add(lat, lng, name) {
                        this.waypoints.push({ lat: round(lat), lng: round(lng), name });
                        if (this.waypoints.length > 1) this.modes.push(this.newMode);
                        this.changed();
                    },

                    remove(i) {
                        this.waypoints.splice(i, 1);
                        this.modes.splice(i === 0 ? 0 : i - 1, 1);
                        this.changed();
                    },

                    move(i, delta) {
                        const j = i + delta;
                        if (j < 0 || j >= this.waypoints.length) return;
                        [this.waypoints[i], this.waypoints[j]] = [this.waypoints[j], this.waypoints[i]];
                        this.changed();
                    },

                    /** Names live in the saved route too; no need to route again. */
                    renamed() {
                        if (!this.state?.properties) return;
                        this.state = { ...this.state, properties: { ...this.state.properties, waypoints: this.waypoints.map((w) => ({ ...w, name: w.name || null })) } };
                        this.draw(false);
                    },

                    setAllModes(mode) {
                        this.newMode = mode;
                        this.modes = this.modes.map(() => mode);
                        this.changed();
                    },

                    changed() {
                        this.state = null;
                        this.draw(false);
                        clearTimeout(ctx.timer);
                        ctx.timer = setTimeout(() => this.rebuild(), 600);
                    },

                    async rebuild() {
                        this.error = null;
                        if (this.waypoints.length < 2) return;
                        this.busy = true;
                        const result = await this.$wire.buildTourRoute(
                            this.waypoints.map((w) => ({ lat: w.lat, lng: w.lng, name: w.name || null })),
                            [...this.modes],
                        );
                        this.busy = false;
                        if (!result || result.error) {
                            this.error = result?.error ?? 'Не удалось построить маршрут.';
                            return;
                        }
                        this.state = result;
                        this.draw(false);
                    },

                    fromItinerary() {
                        const days = Object.values(this.$wire.get('data.days') ?? {});
                        const byId = Object.fromEntries(this.places.map((p) => [String(p.id), p]));
                        const stops = days.map((d) => byId[String(d.place_id ?? '')]).filter(Boolean)
                            .filter((p, i, all) => i === 0 || all[i - 1].id !== p.id);
                        if (stops.length < 2) {
                            this.error = 'Укажите место хотя бы в двух днях на вкладке «Программа».';
                            return;
                        }
                        this.waypoints = stops.map((p) => ({ lat: p.lat, lng: p.lng, name: p.name }));
                        this.modes = stops.slice(1).map(() => this.newMode);
                        this.changed();
                        this.fit();
                    },

                    async importGpx(event) {
                        const file = event.target.files[0];
                        event.target.value = '';
                        if (!file) return;
                        // Timestamps and device extensions are not needed and make big files heavy.
                        const xml = (await file.text())
                            .replace(/<time>[^<]*<\/time>/g, '')
                            .replace(/<extensions>[\s\S]*?<\/extensions>/g, '')
                            .replace(/>\s+</g, '><');
                        this.busy = true;
                        this.error = null;
                        const result = await this.$wire.importTourGpx(xml);
                        this.busy = false;
                        if (!result || result.error) {
                            this.error = result?.error ?? 'Не удалось прочитать GPX.';
                            return;
                        }
                        this.state = result;
                        this.loadFromState();
                        this.draw(true);
                    },

                    clear() {
                        if (!confirm('Удалить маршрут тура?')) return;
                        this.waypoints = [];
                        this.modes = [];
                        this.state = null;
                        this.error = null;
                        this.draw(false);
                    },

                    fit() {
                        const points = this.waypoints.map((w) => [w.lat, w.lng]);
                        if (points.length) ctx.map?.fitBounds(L.latLngBounds(points).pad(0.15), { maxZoom: 12 });
                    },

                    draw(fit) {
                        if (!ctx.map) return;
                        ctx.route.clearLayers();
                        ctx.markers.clearLayers();

                        if (this.state?.features?.length) {
                            this.state.features.forEach((f) => {
                                const style = MODE_STYLE[f.properties?.mode] ?? MODE_STYLE.drive;
                                L.geoJSON(f, { style: {
                                    color: style.color, weight: 4, opacity: 0.9,
                                    dashArray: f.properties?.approximate || f.properties?.mode === 'line' ? '6 6' : null,
                                } }).bindTooltip(`${style.label}: ${f.properties?.distanceKm ?? '?'} км`).addTo(ctx.route);
                            });
                        } else if (this.waypoints.length > 1) {
                            L.polyline(this.waypoints.map((w) => [w.lat, w.lng]), { color: '#94a3b8', weight: 3, dashArray: '4 6' }).addTo(ctx.route);
                        }

                        this.waypoints.forEach((w, i) => {
                            const marker = L.marker([w.lat, w.lng], { icon: pin(i + 1), draggable: true, title: w.name || `Точка ${i + 1}` });
                            marker.on('dragend', (e) => {
                                const { lat, lng } = e.target.getLatLng();
                                this.waypoints[i].lat = round(lat);
                                this.waypoints[i].lng = round(lng);
                                this.changed();
                            });
                            marker.bindTooltip(w.name || `Точка ${i + 1}`).addTo(ctx.markers);
                        });

                        if (fit) {
                            const bounds = ctx.route.getLayers().length ? L.featureGroup(ctx.route.getLayers()).getBounds() : null;
                            if (bounds?.isValid()) ctx.map.fitBounds(bounds.pad(0.1), { maxZoom: 12 }); else this.fit();
                        }
                    },

                    get props() {
                        return this.state?.properties ?? null;
                    },

                    profilePath() {
                        const profile = this.props?.elevationProfile;
                        if (!profile || profile.length < 2) return '';
                        const km = profile[profile.length - 1][0] || 1;
                        const heights = profile.map((p) => p[1]);
                        const min = Math.min(...heights);
                        const span = Math.max(1, Math.max(...heights) - min);
                        const line = profile.map((p, i) => `${i ? 'L' : 'M'}${(p[0] / km * 1000).toFixed(1)},${(95 - (p[1] - min) / span * 85).toFixed(1)}`).join(' ');
                        return `${line} L1000,100 L0,100 Z`;
                    },
                };
            };

            window.tuLocationPicker = ({ lat, lng, alt }) => {
                const ctx = { map: null, marker: null };

                return {
                    lat, lng, alt,

                    init() {
                        whenLeaflet(() => {
                            ctx.map = createMap(this.$refs.map, () => this.place(true));
                            ctx.map.on('click', (e) => this.set(e.latlng.lat, e.latlng.lng));
                        });
                        this.$watch('lat', () => this.place(false));
                        this.$watch('lng', () => this.place(false));
                    },

                    async set(lat, lng) {
                        this.lat = Math.round(lat * 1e6) / 1e6;
                        this.lng = Math.round(lng * 1e6) / 1e6;
                        if (this.alt === null || this.alt === '' || this.alt === undefined) {
                            const height = await altitudeOf(this.lat, this.lng);
                            if (height !== null) this.alt = height;
                        }
                    },

                    place(center) {
                        if (!ctx.map) return;
                        const lat = parseFloat(this.lat), lng = parseFloat(this.lng);
                        if (Number.isNaN(lat) || Number.isNaN(lng)) return;
                        if (!ctx.marker) {
                            ctx.marker = L.marker([lat, lng], { icon: pin('●', '#c4122f'), draggable: true }).addTo(ctx.map);
                            ctx.marker.on('dragend', (e) => { const p = e.target.getLatLng(); this.set(p.lat, p.lng); });
                        } else {
                            ctx.marker.setLatLng([lat, lng]);
                        }
                        if (center) ctx.map.setView([lat, lng], 10);
                    },
                };
            };
        })();
    </script>
@endonce
