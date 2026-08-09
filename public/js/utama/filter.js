// ======================================================
// FILTER.JS FINAL PROFESSIONAL
// Support:
// ✔ Search alumni / NIM / perusahaan
// ✔ Filter linearitas
// ✔ Filter tahun lulus
// ✔ Filter wilayah
// ✔ Sidebar hasil pencarian
// ✔ Marker normal + cluster
// ✔ Status kerja / belum kerja
// ✔ Lokasi perusahaan / domisili otomatis
// ======================================================

let arrayMarker = [];

function canViewBelumBekerja() {
    return !!(window.appAuth && window.appAuth.canViewBelumBekerja);
}

function getDefaultStatusFilterValues() {
    return canViewBelumBekerja()
        ? ['bekerja', 'belum_bekerja']
        : ['bekerja'];
}

function normalisasiModeVisualisasi(mode) {
    const value = (mode || '').toString().trim().toLowerCase();

    if (value === 'choropleth') return 'choropleth';
    if (value === 'heatmap') return 'heatmap';
    return 'marker';
}

function sinkronkanLegendaModeVisualisasi() {
    const mode = normalisasiModeVisualisasi(window.visualizationMode || 'marker');

    const legendaMarkerEl = document.querySelector('.status-legend');
    const legendaChoroplethEl = document.querySelector('.choropleth-legend');

    if (legendaMarkerEl) {
        legendaMarkerEl.classList.toggle('is-mode-hidden', mode !== 'marker');
    }

    if (legendaChoroplethEl) {
        legendaChoroplethEl.classList.toggle('is-mode-hidden', mode !== 'choropleth');
    }
}

function sinkronkanSelectModeVisualisasi(mode) {
    const candidates = [
        document.getElementById('visualization-mode-ui'),
        document.getElementById('filter-visualization-mode')
    ].filter(Boolean);

    if (!candidates.length) {
        return;
    }

    const next = normalisasiModeVisualisasi(mode);

    candidates.forEach(function (el) {
        if (el.value !== next) {
            el.value = next;
        }
    });

    if (typeof window.syncCustomSelectValue === 'function') {
        window.syncCustomSelectValue('visualization-mode-ui', next);
        window.syncCustomSelectValue('filter-visualization-mode', next);
    }
}

function updateChoroplethLegend() {
    const maxEl = document.getElementById('choropleth-legend-max');
    const itemsEl = document.getElementById('choropleth-legend-items');
    const footEl = document.querySelector('.choropleth-legend-foot');

    if (!maxEl || !itemsEl) {
        return;
    }

    const breaks = Array.isArray(window.__choroplethBreaks) ? window.__choroplethBreaks : [];
    const max =
        breaks.length
            ? Math.max(0, ...breaks.map(b => Number(b?.max) || 0))
            : 0;

    maxEl.textContent = String(Math.max(0, Math.floor(max || 0)));

    if (footEl) {
        footEl.classList.toggle('is-hidden', max <= 0);
    }

    const colorsByLabel = {
        'Rendah': '#FEF3C7',
        'Sedang': '#FDBA74',
        'Tinggi': '#FB923C',
        'Tertinggi': '#EF4444',
    };

    const legendItems = [
        { color: '#f1f5f9', label: 'Tidak ada data (0)' }
    ];

    if (!breaks.length) {
        itemsEl.innerHTML = legendItems.map(renderLegendItem).join('');
        return;
    }

    breaks.forEach(function (b, index) {
        const min = Number(b?.min);
        const maxB = Number(b?.max);

        if (!Number.isFinite(min) || !Number.isFinite(maxB)) {
            return;
        }

        const rangeText = min === maxB ? `${min}` : `${min}-${maxB}`;
        const label = (b?.label || '').toString() || `Kelas ${index + 1}`;

        legendItems.push({
            color: colorsByLabel[label] || '#EF4444',
            label: `${label} (${rangeText} alumni)`
        });
    });

    itemsEl.innerHTML = legendItems.map(renderLegendItem).join('');

    function renderLegendItem(item) {
        const safeColor = item?.color || '#f1f5f9';
        const safeLabel = (item?.label || '').toString();

        return `
            <div class="choropleth-legend-item">
                <span class="choropleth-swatch" style="--choropleth-color:${safeColor};" aria-hidden="true"></span>
                <span>${safeLabel}</span>
            </div>
        `;
    }
}

window.updateChoroplethLegend = updateChoroplethLegend;

window.clearHeatmapLayer = function () {
    if (typeof map === 'undefined' || !map) {
        return;
    }

    if (window.heatmapLayer && map.hasLayer(window.heatmapLayer)) {
        map.removeLayer(window.heatmapLayer);
    }
};

window.updateHeatmapLayer = function (points) {
    if (typeof map === 'undefined' || !map) {
        return;
    }

    const mode = normalisasiModeVisualisasi(window.visualizationMode || 'marker');
    if (mode !== 'heatmap') {
        window.clearHeatmapLayer();
        return;
    }

    if (typeof L === 'undefined' || typeof L.heatLayer !== 'function') {
        return;
    }

    const safePoints = Array.isArray(points) ? points : [];

    if (!safePoints.length) {
        window.clearHeatmapLayer();
        return;
    }

    const maxDensity = Math.max.apply(null, safePoints.map(p => Number(p?.[2]) || 1));

    const config = {
        radius: 28,
        blur: 22,
        maxZoom: 17,
        max: Number.isFinite(maxDensity) ? maxDensity : 1,
        minOpacity: 0.35,
        gradient: {
            0.2: '#87CEFA',
            0.4: '#00FF00',
            0.6: '#FFFF00',
            0.8: '#FFA500',
            1.0: '#FF0000'
        }
    };

    if (window.heatmapLayer && map.hasLayer(window.heatmapLayer)) {
        map.removeLayer(window.heatmapLayer);
    }

    window.heatmapLayer = L.heatLayer(safePoints, config);
    window.heatmapLayer.addTo(map);
};

window.setVisualizationMode = function (mode, options) {
    const next = normalisasiModeVisualisasi(mode);
    const rebuildMarkers =
        options && typeof options.rebuildMarkers === 'boolean'
            ? options.rebuildMarkers
            : (next === 'marker');

    window.visualizationMode = next;

    sinkronkanSelectModeVisualisasi(next);
    sinkronkanLegendaModeVisualisasi();

    // Pastikan polygon choropleth terlihat saat mode choropleth.
    if (next === 'choropleth') {
        window.statusPolygonAktif = true;
        const togglePolygon = document.getElementById('toggle-polygon-map');
        if (togglePolygon) {
            togglePolygon.checked = true;
        }

        if (typeof window.perbaruiTampilanPolygon === 'function') {
            window.perbaruiTampilanPolygon();
        }
    }

    // Kelola heatmap layer
    if (next !== 'heatmap') {
        window.clearHeatmapLayer();
    } else {
        window.updateHeatmapLayer(window.__heatmapPoints || []);
    }

    // Refresh style polygon (default vs choropleth)
    if (typeof window.refreshWilayahStyle === 'function') {
        window.refreshWilayahStyle();
    }

    // Sembunyikan seluruh marker saat bukan marker view
    if (next !== 'marker') {
        initMarkerGroups();
        initMultiJobStorage();

        try {
            window.mainAlumniLayerGroup && window.mainAlumniLayerGroup.clearLayers();
            window.mainAlumniClusterGroup && window.mainAlumniClusterGroup.clearLayers();
            window.studiLanjutLayerGroup && window.studiLanjutLayerGroup.clearLayers();
            window.studiLanjutClusterGroup && window.studiLanjutClusterGroup.clearLayers();
        } catch (_) { }

        clearMultiJobLayers();
    }

    if (typeof window.perbaruiTampilanPeta === 'function') {
        window.perbaruiTampilanPeta();
    }

    if (next === 'marker' && rebuildMarkers && typeof window.filterDanTampilkanMarker === 'function') {
        window.filterDanTampilkanMarker();
    }
};

function showToastKecil(message) {
    const text = (message || '').toString().trim();
    if (!text) {
        return;
    }

    const el = document.createElement('div');
    el.className = 'toast-kecil';
    el.setAttribute('role', 'status');
    el.setAttribute('aria-live', 'polite');
    el.textContent = text;

    document.body.appendChild(el);

    // Trigger transition
    requestAnimationFrame(() => el.classList.add('is-show'));

    const ttl = 2200;
    setTimeout(() => el.classList.remove('is-show'), ttl);
    setTimeout(() => el.remove(), ttl + 450);
}

window.showToastKecil = showToastKecil;

function initMultiJobStorage() {
    if (!window.multiJobLayerGroup) {
        window.multiJobLayerGroup = L.layerGroup().addTo(map);
    }

    if (!window.polylineLayerGroup) {
        window.polylineLayerGroup = L.layerGroup().addTo(map);
    }

    if (!window.activeMultiJobLayers) {
        window.activeMultiJobLayers = {};
    }

    if (!window.mainAlumniMarkersById) {
        window.mainAlumniMarkersById = {};
    }

    if (!window.alumniDataById) {
        window.alumniDataById = {};
    }
}

function shouldConsumeLeafletActivation(e) {
    if (typeof window.shouldSuppressMapFeatureClick !== 'function' || !window.shouldSuppressMapFeatureClick()) {
        return false;
    }

    if (e?.originalEvent && typeof L !== 'undefined' && L.DomEvent && typeof L.DomEvent.stop === 'function') {
        L.DomEvent.stop(e.originalEvent);
    }

    const shouldClosePopup =
        typeof window.shouldClosePopupOnSuppressedFeatureClick !== 'function' ||
        window.shouldClosePopupOnSuppressedFeatureClick();

    if (shouldClosePopup && typeof map !== 'undefined' && map && typeof map.closePopup === 'function') {
        map.closePopup();
    }

    return true;
}

function bindGuardedPopup(layer, content, options) {
    if (!layer || typeof layer.bindPopup !== 'function') {
        return layer;
    }

    layer.bindPopup(content, options);

    if (typeof layer.off === 'function' && typeof layer._openPopup === 'function') {
        layer.off('click', layer._openPopup, layer);
    }

    if (typeof layer.on === 'function' && typeof layer.openPopup === 'function') {
        layer.on('click', function (e) {
            if (shouldConsumeLeafletActivation(e)) {
                return;
            }

            layer.openPopup(e?.latlng);
        });
    }

    return layer;
}

function clearMultiJobLayers() {
    initMultiJobStorage();

    if (window.multiJobLayerGroup) {
        window.multiJobLayerGroup.clearLayers();
    }

    if (window.polylineLayerGroup) {
        window.polylineLayerGroup.clearLayers();
    }

    window.activeMultiJobLayers = {};
    window.mainAlumniMarkersById = {};
    window.alumniDataById = {};
}

// ======================================================
// WADAH MARKER
// ======================================================
function initMarkerGroups() {
    if (!window.mainAlumniLayerGroup) {
        window.mainAlumniLayerGroup = L.featureGroup();
    }

    if (!window.mainAlumniClusterGroup) {
        // SIDANG-MAP: Marker alumni dikelompokkan agar peta tetap terbaca dan ringan ketika titik berdekatan.
        window.mainAlumniClusterGroup = L.markerClusterGroup({
            chunkedLoading: true,
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: false,
            zoomToBoundsOnClick: true,
            maxClusterRadius: 50,
            spiderfyDistanceMultiplier: 1.5
        });
    }

    if (!window.studiLanjutLayerGroup) {
        window.studiLanjutLayerGroup = L.layerGroup();
    }

    if (!window.studiLanjutClusterGroup) {
        window.studiLanjutClusterGroup = L.markerClusterGroup({
            chunkedLoading: true,
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: false,
            zoomToBoundsOnClick: true,
            maxClusterRadius: 50,
            spiderfyDistanceMultiplier: 1.5,
            iconCreateFunction: function (cluster) {
                const count = cluster.getChildCount();
                return L.divIcon({
                    html: `<div><span>${count}</span></div>`,
                    className: 'marker-cluster studi-lanjut-cluster',
                    iconSize: L.point(40, 40)
                });
            }
        });
    }

    // Alias untuk kompatibilitas kode lama
    window.wadahNormal = window.mainAlumniLayerGroup;
    window.wadahCluster = window.mainAlumniClusterGroup;
}

// Default: tampilkan marker langsung; koordinat yang sama ditangani sebagai stack marker.
window.statusClusterAktif = false;

let mapMarkerFetchController = null;
let mapMarkerFetchSequence = 0;
let mapFilterOptionsInitialized = false;
let mapMarkerLoadingHideTimer = null;

function getSelectedFilterValues(selectId) {
    const select = document.getElementById(selectId);

    if (!select) {
        return [];
    }

    return Array.from(select.selectedOptions || [])
        .map(option => option.value)
        .filter(Boolean);
}

function appendMultiValueParam(params, key, values) {
    const cleanValues = (values || [])
        .map(value => (value || '').toString().trim())
        .filter(value => value !== '' && value !== 'semua');

    if (cleanValues.length) {
        params.set(key, cleanValues.join(','));
    }
}

function buildMapMarkerApiParams() {
    const params = new URLSearchParams();

    // Keyword pencarian diterapkan langsung pada payload marker di browser.
    // Request API hanya diperlukan untuk pemuatan awal dan filter non-keyword.
    appendMultiValueParam(params, 'bidang_pekerjaan', getSelectedFilterValues('filter-bidang'));
    const statusValues = getSelectedFilterValues('filter-status-kerja')
        .filter(value => canViewBelumBekerja() || value !== 'belum_bekerja');
    appendMultiValueParam(params, 'status', statusValues);

    const linearitas = document.getElementById('filter-linearitas')?.value || 'semua';
    if (linearitas !== 'semua') {
        params.set('linearitas', linearitas);
    }

    const tahun = document.getElementById('filter-tahun')?.value || 'semua';
    if (tahun !== 'semua') {
        params.set('tahun', tahun);
    }

    const angkatan = document.getElementById('filter-angkatan')?.value || 'semua';
    if (angkatan !== 'semua') {
        params.set('angkatan', angkatan);
    }

    const wilayahId = document.getElementById('filter-wilayah')?.value || '';
    if (wilayahId !== '' && wilayahId !== '0') {
        params.set('wilayah_id', wilayahId);
    }

    return params;
}

function sinkronkanFilterWilayahPeta(options) {
    const select = document.getElementById('filter-wilayah');
    if (!select || typeof window.terapkanFilterWilayahPeta !== 'function') {
        return;
    }

    const value = (select.value || '').toString().trim();
    const selectedOption = select.options[select.selectedIndex] || null;
    const namaWilayah = value
        ? (selectedOption?.dataset?.wilayahNama || selectedOption?.textContent || '')
        : '';

    window.terapkanFilterWilayahPeta(namaWilayah, {
        flyTo: options?.flyTo === true
    });
}

function hydrateMapPayload(payload) {
    const safePayload = payload && typeof payload === 'object' ? payload : {};

    window.mapPayload = safePayload;
    if (Object.prototype.hasOwnProperty.call(safePayload, 'can_view_belum_bekerja')) {
        window.appAuth = window.appAuth || {};
        window.appAuth.canViewBelumBekerja = !!safePayload.can_view_belum_bekerja;
    }
    alumniData = Array.isArray(safePayload.markers) ? safePayload.markers : [];
    window.alumniData = alumniData;
    window.studiLanjutData = Array.isArray(safePayload.studi_lanjut_markers)
        ? safePayload.studi_lanjut_markers
        : [];
}

function emptyMapPayload() {
    return {
        total_alumni: 0,
        total_bekerja: 0,
        total_belum_bekerja: 0,
        total_multi_job: 0,
        total_studi_lanjut: 0,
        markers: [],
        studi_lanjut_markers: []
    };
}

function getMapPayloadCount(key, fallback) {
    const payload = window.mapPayload || {};

    if (!Object.prototype.hasOwnProperty.call(payload, key)) {
        return fallback;
    }

    const value = Number(payload[key]);

    if (!Number.isFinite(value)) {
        return fallback;
    }

    return Math.max(0, Math.floor(value));
}

function setMapMarkerLoading(visible, message, state) {
    const loadingEl = document.getElementById('map-marker-loading');
    if (!loadingEl) {
        return;
    }

    const textEl = document.getElementById('map-marker-loading-text');

    if (mapMarkerLoadingHideTimer) {
        clearTimeout(mapMarkerLoadingHideTimer);
        mapMarkerLoadingHideTimer = null;
    }

    if (message && textEl) {
        textEl.textContent = message;
    }

    loadingEl.classList.toggle('is-error', state === 'error');
    loadingEl.hidden = !visible;
}

function showMapMarkerLoadingError(message) {
    setMapMarkerLoading(true, message || 'Data marker gagal dimuat.', 'error');

    mapMarkerLoadingHideTimer = setTimeout(function () {
        setMapMarkerLoading(false);
    }, 4500);
}

function renderFetchedMapPayload() {
    window.__RENDERING_FETCHED_MARKERS__ = true;
    try {
        filterDanTampilkanMarker();
    } finally {
        window.__RENDERING_FETCHED_MARKERS__ = false;
    }
}

function showMapDataError(message) {
    const text = message || 'Data marker peta gagal dimuat. Silakan coba lagi.';

    if (typeof Swal !== 'undefined' && Swal && typeof Swal.fire === 'function') {
        Swal.fire({
            icon: 'error',
            title: 'Gagal memuat peta',
            text
        });
        return;
    }

    if (typeof window.showToastKecil === 'function') {
        window.showToastKecil(text);
    }

    const container = document.getElementById('search-results');
    if (container) {
        container.classList.remove('is-hidden');
        container.innerHTML = '';
        const messageEl = document.createElement('div');
        messageEl.className = 'result-empty';
        messageEl.textContent = text;
        container.appendChild(messageEl);
    }
}

function initializeMapFilterOptions() {
    if (mapFilterOptionsInitialized) {
        return;
    }

    populateBidangFilter();
    populateAngkatanFilter();
    populateWilayahFilter();
    mapFilterOptionsInitialized = true;
}

function fetchMapMarkersAndRender(options) {
    const endpoint = window.mapDataUrl || window.__MAP_MARKER_ENDPOINT__;

    if (!endpoint || typeof fetch !== 'function') {
        setMapMarkerLoading(false);
        renderFetchedMapPayload();
        return Promise.resolve();
    }

    if (mapMarkerFetchController) {
        mapMarkerFetchController.abort();
    }

    const requestId = ++mapMarkerFetchSequence;
    mapMarkerFetchController = new AbortController();

    const params = buildMapMarkerApiParams();
    const url = new URL(endpoint, window.location.origin);
    params.forEach((value, key) => url.searchParams.set(key, value));

    setMapMarkerLoading(
        true,
        options?.initializeCustomSelect ? 'Memuat data alumni...' : 'Memperbarui marker...'
    );

    // SIDANG-MAP: Filter dikirim ke endpoint JSON; hasilnya diteruskan ke pembentukan marker dan ringkasan Leaflet.
    return fetch(url.toString(), {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        signal: mapMarkerFetchController.signal
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Gagal memuat data marker (${response.status})`);
            }

            return response.json();
        })
        .then(payload => {
            if (requestId !== mapMarkerFetchSequence) {
                return;
            }

            hydrateMapPayload(payload);
            initializeMapFilterOptions();

            if (options?.initializeCustomSelect && !window.__CUSTOM_SELECT_INITIALIZED__) {
                initCustomSelect();
                window.__CUSTOM_SELECT_INITIALIZED__ = true;
            }

            renderFetchedMapPayload();
            setMapMarkerLoading(false);
        })
        .catch(error => {
            if (error && error.name === 'AbortError') {
                return;
            }

            console.error(error);
            hydrateMapPayload(emptyMapPayload());

            if (options?.initializeCustomSelect && !window.__CUSTOM_SELECT_INITIALIZED__) {
                initializeMapFilterOptions();
                initCustomSelect();
                window.__CUSTOM_SELECT_INITIALIZED__ = true;
            }

            renderFetchedMapPayload();
            showMapMarkerLoadingError('Data marker gagal dimuat.');
            showMapDataError(error?.message || 'Data marker peta gagal dimuat. Silakan coba lagi.');
        });
}

// ======================================================
// SAAT DOM READY
// ======================================================
document.addEventListener("DOMContentLoaded", function () {

    initMarkerGroups();

    window.visualizationMode = normalisasiModeVisualisasi(window.visualizationMode || 'marker');
    sinkronkanSelectModeVisualisasi(window.visualizationMode);
    sinkronkanLegendaModeVisualisasi();

    bindFilterEvents();

    // Bidang Kerja dibuat multi-select (tetap tampil dropdown via custom select)
    const bidangSelect = document.getElementById('filter-bidang');
    if (bidangSelect) {
        bidangSelect.multiple = true;
    }

    // Cari Berdasarkan dibuat multi-select (tetap tampil dropdown via custom select)
    const cariBerdasarkanSelect = document.getElementById('search-category');
    if (cariBerdasarkanSelect) {
        cariBerdasarkanSelect.multiple = true;
    }

    // Status Kerja dibuat multi-select (konsisten dengan Bidang Kerja)
    const statusKerjaSelect = document.getElementById('filter-status-kerja');
    if (statusKerjaSelect) {
        statusKerjaSelect.multiple = true;
    }

    initMultiJobStorage();
    initMultiJobToggleHandler();
    initPopupProfileClickHandler();

    if (window.mapDataUrl || window.__MAP_MARKER_ENDPOINT__) {
        fetchMapMarkersAndRender({ initializeCustomSelect: true });
    } else {
        setMapMarkerLoading(false);
        populateBidangFilter();
        populateAngkatanFilter();
        initCustomSelect();
        window.__CUSTOM_SELECT_INITIALIZED__ = true;
        filterDanTampilkanMarker();
    }
});

function initPopupProfileClickHandler() {
    if (window.__popupProfileClickHandlerInstalled) {
        return;
    }

    window.__popupProfileClickHandlerInstalled = true;

    document.addEventListener('click', function (e) {
        const el = e.target && e.target.closest ? e.target.closest('.clickable-profile[data-alumni-id]') : null;
        if (!el) {
            return;
        }

        const alumniId = (el.dataset.alumniId || '').toString();
        if (!alumniId) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        if (typeof map !== 'undefined' && map && typeof map.closePopup === 'function') {
            map.closePopup();
        }

        if (typeof window.bukaProfilAlumniById === 'function') {
            window.bukaProfilAlumniById(alumniId);
        }
    });
}

function initMultiJobToggleHandler() {
    if (window.__multiJobToggleHandlerInstalled) {
        return;
    }

    window.__multiJobToggleHandlerInstalled = true;

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.multi-job-toggle');
        if (!btn) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        const alumniId = btn.dataset.alumniId || '';
        toggleMultiJobLayers(alumniId);

        btn.classList.toggle('is-active', !!(window.activeMultiJobLayers && window.activeMultiJobLayers[alumniId]));
    });
}

function toggleMultiJobLayers(alumniId) {
    const id = (alumniId || '').toString();
    if (!id) {
        return;
    }

    initMultiJobStorage();

    const active = window.activeMultiJobLayers || {};

    if (active[id]) {
        (active[id].markers || []).forEach(m => window.multiJobLayerGroup.removeLayer(m));
        (active[id].lines || []).forEach(l => window.polylineLayerGroup.removeLayer(l));
        delete active[id];
        window.activeMultiJobLayers = active;
        return;
    }

    const item = (window.alumniDataById && window.alumniDataById[id]) ||
        (Array.isArray(alumniData) ? alumniData.find(x => (x && ((x.alumni_id ?? x.id) + '') === id)) : null);

    if (!item) {
        return;
    }

    const jobs = Array.isArray(item.pekerjaan_lainnya) ? item.pekerjaan_lainnya : [];
    if (!jobs.length) {
        return;
    }

    const mainMarker = window.mainAlumniMarkersById ? window.mainAlumniMarkersById[id] : null;
    if (!mainMarker) {
        return;
    }

    const mainLatLng = mainMarker.getLatLng();

    const sideIcon = L.icon({
        iconUrl: '/img/icon sampingan.png',
        // iconUrl: 'https://jmogfydhlafcuoknkcrg.supabase.co/storage/v1/object/public/alumni/icon%20sampingan.png',
        iconSize: [24, 38],
        iconAnchor: [12, 38],
        popupAnchor: [0, -42]
    });

    const markers = [];
    const lines = [];

    jobs.forEach(function (job) {
        const lat = parseFloat(job.latitude);
        const lng = parseFloat(job.longitude);

        if (!lat || !lng) {
            return;
        }

        if (Math.abs(lat - mainLatLng.lat) < 1e-10 && Math.abs(lng - mainLatLng.lng) < 1e-10) {
            return;
        }

        const perusahaan = job.perusahaan || '-';
        const jabatan = job.jabatan || '-';
        const statusKarir = job.status_karir || 'Sampingan';

        const perusahaanTooltip =
            perusahaan && perusahaan.trim() ? perusahaan : 'Instansi tidak diketahui';
        const jabatanTooltip =
            jabatan && jabatan.trim() ? jabatan : 'Jabatan tidak diketahui';

        const popup = `
            <div class="premium-popup">
                <div class="popup-cover"></div>
                <div class="popup-body" style="padding-top: 18px;">
                    <h3 class="popup-name">${item.nama || '-'}</h3>
                    <span class="popup-year">Pekerjaan Sampingan</span>
                    <div class="popup-info">
                        <div class="info-row">
                            <span class="icon">\u{1F3E2}</span>
                            <span><b>${perusahaan}</b></span>
                        </div>
                        <div class="info-row">
                            <span class="icon">\u{1F4BC}</span>
                            <span>${jabatan}</span>
                        </div>
                        <div class="info-row">
                            <span class="icon">\u{1F3F7}\u{FE0F}</span>
                            <span>${statusKarir}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const marker = L.marker([lat, lng], { icon: sideIcon });
        bindGuardedPopup(marker, popup);

        bindTooltipDenganDelay(
            marker,
            `
                <strong>${item.nama || '-'}</strong><br>
                <span>Pekerjaan Sampingan</span><br>
                ${perusahaanTooltip}<br>
                ${jabatanTooltip}
            `
        );

        const line = L.polyline(
            [mainLatLng, [lat, lng]],
            {
                color: '#6366f1',
                weight: 2,
                opacity: 0.85,
                dashArray: '6 6'
            }
        );

        window.polylineLayerGroup.addLayer(line);
        window.multiJobLayerGroup.addLayer(marker);

        markers.push(marker);
        lines.push(line);
    });

    if (markers.length === 0 && lines.length === 0) {
        return;
    }

    active[id] = { markers, lines };
    window.activeMultiJobLayers = active;
}

function getCariBerdasarkanScopes() {
    const select = document.getElementById('search-category');

    if (!select) {
        return { nama: true, nim: true, perusahaan: true, isSemua: true };
    }

    const values = Array.from(select.selectedOptions || [])
        .map(o => o.value)
        .filter(Boolean);

    const isSemua = values.length === 0 || values.includes('semua');

    if (isSemua) {
        return { nama: true, nim: true, perusahaan: true, isSemua: true };
    }

    return {
        nama: values.includes('nama'),
        nim: values.includes('nim'),
        perusahaan: values.includes('perusahaan'),
        isSemua: false
    };
}

function syncSearchClearButtonState() {
    const clearBtnEl = document.getElementById('btn-clear-search');
    if (!clearBtnEl) {
        return;
    }

    const val = document.getElementById('search-input')?.value ?? '';
    clearBtnEl.hidden = String(val).trim().length === 0;
}

function getSelectedFilterLabels(selectId, values) {
    const select = document.getElementById(selectId);
    const options = Array.from(select?.options || []);
    const selectedValues = Array.isArray(values) ? values : getSelectedFilterValues(selectId);

    return selectedValues
        .map(value => {
            const option = options.find(o => o.value === value);
            return (option?.textContent || value || '').toString().trim();
        })
        .filter(Boolean);
}

function getActiveSelectValue(selectId, fallback) {
    const select = document.getElementById(selectId);
    if (!select) {
        return fallback;
    }

    return (select.value ?? fallback).toString();
}

function isDefaultStatusFilter(values) {
    const cleanValues = (values || [])
        .map(value => (value || '').toString())
        .filter(value => value !== '' && value !== 'semua');
    const defaultValues = getDefaultStatusFilterValues();

    return cleanValues.length === defaultValues.length &&
        defaultValues.every(value => cleanValues.includes(value));
}

function getActiveFilterItems() {
    const items = [];
    const searchInput = document.getElementById('search-input');
    const keyword = (searchInput?.value ?? '').toString().trim();
    const hasActiveKeyword = keyword.length >= 2;

    if (hasActiveKeyword) {
        items.push({
            key: 'search',
            label: 'Pencarian',
            value: keyword
        });
    }

    const searchScopeValues = getSelectedFilterValues('search-category')
        .filter(value => value !== 'semua');
    if (hasActiveKeyword && searchScopeValues.length) {
        items.push({
            key: 'search_scope',
            label: 'Cari Berdasarkan',
            value: getSelectedFilterLabels('search-category', searchScopeValues).join(', ')
        });
    }

    const bidangValues = getSelectedFilterValues('filter-bidang')
        .filter(value => value !== 'semua');
    if (bidangValues.length) {
        items.push({
            key: 'bidang',
            label: 'Bidang Kerja',
            value: getSelectedFilterLabels('filter-bidang', bidangValues).join(', ')
        });
    }

    const wilayahValue = getActiveSelectValue('filter-wilayah', '');
    if (wilayahValue !== '' && wilayahValue !== '0') {
        const wilayahLabel = getSelectedFilterLabels('filter-wilayah', [wilayahValue])[0] || wilayahValue;
        items.push({
            key: 'wilayah',
            label: 'Kabupaten/Kota',
            value: wilayahLabel
        });
    }

    const tahunValue = getActiveSelectValue('filter-tahun', 'semua');
    if (tahunValue !== 'semua') {
        items.push({
            key: 'tahun',
            label: 'Tahun Lulus',
            value: getSelectedFilterLabels('filter-tahun', [tahunValue])[0] || tahunValue
        });
    }

    const linearitasValue = getActiveSelectValue('filter-linearitas', 'semua');
    if (linearitasValue !== 'semua') {
        items.push({
            key: 'linearitas',
            label: 'Kesesuaian Bidang',
            value: linearitasValue
        });
    }

    const statusSelect = document.getElementById('filter-status-kerja');
    if (statusSelect) {
        const statusValues = getSelectedFilterValues('filter-status-kerja');
        if (statusValues.includes('semua') || statusValues.length === 0) {
            items.push({
                key: 'status',
                label: 'Status Kerja',
                value: 'Semua Status'
            });
        } else if (!isDefaultStatusFilter(statusValues)) {
            const cleanStatusValues = statusValues.filter(value => value !== 'semua');
            items.push({
                key: 'status',
                label: 'Status Kerja',
                value: getSelectedFilterLabels('filter-status-kerja', cleanStatusValues).join(', ')
            });
        }
    }

    const angkatanValue = getActiveSelectValue('filter-angkatan', 'semua');
    if (angkatanValue !== 'semua') {
        items.push({
            key: 'angkatan',
            label: 'Angkatan',
            value: getSelectedFilterLabels('filter-angkatan', [angkatanValue])[0] || angkatanValue
        });
    }

    return items.filter(item => (item.value || '').toString().trim() !== '');
}

function setFilterSelectValue(selectId, value) {
    if (typeof window.syncCustomSelectValue === 'function') {
        window.syncCustomSelectValue(selectId, value);
        return;
    }

    const select = document.getElementById(selectId);
    if (!select) {
        return;
    }

    if (select.multiple) {
        const values = Array.isArray(value) ? value : [value];
        Array.from(select.options || []).forEach(function (option) {
            option.selected = values.includes(option.value);
        });
        return;
    }

    select.value = value;
}

function resetActiveFilterByKey(key) {
    switch (key) {
        case 'search': {
            const searchInput = document.getElementById('search-input');
            if (searchInput) {
                searchInput.value = '';
            }
            if (typeof window.resetHighlightWilayah === 'function') {
                window.resetHighlightWilayah();
            }
            break;
        }
        case 'search_scope':
            setFilterSelectValue('search-category', 'semua');
            break;
        case 'bidang':
            setFilterSelectValue('filter-bidang', 'semua');
            break;
        case 'wilayah':
            setFilterSelectValue('filter-wilayah', '');
            break;
        case 'tahun':
            setFilterSelectValue('filter-tahun', 'semua');
            break;
        case 'linearitas':
            setFilterSelectValue('filter-linearitas', 'semua');
            break;
        case 'status':
            setFilterSelectValue('filter-status-kerja', getDefaultStatusFilterValues());
            break;
        case 'angkatan':
            setFilterSelectValue('filter-angkatan', 'semua');
            break;
        default:
            return;
    }

    syncSearchClearButtonState();
    filterDanTampilkanMarker();
    updateActiveFilterUI();
}

function updateActiveFilterUI() {
    const items = getActiveFilterItems();
    const count = items.length;
    const filterButton = document.getElementById('toggle-filter');
    const countBadge = document.getElementById('active-filter-count');
    const summary = document.getElementById('active-filter-summary');
    const total = document.getElementById('active-filter-total');
    const list = document.getElementById('active-filter-list');
    const legendSummary = document.getElementById('legend-active-filter-summary');
    const legendRegionContext = document.getElementById('legend-region-context');

    if (filterButton) {
        filterButton.classList.toggle('has-active-filters', count > 0);
        filterButton.setAttribute('title', count > 0 ? `Filter (${count} aktif)` : 'Filter');
        filterButton.setAttribute('aria-label', count > 0 ? `Filter, ${count} aktif` : 'Filter');
    }

    if (countBadge) {
        countBadge.hidden = count === 0;
        countBadge.textContent = String(count);
    }

    if (summary) {
        summary.hidden = count === 0;
        summary.classList.toggle('has-active-filters', count > 0);
    }

    if (total) {
        total.textContent = String(count);
    }

    if (list) {
        if (count === 0) {
            list.innerHTML = '';
        } else {
            list.innerHTML = items.map(function (item) {
                const key = escapeHtml(item.key);
                const label = escapeHtml(item.label);
                const value = escapeHtml(item.value);

                return `
                    <span class="active-filter-chip" title="${label}: ${value}">
                        <span><b>${label}:</b> ${value}</span>
                        <button type="button" class="active-filter-remove" data-filter-key="${key}" aria-label="Hapus filter ${label}">&times;</button>
                    </span>
                `;
            }).join('');
        }
    }

    if (legendSummary) {
        if (count === 0) {
            legendSummary.hidden = true;
            legendSummary.textContent = '';
            legendSummary.removeAttribute('title');
        } else {
            const previewLabels = items.slice(0, 2).map(item => item.label).join(', ');
            const remaining = count > 2 ? ` +${count - 2}` : '';
            const detail = items.map(item => `${item.label}: ${item.value}`).join(' | ');

            legendSummary.hidden = false;
            legendSummary.textContent = `Filter aktif: ${previewLabels}${remaining}`;
            legendSummary.setAttribute('title', detail);
        }
    }

    if (legendRegionContext) {
        const wilayahFilter = items.find(item => item.key === 'wilayah');

        legendRegionContext.hidden = !wilayahFilter;
        legendRegionContext.textContent = wilayahFilter ? `Alumni di ${wilayahFilter.value}` : '';
    }
}

// ======================================================
// EVENT FILTER
// ======================================================
function bindFilterEvents() {

    const filterPanel = document.querySelector('.filter-panel');
    const collapsePanelBtn = document.getElementById('collapse-filter-panel');
    const resultsPanelEl = document.getElementById('search-results');
    const searchWrapEl = document.querySelector('.map-search-input-wrap');
    const clearBtnEl = document.getElementById('btn-clear-search');
    const filterToggleBtn = document.getElementById('toggle-filter');
    const filterBodyPanelEl = document.getElementById('filter-body');
    let pendingDismissedClick = false;
    let pendingDismissTimer = null;

    const setPanelCollapsed = (collapsed) => {
        if (!filterPanel) {
            return;
        }

        const isCollapsed = !!collapsed;
        filterPanel.classList.toggle('is-collapsed', isCollapsed);

        if (collapsePanelBtn) {
            collapsePanelBtn.setAttribute('aria-expanded', (!isCollapsed).toString());
        }
    };

    collapsePanelBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const isCollapsed = !!filterPanel?.classList.contains('is-collapsed');
        setPanelCollapsed(!isCollapsed);
    });

    const closeSearchPanel = (opts) => {
        const options = opts || {};
        const clearResults = options.clearResults === true;
        if (resultsPanelEl) {
            if (clearResults) resultsPanelEl.innerHTML = '';
            resultsPanelEl.classList.add('is-hidden');
        }
        if (typeof sinkronkanKontenPanel === 'function') {
            sinkronkanKontenPanel();
        }
    };

    const openSearchPanel = () => {
        if (resultsPanelEl) {
            resultsPanelEl.classList.remove('is-hidden');
        }
        if (typeof sinkronkanKontenPanel === 'function') {
            sinkronkanKontenPanel();
        }
    };

    const renderSearchHelper = (message) => {
        if (!resultsPanelEl) return;
        resultsPanelEl.innerHTML = `<div class="result-helper">${message}</div>`;
        openSearchPanel();
    };

    const setFilterChoicesOpen = (open) => {
        if (!filterBodyPanelEl) {
            return;
        }

        const isOpen = !!open;
        filterBodyPanelEl.classList.toggle('hidden', !isOpen);

        if (filterToggleBtn) {
            filterToggleBtn.setAttribute('aria-expanded', isOpen.toString());
        }
    };

    const closeFilterChoices = () => {
        setFilterChoicesOpen(false);
    };

    const isSearchPanelOpen = () =>
        !!resultsPanelEl && !resultsPanelEl.classList.contains('is-hidden');

    const isFilterChoicesOpen = () =>
        !!filterBodyPanelEl && !filterBodyPanelEl.classList.contains('hidden');

    const closeCustomDropdowns = () => {
        document.querySelectorAll('.custom-dropdown-options.open')
            .forEach(x => x.classList.remove('open'));

        document.querySelectorAll('.custom-dropdown-trigger.active')
            .forEach(x => x.classList.remove('active'));
    };

    const closeLayerControlMenu = () => {
        const layerMenu = document.getElementById('layer-control-menu');
        if (layerMenu) {
            layerMenu.classList.add('hidden');
        }
    };

    const closeLeafletPopup = () => {
        if (typeof map !== 'undefined' && map && typeof map.closePopup === 'function') {
            map.closePopup();
        }
    };

    const getOpenCustomDropdownRoots = () =>
        Array.from(document.querySelectorAll('.custom-dropdown-options.open'))
            .map(el => el.closest('.custom-dropdown-wrapper') || el)
            .filter(Boolean);

    const getOpenLayerControlRoot = () => {
        const menu = document.getElementById('layer-control-menu');
        if (!menu || menu.classList.contains('hidden')) {
            return null;
        }

        return document.getElementById('layer-control-panel') || menu;
    };

    const getActiveDismissRoots = () => {
        const roots = [];

        if ((isSearchPanelOpen() || isFilterChoicesOpen()) && filterPanel) {
            roots.push(filterPanel);
        }

        if (isSearchPanelOpen()) {
            if (resultsPanelEl) roots.push(resultsPanelEl);
            if (searchWrapEl) roots.push(searchWrapEl);
        }

        if (isFilterChoicesOpen()) {
            if (filterBodyPanelEl) roots.push(filterBodyPanelEl);
            if (filterToggleBtn) roots.push(filterToggleBtn);
        }

        getOpenCustomDropdownRoots().forEach(root => roots.push(root));

        const layerRoot = getOpenLayerControlRoot();
        if (layerRoot) {
            roots.push(layerRoot);
        }

        document.querySelectorAll('.leaflet-popup')
            .forEach(popup => roots.push(popup));

        return roots;
    };

    const hasDismissibleUiOpen = () =>
        isSearchPanelOpen() ||
        isFilterChoicesOpen() ||
        getOpenCustomDropdownRoots().length > 0 ||
        !!getOpenLayerControlRoot() ||
        !!document.querySelector('.leaflet-popup');

    const isSearchToFilterShortcut = (target) =>
        isSearchPanelOpen() &&
        !isFilterChoicesOpen() &&
        !document.querySelector('.leaflet-popup') &&
        getOpenCustomDropdownRoots().length === 0 &&
        !getOpenLayerControlRoot() &&
        !!filterToggleBtn &&
        !!target?.closest &&
        filterToggleBtn.contains(target.closest('#toggle-filter'));

    const isInsideActiveDismissRoot = (target) =>
        getActiveDismissRoots().some(root => root && root.contains(target));

    const closeDismissibleUi = () => {
        closeSearchPanel({ clearResults: false });
        closeFilterChoices();
        closeCustomDropdowns();
        closeLayerControlMenu();
        closeLeafletPopup();
    };

    const markDismissedClick = () => {
        pendingDismissedClick = true;
        window.__mapDismissClickUntil = Date.now() + 450;

        if (pendingDismissTimer) {
            clearTimeout(pendingDismissTimer);
        }

        pendingDismissTimer = setTimeout(function () {
            pendingDismissedClick = false;
            window.__mapDismissClickUntil = 0;
        }, 450);
    };

    window.shouldSuppressMapFeatureClick = function () {
        return (
            pendingDismissedClick ||
            Date.now() < (window.__mapDismissClickUntil || 0) ||
            Date.now() < (window.__mapFeatureClickSuppressUntil || 0)
        );
    };

    window.shouldClosePopupOnSuppressedFeatureClick = function () {
        return pendingDismissedClick || Date.now() < (window.__mapDismissClickUntil || 0);
    };

    const consumeDismissalEvent = (event) => {
        event.preventDefault();
        event.stopImmediatePropagation();
    };

    const dismissFromOutsideInteraction = (event) => {
        if (event.button !== undefined && event.button !== 0) {
            return false;
        }

        if (pendingDismissedClick) {
            consumeDismissalEvent(event);
            return true;
        }

        const target = event.target;
        if (!target || !hasDismissibleUiOpen()) {
            return false;
        }

        const mapContainer =
            typeof map !== 'undefined' && map && typeof map.getContainer === 'function'
                ? map.getContainer()
                : document.getElementById('map');
        const isMapInteraction = !!mapContainer && mapContainer.contains(target);
        const isStartEvent = ['pointerdown', 'mousedown', 'touchstart'].includes(event.type);
        const onlyLeafletPopupOpen =
            !!document.querySelector('.leaflet-popup') &&
            !isSearchPanelOpen() &&
            !isFilterChoicesOpen() &&
            getOpenCustomDropdownRoots().length === 0 &&
            !getOpenLayerControlRoot();

        if (onlyLeafletPopupOpen && isStartEvent && isMapInteraction && !isInsideActiveDismissRoot(target)) {
            return false;
        }

        if (isSearchToFilterShortcut(target) || isInsideActiveDismissRoot(target)) {
            return false;
        }

        closeDismissibleUi();
        markDismissedClick();
        consumeDismissalEvent(event);
        return true;
    };

    document.addEventListener('pointerdown', function (event) {
        dismissFromOutsideInteraction(event);
    }, true);

    document.addEventListener('mousedown', function (event) {
        dismissFromOutsideInteraction(event);
    }, true);

    document.addEventListener('touchstart', function (event) {
        dismissFromOutsideInteraction(event);
    }, true);

    document.addEventListener('click', function (event) {
        if (pendingDismissedClick) {
            consumeDismissalEvent(event);

            if (pendingDismissTimer) {
                clearTimeout(pendingDismissTimer);
            }

            pendingDismissTimer = setTimeout(function () {
                pendingDismissedClick = false;
                window.__mapDismissClickUntil = 0;
            }, 80);
        }
    }, true);

    if (filterToggleBtn && filterBodyPanelEl) {
        filterToggleBtn.setAttribute(
            'aria-expanded',
            (!filterBodyPanelEl.classList.contains('hidden')).toString()
        );
    }

    const syncClearButton = () => {
        syncSearchClearButtonState();
    };

    const clearSearch = () => {
        const input = document.getElementById('search-input');
        if (input) input.value = '';
        if (typeof window.resetHighlightWilayah === 'function') {
            window.resetHighlightWilayah();
        }
        closeSearchPanel({ clearResults: true });
        syncClearButton();
        renderFetchedMapPayload();
    };

    if (clearBtnEl) {
        clearBtnEl.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            clearSearch();
        });
    }

    // Klik di dalam search/panel jangan dianggap "klik di luar".
    searchWrapEl?.addEventListener('click', function (e) {
        e.stopPropagation();
    });
    resultsPanelEl?.addEventListener('click', function (e) {
        e.stopPropagation();
    });

    // Klik di luar (peta/area lain) -> tutup panel hasil tanpa menghapus keyword.
    document.addEventListener('click', function (event) {
        const t = event.target;
        const clickedInsideSearch = !!searchWrapEl && searchWrapEl.contains(t);
        const clickedInsideResult = !!resultsPanelEl && resultsPanelEl.contains(t);
        if (!clickedInsideSearch && !clickedInsideResult) {
            closeSearchPanel({ clearResults: false });
        }
    });

    document.getElementById('search-category')
        ?.addEventListener('change', function () {
            if (typeof window.resetHighlightWilayah === 'function') {
                window.resetHighlightWilayah();
            }

            renderFetchedMapPayload();
        });

    document.getElementById('filter-linearitas')
        ?.addEventListener('change', filterDanTampilkanMarker);

    document.getElementById('filter-bidang')
        ?.addEventListener('change', filterDanTampilkanMarker);

    const statusSelect = document.getElementById('filter-status-kerja');
    if (statusSelect) {
        const getSelectedStatusValues = () => Array.from(statusSelect.selectedOptions || [])
            .map(o => o.value)
            .filter(Boolean);

        let prevHasStudiLanjut = getSelectedStatusValues().includes('studi_lanjut');

        statusSelect.addEventListener('change', function () {
            const values = getSelectedStatusValues();
            const hasStudiLanjut = values.includes('studi_lanjut');

            if (hasStudiLanjut && !prevHasStudiLanjut) {
                showToastKecil('Marker studi lanjut berhasil ditampilkan');
            }

            prevHasStudiLanjut = hasStudiLanjut;
            filterDanTampilkanMarker();
        });
    }

    document.getElementById('filter-tahun')
        ?.addEventListener('change', filterDanTampilkanMarker);

    document.getElementById('filter-angkatan')
        ?.addEventListener('change', filterDanTampilkanMarker);

    document.getElementById('filter-wilayah')
        ?.addEventListener('change', function () {
            sinkronkanFilterWilayahPeta({ flyTo: true });
            filterDanTampilkanMarker();
        });

    ['visualization-mode-ui', 'filter-visualization-mode'].forEach(function (id) {
        document.getElementById(id)
            ?.addEventListener('change', function () {
                if (typeof window.setVisualizationMode === 'function') {
                    window.setVisualizationMode(this.value);
                }
            });
    });

    document.getElementById('btn-search')
        ?.addEventListener('click', handleSearchSubmit);

    const searchInputEl = document.getElementById('search-input');
    if (searchInputEl) {
        let isComposing = false;

        const triggerLiveSearch = function () {
            if (isComposing) return;
            handleSearchSubmit();
        };

        // Tetap dukung Enter, tapi tidak wajib.
        searchInputEl.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                handleSearchSubmit();
            }
        });

        // Live search: jalankan saat user mengetik (debounced).
        searchInputEl.addEventListener('input', function () {
            syncClearButton();
            triggerLiveSearch();
        });

        // Saat fokus kembali: jika keyword masih valid, tampilkan lagi panelnya.
        searchInputEl.addEventListener('focus', function () {
            const k = (searchInputEl.value ?? '').toString().trim();
            if (k.length >= 2) {
                openSearchPanel();
            } else if (k.length === 1) {
                renderSearchHelper('Ketik minimal 2 huruf untuk mencari nama, NIM, atau tempat kerja.');
            }
        });

        // Hindari pencarian saat IME composition (contoh: input bahasa).
        searchInputEl.addEventListener('compositionstart', function () {
            isComposing = true;
        });
        searchInputEl.addEventListener('compositionend', function () {
            isComposing = false;
            syncClearButton();
            triggerLiveSearch();
        });
    }

    filterToggleBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const isCurrentlyOpen = !!filterBodyPanelEl && !filterBodyPanelEl.classList.contains('hidden');
        if (isSearchPanelOpen()) {
            closeSearchPanel({ clearResults: false });
        }

        setFilterChoicesOpen(!isCurrentlyOpen);
    });

    // Klik di luar card filter menutup pilihan filter, tanpa mengubah keyword/search bar.
    document.addEventListener('pointerdown', function (event) {
        if (!filterBodyPanelEl || filterBodyPanelEl.classList.contains('hidden')) {
            return;
        }

        const target = event.target;
        const clickedInsideFilterChoices = filterBodyPanelEl.contains(target);
        const clickedOnFilterToggle = !!filterToggleBtn && filterToggleBtn.contains(target);

        if (!clickedInsideFilterChoices && !clickedOnFilterToggle) {
            closeFilterChoices();
        }
    });

    document.getElementById('toggle-advanced-filter')
        ?.addEventListener('click', function () {
            document.getElementById('advanced-filter-body')
                ?.classList.toggle('hidden');

            this.classList.toggle('active');
        });

    document.getElementById('btn-reset-filter')
        ?.addEventListener('click', resetSemuaFilter);

    document.getElementById('active-filter-list')
        ?.addEventListener('click', function (e) {
            const button = e.target?.closest?.('.active-filter-remove[data-filter-key]');
            if (!button) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();
            resetActiveFilterByKey(button.dataset.filterKey || '');
        });

    // Buat panel tetap compact jika filter & hasil masih kosong.
    const sinkronkanKontenPanel = () => {
        const scrollable = document.querySelector('.filter-panel .scrollable-content');
        if (!scrollable) return;

        const filterBody = document.getElementById('filter-body');
        const results = document.getElementById('search-results');

        const filterTerbuka = !!filterBody && !filterBody.classList.contains('hidden');
        const resultsHidden = !!results && results.classList.contains('is-hidden');
        const adaHasil = !!results && !resultsHidden && (results.textContent || '').trim() !== '';

        scrollable.classList.toggle('is-empty', !filterTerbuka && !adaHasil);
    };

    const resultsObserverEl = document.getElementById('search-results');
    const filterBodyEl = document.getElementById('filter-body');

    if (typeof MutationObserver !== 'undefined') {
        if (resultsObserverEl) {
            new MutationObserver(sinkronkanKontenPanel)
                .observe(resultsObserverEl, { childList: true, subtree: true, characterData: true });
        }

        if (filterBodyEl) {
            new MutationObserver(sinkronkanKontenPanel)
                .observe(filterBodyEl, { attributes: true, attributeFilter: ['class'] });
        }
    }

    sinkronkanKontenPanel();

    // Initial state: kalau keyword kosong, panel hasil ditutup & tombol clear disembunyikan.
    syncClearButton();
    updateActiveFilterUI();
    const initialKeyword = (document.getElementById('search-input')?.value ?? '').toString().trim();
    if (initialKeyword.length === 0) {
        closeSearchPanel({ clearResults: true });
    }
}

function handleSearchSubmit() {
    const inputEl = document.getElementById('search-input');
    const keyword = (inputEl?.value ?? '').toString().trim();

    // Empty: tutup panel + jangan render semua alumni di panel.
    if (keyword.length === 0) {
        if (typeof window.resetHighlightWilayah === 'function') {
            window.resetHighlightWilayah();
        }
        const container = document.getElementById('search-results');
        if (container) {
            container.innerHTML = '';
            container.classList.add('is-hidden');
        }
        const btn = document.getElementById('btn-clear-search');
        if (btn) btn.hidden = true;
        renderFetchedMapPayload();
        return;
    }

    // Minimal 2 karakter: tampilkan helper, tapi jangan filter daftar alumni dulu.
    if (keyword.length < 2) {
        const container = document.getElementById('search-results');
        if (container) {
            container.innerHTML = `<div class="result-helper">Ketik minimal 2 huruf untuk mencari nama, NIM, atau tempat kerja.</div>`;
            container.classList.remove('is-hidden');
        }
        window.__SEARCH_KEYWORD_OVERRIDE__ = '';
        renderFetchedMapPayload();
        window.__SEARCH_KEYWORD_OVERRIDE__ = null;
        return;
    }

    // Keyword valid: panel boleh tampil.
    const container = document.getElementById('search-results');
    if (container) {
        container.classList.remove('is-hidden');
    }
    const btn = document.getElementById('btn-clear-search');
    if (btn) btn.hidden = false;

    if (typeof window.resetHighlightWilayah === 'function') {
        window.resetHighlightWilayah();
    }

    renderFetchedMapPayload();
}

function populateBidangFilter() {

    const select =
        document.getElementById('filter-bidang');

    if (!select || !Array.isArray(alumniData)) {
        return;
    }

    Array.from(select.querySelectorAll('option[data-dynamic-option="true"]'))
        .forEach(option => option.remove());

    const bidangList = [...new Set(
        alumniData
            .map(item => (item?.bidang || '').trim())
            .filter(Boolean)
    )].sort((a, b) => a.localeCompare(b, 'id'));

    bidangList.forEach(bidang => {
        const option = document.createElement('option');
        option.value = bidang;
        option.textContent = bidang;
        option.dataset.dynamicOption = 'true';
        select.appendChild(option);
    });
}

function populateAngkatanFilter() {

    const select =
        document.getElementById('filter-angkatan');

    if (!select || !Array.isArray(alumniData)) {
        return;
    }

    Array.from(select.querySelectorAll('option[data-dynamic-option="true"]'))
        .forEach(option => option.remove());

    const angkatanList = [...new Set(
        alumniData
            .map(item => String(item?.angkatan || '').trim())
            .filter(Boolean)
    )].sort((a, b) => Number(b) - Number(a));

    angkatanList.forEach(angkatan => {
        const option = document.createElement('option');
        option.value = angkatan;
        option.textContent = angkatan;
        option.dataset.dynamicOption = 'true';
        select.appendChild(option);
    });
}

function populateWilayahFilter() {
    const select = document.getElementById('filter-wilayah');
    if (!select) {
        return;
    }

    // Jangan populate ulang kalau sudah ada pilihan (selain "Semua")
    if (select.options.length > 1) {
        return;
    }

    fetch('/wilayah-kalsel', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!Array.isArray(data)) {
                return;
            }

            data.forEach(function (wilayah) {
                const option = document.createElement('option');
                option.value = wilayah.id;
                option.textContent = wilayah.display;
                option.dataset.wilayahNama = wilayah.nama || wilayah.display || '';
                select.appendChild(option);
            });

            // initCustomSelect() runs synchronously before this async fetch resolves,
            // so the custom dropdown was built with only "Semua". Rebuild its option list now.
            const wrapper = select.closest('.custom-dropdown-wrapper');
            if (wrapper) {
                const list = wrapper.querySelector('.custom-dropdown-options');
                const trigger = wrapper.querySelector('.custom-dropdown-trigger');
                if (list && trigger) {
                    list.innerHTML = '';
                    Array.from(select.options).forEach(function (opt) {
                        const item = document.createElement('div');
                        item.className = 'custom-option' + (opt.selected ? ' selected' : '');
                        item.dataset.value = opt.value;
                        const left = document.createElement('span');
                        left.className = 'custom-option-left';
                        const text = document.createElement('span');
                        text.className = 'custom-option-text';
                        text.textContent = opt.text;
                        left.appendChild(text);
                        item.appendChild(left);
                        item.addEventListener('click', function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                            select.value = item.dataset.value;
                            trigger.querySelector('span').textContent = opt.text;
                            list.querySelectorAll('.custom-option').forEach(function (x) {
                                x.classList.remove('selected');
                            });
                            item.classList.add('selected');
                            select.dispatchEvent(new Event('change'));
                        });
                        list.appendChild(item);
                    });
                }
            }
        })
        .catch(function (err) {
            console.warn('Gagal memuat daftar wilayah Kalsel:', err);
        });
}

// ======================================================
// CUSTOM SELECT PREMIUM
// ======================================================
function initCustomSelect() {

    const selects = document.querySelectorAll('.custom-select');

    function getStatusKerjaTriggerText(select) {
        const selectedValues = Array.from(select.selectedOptions || [])
            .map(o => o.value)
            .filter(Boolean);

        const isSemua = selectedValues.length === 0 || selectedValues.includes('semua');
        if (isSemua) {
            return 'Semua Status Kerja';
        }

        const selectedTexts = Array.from(select.selectedOptions || [])
            .filter(o => o.value !== 'semua')
            .map(o => o.text)
            .filter(Boolean);

        if (selectedTexts.length === 1) {
            return selectedTexts[0];
        }

        return `${selectedTexts.length} Status Dipilih`;
    }

    function getMultiSelectTriggerText(select) {
        const selectedValues = Array.from(select.selectedOptions || [])
            .map(o => o.value)
            .filter(Boolean);

        const isSemua = selectedValues.length === 0 || selectedValues.includes('semua');
        if (isSemua) {
            return select.options[0]?.text || 'Semua';
        }

        const selectedTexts = Array.from(select.selectedOptions || [])
            .filter(o => o.value !== 'semua')
            .map(o => o.text)
            .filter(Boolean);

        if (selectedTexts.length === 1) {
            return selectedTexts[0];
        }

        if (selectedTexts.length === 2) {
            const gabungan = selectedTexts.join(', ');
            return gabungan.length <= 32 ? gabungan : `${selectedTexts[0]} +1`;
        }

        const first = selectedTexts[0] || 'Bidang Kerja';
        return `${first} +${selectedTexts.length - 1}`;
    }

    function updateCustomSelectUI(select) {
        const wrapper = select.closest('.custom-dropdown-wrapper');
        const triggerTextEl = wrapper?.querySelector('.custom-dropdown-trigger span');
        const options = wrapper?.querySelectorAll('.custom-option') || [];

        if (triggerTextEl) {
            if (select.multiple && select.id === 'filter-status-kerja') {
                triggerTextEl.textContent = getStatusKerjaTriggerText(select);
            } else {
            triggerTextEl.textContent = select.multiple
                ? getMultiSelectTriggerText(select)
                : (select.options[select.selectedIndex]?.text || '');
            }
        }

        options.forEach(optionEl => {
            const value = optionEl.dataset.value;
            const optionNode = Array.from(select.options).find(o => o.value === value);
            optionEl.classList.toggle('selected', !!optionNode?.selected);
        });
    }

    window.updateCustomSelectUI = updateCustomSelectUI;

    selects.forEach(select => {
        if (select.dataset.customSelectInitialized === 'true') {
            updateCustomSelectUI(select);
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'custom-dropdown-wrapper';
        if (select.multiple) {
            wrapper.classList.add('is-multi');
        }

        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);

        select.style.display = 'none';

        const trigger = document.createElement('div');
        trigger.className = 'custom-dropdown-trigger';

        const triggerLabel = select.multiple
            ? (select.id === 'filter-status-kerja'
                ? getStatusKerjaTriggerText(select)
                : getMultiSelectTriggerText(select))
            : select.options[select.selectedIndex].text;

        trigger.innerHTML =
            `<span>${triggerLabel}</span>
             <div class="arrow"></div>`;

        wrapper.appendChild(trigger);

        const list = document.createElement('div');
        list.className = 'custom-dropdown-options';
        wrapper.appendChild(list);

        Array.from(select.options).forEach(option => {

            const item = document.createElement('div');

            item.className =
                'custom-option' + (option.selected ? ' selected' : '');

            item.dataset.value = option.value;
            const left = document.createElement('span');
            left.className = 'custom-option-left';

            const iconUrl = option.dataset.icon || '';
            if (iconUrl) {
                const img = document.createElement('img');
                img.className = 'custom-option-icon';
                img.src = iconUrl;
                img.alt = '';
                img.setAttribute('aria-hidden', 'true');
                left.appendChild(img);
            }

            const text = document.createElement('span');
            text.className = 'custom-option-text';
            text.textContent = option.text;
            left.appendChild(text);

            item.appendChild(left);

            item.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                if (!select.multiple) {
                    select.value = this.dataset.value;

                    trigger.querySelector('span').textContent =
                        this.textContent;

                    list.querySelectorAll('.custom-option')
                        .forEach(x => x.classList.remove('selected'));

                    this.classList.add('selected');

                    select.dispatchEvent(new Event('change'));
                    return;
                }

                const clickedValue = this.dataset.value;
                const options = Array.from(select.options);

                if (clickedValue === 'semua') {
                    options.forEach(o => { o.selected = (o.value === 'semua'); });
                } else {
                    const semuaOption = options.find(o => o.value === 'semua');
                    if (semuaOption) {
                        semuaOption.selected = false;
                    }

                    const clickedOption = options.find(o => o.value === clickedValue);
                    if (clickedOption) {
                        clickedOption.selected = !clickedOption.selected;
                    }

                    const adaSpesifik = options.some(o => o.value !== 'semua' && o.selected);
                    if (!adaSpesifik) {
                        if (semuaOption) {
                            semuaOption.selected = true;
                        }
                    }
                }

                updateCustomSelectUI(select);
                select.dispatchEvent(new Event('change'));
            });

            list.appendChild(item);
        });

        select.dataset.customSelectInitialized = 'true';

        trigger.addEventListener('click', function (e) {

            e.stopPropagation();

            document.querySelectorAll('.custom-dropdown-options')
                .forEach(x => {
                    if (x !== list) x.classList.remove('open');
                });

            document.querySelectorAll('.custom-dropdown-trigger')
                .forEach(x => {
                    if (x !== trigger) x.classList.remove('active');
                });

            list.classList.toggle('open');
            trigger.classList.toggle('active');
        });
    });

    document.addEventListener('click', function (e) {
        if (e.target && e.target.closest && e.target.closest('.custom-dropdown-wrapper')) {
            return;
        }

        document.querySelectorAll('.custom-dropdown-options')
            .forEach(x => x.classList.remove('open'));

        document.querySelectorAll('.custom-dropdown-trigger')
            .forEach(x => x.classList.remove('active'));
    });
}

window.syncCustomSelectValue = function (selectId, value) {

    const select = document.getElementById(selectId);

    if (!select) {
        return;
    }

    if (select.multiple) {
        const values = Array.isArray(value) ? value : [value];
        const options = Array.from(select.options);

        if (values.includes('semua')) {
            options.forEach(o => { o.selected = (o.value === 'semua'); });
        } else {
            options.forEach(o => { o.selected = values.includes(o.value); });
            const adaSpesifik = options.some(o => o.value !== 'semua' && o.selected);
            if (!adaSpesifik) {
                const semuaOption = options.find(o => o.value === 'semua');
                if (semuaOption) {
                    semuaOption.selected = true;
                }
            }
        }
    } else {
        select.value = value;
    }

    const wrapper = select.closest('.custom-dropdown-wrapper');

    if (wrapper && typeof window.updateCustomSelectUI === 'function') {
        window.updateCustomSelectUI(select);
    } else {
        const trigger = wrapper?.querySelector('.custom-dropdown-trigger span');
        const optionEls = wrapper?.querySelectorAll('.custom-option') || [];

        if (trigger && select.selectedIndex >= 0) {
            trigger.textContent = select.options[select.selectedIndex].text;
        }

        optionEls.forEach(option => {
            option.classList.toggle('selected', option.dataset.value === value);
        });
    }
};

window.resetSemuaFilter = function () {
    const defaultStatusValues = getDefaultStatusFilterValues();

    if (typeof window.syncCustomSelectValue === 'function') {
        window.syncCustomSelectValue('search-category', 'semua');
        window.syncCustomSelectValue('filter-linearitas', 'semua');
        window.syncCustomSelectValue('filter-bidang', 'semua');
        window.syncCustomSelectValue('filter-status-kerja', defaultStatusValues);
        window.syncCustomSelectValue('filter-tahun', 'semua');
        window.syncCustomSelectValue('filter-angkatan', 'semua');
        window.syncCustomSelectValue('filter-wilayah', '');
        window.syncCustomSelectValue('visualization-mode-ui', 'marker');
        window.syncCustomSelectValue('filter-visualization-mode', 'marker');
    } else {
        const ids = [
            'search-category',
            'filter-linearitas',
            'filter-bidang',
            'filter-status-kerja',
            'filter-tahun',
            'filter-angkatan',
            'filter-wilayah',
            'visualization-mode-ui',
            'filter-visualization-mode'
        ];

        ids.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                if (id === 'filter-status-kerja') {
                    Array.from(element.options || []).forEach(function (opt) {
                        opt.selected = defaultStatusValues.includes(opt.value);
                    });
                } else if (id === 'visualization-mode-ui' || id === 'filter-visualization-mode') {
                    element.value = 'marker';
                } else if (id === 'filter-wilayah') {
                    element.value = '';
                } else {
                    element.value = 'semua';
                }
            }
        });
    }

    if (typeof window.setVisualizationMode === 'function') {
        window.setVisualizationMode('marker', { rebuildMarkers: false });
    } else {
        window.visualizationMode = 'marker';
        sinkronkanLegendaModeVisualisasi();
    }

    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.value = '';
    }

    syncSearchClearButtonState();

    if (typeof window.resetHighlightWilayah === 'function') {
        window.resetHighlightWilayah();
    }

    filterDanTampilkanMarker();
    updateActiveFilterUI();
};

// ======================================================
// TAMPILAN PETA
// ======================================================
window.perbaruiTampilanPeta = function () {

    initMarkerGroups();

    const mainNormal = window.mainAlumniLayerGroup;
    const mainCluster = window.mainAlumniClusterGroup;
    const studiNormal = window.studiLanjutLayerGroup;
    const studiCluster = window.studiLanjutClusterGroup;

    [mainNormal, mainCluster, studiNormal, studiCluster].forEach(function (layer) {
        if (layer && map.hasLayer(layer)) {
            map.removeLayer(layer);
        }
    });

    const mode = normalisasiModeVisualisasi(window.visualizationMode || 'marker');
    if (mode !== 'marker') {
        return;
    }

    map.addLayer(window.statusClusterAktif ? mainCluster : mainNormal);

    const studiEnabled = !!window.__studiLanjutEnabled;
    if (studiEnabled) {
        map.addLayer(window.statusClusterAktif ? studiCluster : studiNormal);
    }
};

// ======================================================
// WARNA MARKER
// ======================================================
function getMarkerColor(linearitas) {

    switch (linearitas) {

        case 'Sangat Erat':
            return 'green';

        case 'Erat':
            return 'blue';

        case 'Cukup Erat':
            return 'yellow';

        case 'Kurang Erat':
            return 'orange';

        case 'Tidak Erat':
            return 'red';

        default:
            return 'red';
    }
}

// ======================================================
// BADGE CSS
// ======================================================
function getStatusClass(linearitas) {

    switch (linearitas) {

        case 'Sangat Erat':
            return 'status-sangat';

        case 'Erat':
            return 'status-erat';

        case 'Cukup Erat':
            return 'status-cukup';

        case 'Kurang Erat':
            return 'status-kurang';

        case 'Tidak Erat':
            return 'status-tidak';

        default:
            return 'status-tidak';
    }
}

function bindTooltipDenganDelay(marker, tooltipHtml) {
    if (!marker) {
        return;
    }

    marker.bindTooltip(
        tooltipHtml,
        {
            direction: 'right',
            sticky: false,
            opacity: 0.95,
            offset: [12, 10],
            className: 'alumni-tooltip'
        }
    );

    // Delay tooltip supaya tidak langsung muncul saat kursor lewat marker
    const TOOLTIP_DELAY_MS = 650;
    let tooltipTimer = null;

    // Matikan open/close tooltip bawaan Leaflet agar bisa pakai delay hover.
    if (typeof marker._openTooltip === 'function') {
        marker.off('mouseover', marker._openTooltip, marker);
    }
    if (typeof marker._closeTooltip === 'function') {
        marker.off('mouseout', marker._closeTooltip, marker);
    }

    marker.on('mouseover', function () {
        clearTimeout(tooltipTimer);

        tooltipTimer = setTimeout(function () {
            marker.openTooltip();
        }, TOOLTIP_DELAY_MS);
    });

    marker.on('mouseout', function () {
        clearTimeout(tooltipTimer);
        marker.closeTooltip();
    });
}

function escapeHtml(value) {
    return (value ?? '').toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getLinearitasDescription(linearitas) {
    switch (linearitas) {
        case 'Sangat Erat':
            return 'Pekerjaan sangat sesuai dengan bidang pendidikan alumni.';
        case 'Erat':
            return 'Pekerjaan sesuai dengan bidang pendidikan alumni.';
        case 'Cukup Erat':
            return 'Pekerjaan masih cukup berkaitan dengan bidang pendidikan alumni.';
        case 'Kurang Erat':
            return 'Pekerjaan hanya sedikit berkaitan dengan bidang pendidikan alumni.';
        case 'Tidak Erat':
            return 'Pekerjaan kurang atau tidak berkaitan dengan bidang pendidikan alumni.';
        default:
            return 'Menunjukkan seberapa sesuai bidang pendidikan alumni dengan pekerjaan saat ini.';
    }
}

function renderPopupStatusBadge(statusKerja, linearitas) {
    if (statusKerja === 'Belum Bekerja') {
        return '<span class="popup-badge">Belum Bekerja</span>';
    }

    const label = escapeHtml(linearitas || 'Tidak Erat');
    const description = escapeHtml(getLinearitasDescription(linearitas));

    return `
        <span class="popup-badge popup-badge-linearitas">
            <span>${label}</span>
            <button
                type="button"
                class="linearitas-info-trigger"
                aria-label="Penjelasan kesesuaian bidang"
            >
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <span class="linearitas-info-tooltip" role="tooltip">
                    ${description}
                </span>
            </button>
        </span>
    `;
}

function getCoordinateStackKey(latitude, longitude) {
    const lat = Number(latitude);
    const lng = Number(longitude);

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return '';
    }

    return `${lat.toFixed(7)},${lng.toFixed(7)}`;
}

function getCoordinateStackCounts(entries) {
    return entries.reduce(function (counts, entry) {
        const key = entry.statusKey || 'lainnya';
        counts[key] = (counts[key] || 0) + 1;
        return counts;
    }, {});
}

function getCoordinateStackVariant(entries) {
    const keys = Array.from(new Set(entries.map(entry => entry.statusKey || 'lainnya')));

    if (keys.length === 1) {
        return keys[0].replace(/_/g, '-');
    }

    return 'campuran';
}

function buildCoordinateStackSummary(counts) {
    const parts = [];

    if (counts.bekerja) {
        parts.push(`${counts.bekerja} bekerja`);
    }

    if (counts.belum_bekerja) {
        parts.push(`${counts.belum_bekerja} belum bekerja`);
    }

    if (counts.studi_lanjut) {
        parts.push(`${counts.studi_lanjut} studi lanjut`);
    }

    return parts.length ? parts.join(' | ') : 'Beberapa alumni di titik yang sama';
}

function buildCoordinateStackPopup(entries) {
    const counts = getCoordinateStackCounts(entries);
    const total = entries.length;
    const summary = buildCoordinateStackSummary(counts);

    const rows = entries.map(function (entry) {
        const statusClass = (entry.statusKey || 'lainnya').replace(/_/g, '-');
        const alumniId = escapeHtml(entry.alumniId || '');
        const name = escapeHtml(entry.nama || '-');
        const subtitle = escapeHtml(entry.subtitle || '-');
        const statusLabel = escapeHtml(entry.statusLabel || 'Alumni');
        const angkatan = escapeHtml(entry.angkatan || '-');
        const detailParts = [];

        if (angkatan && angkatan !== '-') {
            detailParts.push(`Angkatan ${angkatan}`);
        }

        if (entry.multiJobCount > 0) {
            detailParts.push(`Multi-job ${entry.multiJobCount}`);
        }

        const detailText = escapeHtml(detailParts.join(' | '));

        const nameEl = entry.alumniId
            ? `<button type="button" class="coordinate-stack-name clickable-profile" data-alumni-id="${alumniId}">${name}</button>`
            : `<div class="coordinate-stack-name coordinate-stack-name--static">${name}</div>`;

        return `
            <div class="coordinate-stack-item">
                <div class="coordinate-stack-item-body">
                    ${nameEl}
                    <div class="coordinate-stack-subtitle">${subtitle}</div>
                    <div class="coordinate-stack-meta">
                        <span class="coordinate-stack-status coordinate-stack-status--${statusClass}">${statusLabel}</span>
                        ${detailText ? `<span class="coordinate-stack-detail">${detailText}</span>` : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');

    return `
        <div class="coordinate-stack-popup">
            <div class="coordinate-stack-header">
                <div class="coordinate-stack-title">${total} alumni di lokasi ini</div>
                <div class="coordinate-stack-summary">${escapeHtml(summary)}</div>
            </div>
            <div class="coordinate-stack-list">
                ${rows}
            </div>
        </div>
    `;
}

function createCoordinateStackMarker(stack) {
    const entries = stack.entries || [];
    const total = entries.length;
    const variant = getCoordinateStackVariant(entries);

    const icon = L.divIcon({
        html: `<div class="coordinate-stack-pin"><span>${total}</span></div>`,
        className: `coordinate-stack-marker coordinate-stack-marker--${variant}`,
        iconSize: [32, 38],
        iconAnchor: [16, 36],
        popupAnchor: [0, -34]
    });

    const marker = L.marker([stack.latitude, stack.longitude], {
        icon,
        zIndexOffset: 700
    });

    bindGuardedPopup(marker, buildCoordinateStackPopup(entries), {
        className: 'coordinate-stack-popup-wrap',
        maxWidth: 330,
        minWidth: 260
    });

    bindTooltipDenganDelay(marker, `${total} alumni di lokasi ini`);

    return marker;
}

function createCoordinateStackManager(targetLayer, options) {
    const stacks = new Map();
    const config = options || {};

    function registerEntryMarker(entry, marker) {
        if (Number.isInteger(entry.index)) {
            arrayMarker[entry.index] = marker;
        }

        if (config.registerMainMarkers && entry.alumniId) {
            window.mainAlumniMarkersById[entry.alumniId] = marker;
        }
    }

    return {
        add: function (entry) {
            if (!entry || !entry.marker || !targetLayer) {
                return;
            }

            const key = getCoordinateStackKey(entry.latitude, entry.longitude);
            if (!key) {
                targetLayer.addLayer(entry.marker);
                registerEntryMarker(entry, entry.marker);
                return;
            }

            if (!stacks.has(key)) {
                stacks.set(key, {
                    latitude: entry.latitude,
                    longitude: entry.longitude,
                    entries: []
                });
            }

            stacks.get(key).entries.push(entry);
        },
        flush: function () {
            stacks.forEach(function (stack) {
                if (!stack.entries.length) {
                    return;
                }

                if (stack.entries.length === 1) {
                    const entry = stack.entries[0];
                    targetLayer.addLayer(entry.marker);
                    registerEntryMarker(entry, entry.marker);
                    return;
                }

                const stackMarker = createCoordinateStackMarker(stack);
                targetLayer.addLayer(stackMarker);

                stack.entries.forEach(function (entry) {
                    registerEntryMarker(entry, stackMarker);
                });
            });
        }
    };
}

// ======================================================
// FILTER UTAMA
// ======================================================
function filterDanTampilkanMarker() {

    sinkronkanFilterWilayahPeta({ flyTo: false });
    updateActiveFilterUI();

    if ((window.mapDataUrl || window.__MAP_MARKER_ENDPOINT__) && !window.__RENDERING_FETCHED_MARKERS__) {
        fetchMapMarkersAndRender();
        return;
    }

    initMarkerGroups();
    initMultiJobStorage();

    const visualizationMode = normalisasiModeVisualisasi(window.visualizationMode || 'marker');
    const markerMode = visualizationMode === 'marker';

    const choroplethStats = {};
    const heatBuckets = new Map();
    const heatSeenMain = new Set();
    const heatSeenStudi = new Set();
    const seenRegionTotal = new Set();
    const seenRegionCategory = new Set();

    function ensureRegionStats(key) {
        if (!key) {
            return null;
        }

        if (!choroplethStats[key]) {
            choroplethStats[key] = {
                total: 0,
                bekerja: 0,
                belum_bekerja: 0,
                studi_lanjut: 0
            };
        }

        return choroplethStats[key];
    }

    function getWilayahKeyDariRow(row) {
        const fromPayload = (row && row.wilayah_key) ? row.wilayah_key.toString().trim() : '';

        const getKey = (value) => {
            if (typeof window.getKeyWilayah === 'function') {
                return window.getKeyWilayah(value);
            }

            if (typeof getKeyWilayah === 'function') {
                return getKeyWilayah(value);
            }

            return (value || '').toString().trim().toLowerCase();
        };

        const knownKeys = Object.keys(window.wilayahRegistry || {});
        const knownFallback = Object.keys(window.wilayahConfig || {});
        const allKeys = knownKeys.length ? knownKeys : knownFallback;
        const knownKeySet = new Set(allKeys);

        const tryExactKey = (raw) => {
            const key = getKey(raw);
            if (key && knownKeySet.has(key)) {
                return key;
            }
            return '';
        };

        const resolveFromText = (text) => {
            const t = (text || '').toString();
            if (!t || !allKeys.length) {
                return '';
            }

            let best = '';
            let bestLen = 0;

            allKeys.forEach(function (k) {
                if (!k) return;

                // Cek frasa utuh agar "banjar" tidak match ke "banjarbaru"
                if (cocokFrasaWilayah(t, k)) {
                    const len = k.length;
                    if (len > bestLen) {
                        best = k;
                        bestLen = len;
                    }
                }
            });

            return best;
        };

        if (fromPayload) {
            const exact = tryExactKey(fromPayload);
            if (exact) {
                return exact;
            }
        }

        const kota = (row && (row.kota || row.kota_kampus || '')) ? (row.kota || row.kota_kampus || '') : '';
        if (kota) {
            const exact = tryExactKey(kota);
            if (exact) {
                return exact;
            }
        }

        const alamat = (row && (row.alamat || row.alamat_kampus || row.alamat_lengkap || ''))
            ? (row.alamat || row.alamat_kampus || row.alamat_lengkap || '')
            : '';

        const provinsi = (row && (row.provinsi || row.provinsi_kampus || ''))
            ? (row.provinsi || row.provinsi_kampus || '')
            : '';

        const teks = [kota, alamat, provinsi, fromPayload].filter(Boolean).join(' ');
        const match = resolveFromText(teks);
        if (match) {
            if (window.__DEBUG_CHOROPLETH) {
                try {
                    console.log('Choropleth region resolved:', { kota, alamat, provinsi, fromPayload, match });
                } catch (_) { }
            }
            return match;
        }

        // Fallback: tetap kembalikan key hasil normalisasi agar tidak blank.
        return getKey(kota || fromPayload || provinsi);
    }

    function getWilayahKeyDariKoordinat(lat, lng) {
        const a = Number(lat);
        const b = Number(lng);
        if (!Number.isFinite(a) || !Number.isFinite(b)) {
            return '';
        }

        const registry = window.wilayahRegistry || {};
        const keys = Object.keys(registry);
        if (!keys.length) {
            return '';
        }

        const extractPolygons = (geometry) => {
            const type = geometry?.type || '';
            const coords = geometry?.coordinates;
            if (!Array.isArray(coords)) {
                return [];
            }

            if (type === 'Polygon') {
                return [coords];
            }

            if (type === 'MultiPolygon') {
                return coords;
            }

            return [];
        };

        const pointInRing = (x, y, ring) => {
            if (!Array.isArray(ring) || ring.length < 3) {
                return false;
            }

            let inside = false;
            for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
                const xi = Number(ring[i]?.[0]);
                const yi = Number(ring[i]?.[1]);
                const xj = Number(ring[j]?.[0]);
                const yj = Number(ring[j]?.[1]);

                if (!Number.isFinite(xi) || !Number.isFinite(yi) || !Number.isFinite(xj) || !Number.isFinite(yj)) {
                    continue;
                }

                const intersect =
                    ((yi > y) !== (yj > y)) &&
                    (x < ((xj - xi) * (y - yi)) / (yj - yi) + xi);

                if (intersect) {
                    inside = !inside;
                }
            }
            return inside;
        };

        const pointInPolygonRings = (x, y, rings) => {
            if (!Array.isArray(rings) || rings.length === 0) {
                return false;
            }

            if (!pointInRing(x, y, rings[0])) {
                return false;
            }

            for (let i = 1; i < rings.length; i++) {
                if (pointInRing(x, y, rings[i])) {
                    return false;
                }
            }

            return true;
        };

        const x = b;
        const y = a;

        for (let idx = 0; idx < keys.length; idx++) {
            const key = keys[idx];
            const layer = registry[key]?.layer;
            if (layer && typeof layer.getBounds === 'function') {
                try {
                    const leafletBounds = layer.getBounds();
                    if (leafletBounds && typeof leafletBounds.contains === 'function') {
                        if (!leafletBounds.contains([y, x])) {
                            continue;
                        }
                    }
                } catch (_) { }
            }
            const geom = layer?.feature?.geometry;
            const polygons = extractPolygons(geom);
            if (!polygons.length) {
                continue;
            }

            // Quick bounds check jika tersedia (lebih cepat sebelum point-in-polygon).
            const bounds = registry[key]?.bounds;
            if (bounds && Number.isFinite(bounds.minLat)) {
                if (y < bounds.minLat || y > bounds.maxLat || x < bounds.minLng || x > bounds.maxLng) {
                    continue;
                }
            }

            for (let p = 0; p < polygons.length; p++) {
                if (pointInPolygonRings(x, y, polygons[p])) {
                    return key;
                }
            }
        }

        return '';
    }

    function tambahStatsChoropleth(wilayahKey, alumniUniqueId, kategoriKey) {
        const stats = ensureRegionStats(wilayahKey);
        if (!stats) {
            return;
        }

        const unique = (alumniUniqueId || '').toString();
        if (!unique) {
            return;
        }

        const totalKey = wilayahKey + '|' + unique;
        if (!seenRegionTotal.has(totalKey)) {
            seenRegionTotal.add(totalKey);
            stats.total += 1;
        }

        const cat = (kategoriKey || '').toString();
        if (!cat || !(cat in stats)) {
            return;
        }

        const catKey = wilayahKey + '|' + cat + '|' + unique;
        if (!seenRegionCategory.has(catKey)) {
            seenRegionCategory.add(catKey);
            stats[cat] += 1;
        }
    }

    function heatBucketKey(lat, lng) {
        const a = Number(lat);
        const b = Number(lng);
        if (!Number.isFinite(a) || !Number.isFinite(b)) {
            return '';
        }

        return a.toFixed(5) + ',' + b.toFixed(5);
    }

    function tambahHeatPoint(lat, lng, increment) {
        const key = heatBucketKey(lat, lng);
        if (!key) {
            return;
        }

        const inc = Number(increment) || 1;

        if (!heatBuckets.has(key)) {
            heatBuckets.set(key, { lat: Number(lat), lng: Number(lng), count: 0 });
        }

        const current = heatBuckets.get(key);
        current.count += inc;
        heatBuckets.set(key, current);
    }

    function normalisasiTeksWilayah(teks) {
        let text = (teks || '')
            .toString()
            .toLowerCase()
            .trim();

        // Buang tanda baca agar "Banjar, KalSel" tetap match ke "banjar".
        text = text.replace(/[^a-z0-9]+/gi, ' ');
        text = text.replace(/\s+/g, ' ').trim();

        // Alias umum yang sering muncul sebelum prefix stripping
        if (text === 'kota baru') return 'kotabaru';
        if (text === 'banjar baru') return 'banjarbaru';

        text = text
            .replace(/^kabupaten\s+/i, '')
            .replace(/^kab\s+/i, '')
            .replace(/^kota\s+/i, '')
            .trim();

        return text;
    }

    function escapeRegex(teks) {
        return (teks || '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function cocokFrasaWilayah(teks, frasa) {
        const t = normalisasiTeksWilayah(teks);
        const f = normalisasiTeksWilayah(frasa);

        if (t === '' || f === '') {
            return false;
        }

        // Cocokkan sebagai frasa utuh (menghindari "banjar" ikut cocok ke "banjarbaru")
        const re = new RegExp('(^|\\s)' + escapeRegex(f) + '($|\\s)');
        return re.test(t);
    }

    const rawKeyword =
        window.__SEARCH_KEYWORD_OVERRIDE__ !== undefined && window.__SEARCH_KEYWORD_OVERRIDE__ !== null
            ? String(window.__SEARCH_KEYWORD_OVERRIDE__)
            : (document.getElementById('search-input')?.value ?? '');

    const keywordTrimmed = rawKeyword.toString().trim().toLowerCase();
    // Minimal 2 karakter untuk benar-benar menjalankan pencarian (panel tetap bisa menampilkan helper).
    const keyword = keywordTrimmed.length >= 2 ? keywordTrimmed : '';

    const scopes = getCariBerdasarkanScopes();

    const linearitasFilter =
        document.getElementById('filter-linearitas')
            ?.value || 'semua';

    const bidangSelect =
        document.getElementById('filter-bidang');

    const bidangFilters = bidangSelect
        ? Array.from(bidangSelect.selectedOptions || []).map(o => o.value)
        : ['semua'];

    const bidangFilterIsSemua =
        bidangFilters.length === 0 || bidangFilters.includes('semua');

    const statusSelect =
        document.getElementById('filter-status-kerja');

    const statusFilters = statusSelect
        ? Array.from(statusSelect.selectedOptions || []).map(o => o.value)
        : ['semua'];

    const statusFilterIsSemua =
        statusFilters.length === 0 || statusFilters.includes('semua');

    const defaultStatusValues = getDefaultStatusFilterValues();
    const cleanStatusFilters = statusFilters.filter(v => v !== 'semua');
    const statusFilterIsDefault =
        !statusFilterIsSemua &&
        defaultStatusValues.every(value => statusFilters.includes(value)) &&
        !statusFilters.includes('studi_lanjut') &&
        cleanStatusFilters.length === defaultStatusValues.length;

    const tahunFilter =
        document.getElementById('filter-tahun')
            ?.value || 'semua';

    const angkatanFilter =
        document.getElementById('filter-angkatan')
            ?.value || 'semua';

    // Clear semua layer supaya tidak ada marker duplikat
    window.mainAlumniLayerGroup.clearLayers();
    window.mainAlumniClusterGroup.clearLayers();
    window.studiLanjutLayerGroup.clearLayers();
    window.studiLanjutClusterGroup.clearLayers();

    arrayMarker = [];
    clearMultiJobLayers();

    let hasilHTML = '';
    let jumlah = 0;
    const alumniIdsDisplayed = new Set();
    const alumniIdsBekerja = new Set();
    const alumniIdsBelumBekerja = new Set();
    const alumniIdsStudiLanjut = new Set();
    const alumniIdsStudiLanjutMatched = new Set();
    const multiJobAlumniIds = new Set();

    const mainTarget = window.statusClusterAktif
        ? window.mainAlumniClusterGroup
        : window.mainAlumniLayerGroup;
    const gunakanStackKoordinat = markerMode && !window.statusClusterAktif;
    const mainCoordinateStack = gunakanStackKoordinat
        ? createCoordinateStackManager(mainTarget, { registerMainMarkers: true })
        : null;

    const isDefaultState =
        keyword === '' &&
        linearitasFilter === 'semua' &&
        bidangFilterIsSemua &&
        statusFilterIsDefault &&
        tahunFilter === 'semua' &&
        angkatanFilter === 'semua' &&
        scopes.isSemua;

    alumniData.forEach(function (item, index) {

        const alumniId = (item.alumni_id ?? item.id ?? '').toString();
        if (alumniId) {
            window.alumniDataById[alumniId] = item;
        }

        const nama = item.nama || '';
        const perusahaan = item.perusahaan || '-';
        const jabatan = item.jabatan || '-';
        const bidang = (item.bidang || '').trim();
        const tahunLulus = item.tahun_lulus || '-';
        const angkatan = String(item.angkatan || '').trim();
        const statusKerja = item.status || 'Belum Bekerja';
        const linearitas = item.linearitas || 'Tidak Erat';

        let latitude = null;
        let longitude = null;
        let alamatLengkap = '';

        // =====================================
        // Tentukan sumber lokasi
        // =====================================
        if (statusKerja === 'Bekerja') {

            latitude = parseFloat(item.latitude);
            longitude = parseFloat(item.longitude);
            alamatLengkap = item.alamat || '';
        } else {

            latitude = parseFloat(item.latitude);
            longitude = parseFloat(item.longitude);
            alamatLengkap = item.alamat || '';
        }

        if (!latitude || !longitude) return;

        // =====================================
        // FILTER
        // =====================================
        let cocokKeyword = true;

        if (keyword !== '') {

            const n = nama.toLowerCase();
            const nim = (item.nim || '').toString().toLowerCase();
            const p = perusahaan.toLowerCase();

            const cocokNama = scopes.nama && n.includes(keyword);
            const cocokNim = scopes.nim && nim.includes(keyword);
            const cocokPerusahaan = scopes.perusahaan && p.includes(keyword);

            cocokKeyword = cocokNama || cocokNim || cocokPerusahaan;
        }

        const cocokLinearitas =
            linearitasFilter === 'semua' ||
            linearitas === linearitasFilter;

        const cocokBidang =
            bidangFilterIsSemua ||
            bidangFilters.includes(bidang);

        const statusKey =
            statusKerja === 'Belum Bekerja'
                ? 'belum_bekerja'
                : 'bekerja';

        const cocokStatusKerja =
            statusFilterIsSemua ||
            statusFilters.includes(statusKey);

        let cocokTahun = true;
        const cocokAngkatan =
            angkatanFilter === 'semua' ||
            angkatan === angkatanFilter;

        if (tahunFilter !== 'semua') {

            const selisih =
                new Date().getFullYear() -
                parseInt(tahunLulus);

            cocokTahun =
                selisih >= 0 &&
                selisih <= parseInt(tahunFilter);
        }

        if (
            !cocokKeyword ||
            !cocokLinearitas ||
            !cocokBidang ||
            !cocokStatusKerja ||
            !cocokTahun ||
            !cocokAngkatan
        ) return;

        const alumniUniqueId = alumniId
            ? ('alumni:' + alumniId)
            : ('point:' + latitude + ',' + longitude);

        const wilayahKey = getWilayahKeyDariRow(item);
        tambahStatsChoropleth(wilayahKey, alumniUniqueId, statusKey);

        if (!heatSeenMain.has(alumniUniqueId)) {
            heatSeenMain.add(alumniUniqueId);
            tambahHeatPoint(latitude, longitude, 1);
        }

        if (!markerMode) {
            arrayMarker[index] = { latitude, longitude };
        } else {
        const icon = L.icon({
            iconUrl: statusKerja === 'Belum Bekerja'
                ? '/img/icon alumni nganggur.png'
                : '/img/icon alumni kerja.png',
            // iconUrl: statusKerja === 'Belum Bekerja'
            //     ? 'https://jmogfydhlafcuoknkcrg.supabase.co/storage/v1/object/public/alumni/icon%20alumni%20nganggur.png'
            //     : 'https://jmogfydhlafcuoknkcrg.supabase.co/storage/v1/object/public/alumni/icon%20alumni%20kerja.png',
            iconSize: [24, 38],
            iconAnchor: [12, 38],
            popupAnchor: [0, -42]
        });

        const marker =
            L.marker([latitude, longitude], { icon });

        if (alumniId) {
            window.mainAlumniMarkersById[alumniId] = marker;
        }

        const avatar =
            'https://ui-avatars.com/api/?name=' +
            encodeURIComponent(nama) +
            '&background=004a87&color=fff&size=60&rounded=true';

        const infoKerja =
            statusKerja === 'Belum Bekerja'
                ?
                `
                <div class="info-row">
                    <span class="icon">\u{1F3E0}</span>
                    <span>Domisili Saat Ini</span>
                </div>

                <div class="info-row">
                    <span class="icon">\u{1F4CC}</span>
                    <span>${alamatLengkap}</span>
                </div>
                `
                :
                `
                <div class="info-row">
                    <span class="icon">\u{1F3E2}</span>
                    <span><b>${perusahaan}</b></span>
                </div>

                <div class="info-row">
                    <span class="icon">\u{1F4BC}</span>
                    <span>${jabatan}</span>
                </div>
                `;

        const pekerjaanLainnya = Array.isArray(item.pekerjaan_lainnya) ? item.pekerjaan_lainnya : [];
        const multiJobMappableCount = pekerjaanLainnya.length;
        const multiJobTotalCount = statusKerja === 'Belum Bekerja'
            ? 0
            : Math.max(Number(item.multi_job_count ?? multiJobMappableCount) || 0, multiJobMappableCount);

        if (multiJobTotalCount > 0 && alumniId) {
            multiJobAlumniIds.add(alumniId);
        }

        const multiJobButton =
            multiJobMappableCount > 0 && alumniId
                ? `
                    <button
                        type="button"
                        class="multi-job-toggle"
                        data-alumni-id="${alumniId}"
                        title="Tampilkan pekerjaan sampingan"
                        style="border:none;background:#ede9fe;color:#5b21b6;border:1px solid #c4b5fd;border-radius:10px;padding:6px 10px;font-size:11px;font-weight:900;cursor:pointer;display:inline-flex;align-items:center;gap:6px;"
                    >
                        MULTI-JOB ${multiJobTotalCount}
                    </button>
                `
                : multiJobTotalCount > 0 && alumniId
                    ? `
                        <span
                            class="popup-badge"
                            title="Lokasi pekerjaan sampingan belum tersedia"
                            style="background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;"
                        >
                            MULTI-JOB ${multiJobTotalCount}
                        </span>
                    `
                : '';

        const popup = `
            <div class="premium-popup">

                <div class="popup-cover"></div>

                <div class="popup-avatar">
                    <img src="${avatar}">
                </div>

                <div class="popup-body">

                    <h3 class="popup-name clickable-profile" data-alumni-id="${alumniId}">${nama}</h3>

                    <span class="popup-year">
                        Angkatan ${angkatan || '-'}
                    </span>

                    <div class="popup-info">
                        ${infoKerja}
                    </div>

                    <div class="popup-footer">
                        ${renderPopupStatusBadge(statusKerja, linearitas)}
                        ${multiJobButton}
                    </div>

                </div>
            </div>
        `;

        bindGuardedPopup(marker, popup);
        const tooltipTempat =
            statusKerja === 'Belum Bekerja'
                ? 'Belum Bekerja'
                : (perusahaan && perusahaan.trim() ? perusahaan : 'Tempat kerja belum diisi');

        bindTooltipDenganDelay(marker, `${nama} - ${tooltipTempat}`);

        const markerEntry = {
            marker,
            index,
            alumniId,
            latitude,
            longitude,
            nama,
            tahunLulus,
            angkatan,
            statusKey,
            statusLabel: statusKerja === 'Belum Bekerja' ? 'Belum Bekerja' : 'Bekerja',
            subtitle: statusKerja === 'Belum Bekerja'
                ? `Domisili: ${alamatLengkap || '-'}`
                : `${perusahaan || '-'} - ${jabatan || '-'}`,
            meta: statusKerja === 'Belum Bekerja'
                ? ''
                : `Linearitas: ${linearitas || '-'}`,
            multiJobCount: multiJobTotalCount
        };

        if (mainCoordinateStack) {
            mainCoordinateStack.add(markerEntry);
        } else {
            mainTarget.addLayer(marker);
            arrayMarker[index] = marker;
        }
        }

        if (alumniId) {
            alumniIdsDisplayed.add(alumniId);
        }

        if (statusKerja === 'Belum Bekerja') {
            if (alumniId) alumniIdsBelumBekerja.add(alumniId);
        } else {
            if (alumniId) alumniIdsBekerja.add(alumniId);
        }

        // =====================================
        // SIDEBAR
        // =====================================
        if (!isDefaultState) {

            const statusClass =
                getStatusClass(linearitas);

            hasilHTML += `
                <div class="result-card"
                     onclick="terbangKeLokasi(${index})">

                    <div class="result-name">
                        ${nama}
                        <span style="font-size:11px;color:#94a3b8;">
                            (${tahunLulus})
                        </span>
                    </div>

                    <div class="result-job">
                        ${
                            statusKerja === 'Belum Bekerja'
                            ? '\u{1F3E0} Belum Bekerja'
                            : '\u{1F3E2} ' + perusahaan
                        }
                    </div>

                    <div class="result-status ${statusClass}">
                        ${
                            statusKerja === 'Belum Bekerja'
                            ? 'Belum Bekerja'
                            : linearitas
                        }
                    </div>

                </div>
            `;

            jumlah++;
        }

    });

    if (mainCoordinateStack) {
        mainCoordinateStack.flush();
    }

    const studiLanjutData =
        (window.mapPayload && Array.isArray(window.mapPayload.studi_lanjut_markers) && window.mapPayload.studi_lanjut_markers) ||
        (Array.isArray(window.studiLanjutData) && window.studiLanjutData) ||
        [];

    const tampilkanStudiLanjut =
        statusFilterIsSemua || statusFilters.includes('studi_lanjut');

    window.__studiLanjutEnabled = !!tampilkanStudiLanjut;

    const studiTarget = window.statusClusterAktif
        ? window.studiLanjutClusterGroup
        : window.studiLanjutLayerGroup;
    const studiCoordinateStack = gunakanStackKoordinat
        ? createCoordinateStackManager(studiTarget, { registerMainMarkers: false })
        : null;

    if (Array.isArray(studiLanjutData)) {
        studiLanjutData.forEach(function (row) {
            const alumniId = (row.alumni_id ?? '').toString();
            const nama = row.nama_lengkap || row.nama || '';

            const tahunLulus = row.tahun_lulus_alumni ?? row.tahun_lulus ?? '-';
            const angkatan = String(row.angkatan || '').trim();

            const kampusRaw = (row.kampus || '').trim();
            const kampus = kampusRaw ? kampusRaw : 'Kampus tidak diketahui';

            const jenjangRaw = (row.jenjang || '').trim();
            const jenjang = jenjangRaw ? jenjangRaw : 'Jenjang tidak diketahui';

            const prodiRaw = (row.program_studi || '').trim();
            const programStudi = prodiRaw ? prodiRaw : 'Program studi tidak diketahui';

            const statusStudiRaw = (row.status || '').trim();
            const statusStudi = statusStudiRaw ? statusStudiRaw : 'Status tidak diketahui';

            const tahunMasuk = row.tahun_masuk ?? null;
            const tahunLulusStudi = row.tahun_lulus_studi ?? row.tahun_lulus ?? null;

            const latitude = parseFloat(row.latitude);
            const longitude = parseFloat(row.longitude);

            if (!latitude || !longitude) {
                return;
            }

            let cocokKeyword = true;
            if (keyword !== '') {
                const n = (nama || '').toLowerCase();
                const nim = (row.nim || '').toString().toLowerCase();

                const teksInstansi = [
                    kampus || '',
                    jenjang || '',
                    programStudi || ''
                ].join(' ').toLowerCase();

                const cocokNama = scopes.nama && n.includes(keyword);
                const cocokNim = scopes.nim && nim.includes(keyword);
                const cocokInstansi = scopes.perusahaan && teksInstansi.includes(keyword);

                cocokKeyword = cocokNama || cocokNim || cocokInstansi;
            }

            let cocokTahun = true;
            if (tahunFilter !== 'semua') {
                const selisih =
                    new Date().getFullYear() -
                    parseInt(tahunLulus);

                cocokTahun =
                    selisih >= 0 &&
                    selisih <= parseInt(tahunFilter);
            }

            const cocokAngkatan =
                angkatanFilter === 'semua' ||
                angkatan === angkatanFilter;

            if (!cocokKeyword || !cocokTahun || !cocokAngkatan) {
                return;
            }

            if (alumniId) {
                alumniIdsStudiLanjutMatched.add(alumniId);
            }

            if (!tampilkanStudiLanjut) {
                return;
            }

            const studiUniqueId = alumniId
                ? ('alumni:' + alumniId)
                : ('studi:' + latitude + ',' + longitude);

            const wilayahKeyFromCoord = getWilayahKeyDariKoordinat(latitude, longitude);
            const wilayahKeyFromRow = getWilayahKeyDariRow(row);
            const wilayahKey = wilayahKeyFromCoord || wilayahKeyFromRow;

            if (window.__DEBUG_CHOROPLETH && wilayahKeyFromCoord && wilayahKeyFromRow && wilayahKeyFromCoord !== wilayahKeyFromRow) {
                try {
                    console.log('Studi lanjut wilayah mismatch:', { alumniId, wilayahKeyFromCoord, wilayahKeyFromRow, row });
                } catch (_) { }
            }
            tambahStatsChoropleth(wilayahKey, studiUniqueId, 'studi_lanjut');

            if (!heatSeenStudi.has(studiUniqueId)) {
                heatSeenStudi.add(studiUniqueId);
                tambahHeatPoint(latitude, longitude, 1);
            }

            if (!markerMode) {
                // Mode selain marker: tidak membuat marker Leaflet (tetap hitung choropleth/heatmap).
            } else {
            const icon = L.icon({
                iconUrl: '/img/Icon studi lanjut.png',
                // iconUrl: 'https://jmogfydhlafcuoknkcrg.supabase.co/storage/v1/object/public/alumni/Icon%20studi%20lanjut.png',
                iconSize: [24, 38],
                iconAnchor: [12, 38],
                popupAnchor: [0, -42]
            });

            const marker = L.marker([latitude, longitude], { icon });

            const avatar =
                'https://ui-avatars.com/api/?name=' +
                encodeURIComponent(nama) +
                '&background=004a87&color=fff&size=60&rounded=true';

            const lokasiKampus = [row.kota_kampus, row.provinsi_kampus]
                .filter(x => (x || '').toString().trim() !== '')
                .join(', ');

            const periode = `${tahunMasuk ?? '-'} - ${tahunLulusStudi ? tahunLulusStudi : 'Sekarang'}`;

            const popup = `
                <div class="premium-popup">
                    <div class="popup-cover"></div>

                    <div class="popup-avatar">
                        <img src="${avatar}">
                    </div>

                    <div class="popup-body">
                        <h3 class="popup-name clickable-profile" data-alumni-id="${alumniId}">${nama}</h3>

                        <span class="popup-year">
                            Angkatan ${angkatan || '-'}
                        </span>

                        <div class="popup-info">
                            <div class="info-row">
                                <span class="icon">\u{1F393}</span>
                                <span><b>${kampus}</b></span>
                            </div>
                            <div class="info-row">
                                <span class="icon">\u{1F4DA}</span>
                                <span>${jenjang} - ${programStudi}</span>
                            </div>
                            <div class="info-row">
                                <span class="icon">\u{2139}\u{FE0F}</span>
                                <span>Status: ${statusStudi}</span>
                            </div>
                            <div class="info-row">
                                <span class="icon">\u{1F5D3}\u{FE0F}</span>
                                <span>Periode: ${periode}</span>
                            </div>
                            ${lokasiKampus ? `
                                <div class="info-row">
                                    <span class="icon">\u{1F4CD}</span>
                                    <span>${lokasiKampus}</span>
                                </div>
                            ` : ''}
                        </div>

                        <div class="popup-footer">
                            <span class="popup-badge">Studi Lanjut</span>
                        </div>
                    </div>
                </div>
            `;

            bindGuardedPopup(marker, popup);

            bindTooltipDenganDelay(marker, `
                <div><b>${nama}</b></div>
                <div>Studi Lanjut</div>
                <div>${kampus}</div>
                <div>${jenjang} - ${programStudi}</div>
            `);

            const studiEntry = {
                marker,
                index: null,
                alumniId,
                latitude,
                longitude,
                nama,
                tahunLulus,
                angkatan,
                statusKey: 'studi_lanjut',
                statusLabel: 'Studi Lanjut',
                subtitle: `${kampus} - ${jenjang}`,
                meta: programStudi,
                multiJobCount: 0
            };

            if (studiCoordinateStack) {
                studiCoordinateStack.add(studiEntry);
            } else {
                studiTarget.addLayer(marker);
            }
            }

            if (alumniId) {
                alumniIdsDisplayed.add(alumniId);
                alumniIdsStudiLanjut.add(alumniId);
            }
        });
    }

    if (studiCoordinateStack) {
        studiCoordinateStack.flush();
    }

    const heatmapPoints = Array.from(heatBuckets.values()).map(function (item) {
        return [item.lat, item.lng, item.count || 1];
    });

    window.__choroplethStats = choroplethStats;
    window.__heatmapPoints = heatmapPoints;

    if (window.__DEBUG_CHOROPLETH) {
        try {
            console.log('Choropleth counts:', choroplethStats);
        } catch (_) { }
    }

    if (typeof window.refreshWilayahStyle === 'function') {
        window.refreshWilayahStyle();
    }

    if (visualizationMode === 'heatmap' && typeof window.updateHeatmapLayer === 'function') {
        window.updateHeatmapLayer(heatmapPoints);
    }

    if (typeof window.updateChoroplethLegend === 'function') {
        window.updateChoroplethLegend();
    }

    const gunakanHitunganPencarianLokal = keyword !== '';
    perbaruiLegendaStatus(
        gunakanHitunganPencarianLokal
            ? alumniIdsDisplayed.size
            : getMapPayloadCount('total_alumni', alumniIdsDisplayed.size),
        gunakanHitunganPencarianLokal
            ? alumniIdsBekerja.size
            : getMapPayloadCount('total_bekerja', alumniIdsBekerja.size),
        gunakanHitunganPencarianLokal
            ? alumniIdsBelumBekerja.size
            : getMapPayloadCount('total_belum_bekerja', alumniIdsBelumBekerja.size),
        gunakanHitunganPencarianLokal
            ? multiJobAlumniIds.size
            : getMapPayloadCount('total_multi_job', multiJobAlumniIds.size),
        gunakanHitunganPencarianLokal
            ? alumniIdsStudiLanjutMatched.size
            : getMapPayloadCount('total_studi_lanjut', alumniIdsStudiLanjutMatched.size)
    );
    updateActiveFilterUI();
    window.perbaruiTampilanPeta();

    const container =
        document.getElementById('search-results');

    if (!container) return;

    // UI panel hasil pencarian:
    // - keyword kosong: tutup panel (jangan render semua alumni)
    // - keyword 1 char: helper
    // - keyword >=2: tampilkan hasil / empty state
    if (keywordTrimmed.length === 0) {
        container.innerHTML = '';
        container.classList.add('is-hidden');
    } else if (keywordTrimmed.length < 2) {
        container.classList.remove('is-hidden');
        container.innerHTML = `<div class="result-helper">Ketik minimal 2 huruf untuk mencari nama, NIM, atau tempat kerja.</div>`;
    } else if (jumlah > 0) {
        container.classList.remove('is-hidden');
        container.innerHTML =
            `<div class="result-count">
                Ditemukan ${jumlah} Alumni
             </div>` + hasilHTML;
    } else {
        container.classList.remove('is-hidden');
        container.innerHTML =
            `<div class="result-empty">
                Tidak ada alumni yang cocok.
             </div>`;
    }
}

window.filterDanTampilkanMarker = filterDanTampilkanMarker;

function perbaruiLegendaStatus(jumlahTotalAlumni, jumlahBekerja, jumlahBelumBekerja, jumlahMultiJob, jumlahStudiLanjut) {

    const bekerjaEl = document.getElementById('legend-bekerja-count');
    const belumEl = document.getElementById('legend-belum-count');
    const totalEl = document.getElementById('legend-total-count');
    const multiJobEl = document.getElementById('legend-multijob-count');
    const studiEl = document.getElementById('legend-studi-count');
    const belumItemEl = belumEl?.closest('.status-legend-item');
    const canViewSensitiveStatus = canViewBelumBekerja();

    if (bekerjaEl) bekerjaEl.textContent = `(${jumlahBekerja} orang)`;
    if (belumEl) {
        belumEl.textContent = canViewSensitiveStatus
            ? `(${jumlahBelumBekerja} orang)`
            : 'Login diperlukan';
    }
    if (belumItemEl) {
        belumItemEl.classList.toggle('status-legend-item--locked', !canViewSensitiveStatus);
    }
    if (totalEl) totalEl.textContent = `${jumlahTotalAlumni} orang`;
    if (multiJobEl) multiJobEl.textContent = `(${jumlahMultiJob ?? 0} orang)`;
    if (studiEl) studiEl.textContent = `(${jumlahStudiLanjut ?? 0} orang)`;
}

// ======================================================
// FLY TO MARKER
// ======================================================
function terbangKeLokasi(index) {

    const item = arrayMarker[index];

    if (!item) return;

    if (typeof item.getLatLng === 'function') {
        const posisi = item.getLatLng();

        map.flyTo(posisi, 16, {
            animate: true,
            duration: 1.5
        });

        setTimeout(function () {
            item.openPopup();
        }, 350);

        return;
    }

    const latitude = parseFloat(item.latitude);
    const longitude = parseFloat(item.longitude);

    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
        return;
    }

    map.flyTo([latitude, longitude], 16, {
        animate: true,
        duration: 1.5
    });
}
