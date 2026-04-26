// 1. Inisialisasi Peta & Base Map
const defaultCenter = [-3.316694, 114.590111];
const defaultZoom = 8; // Zoom saya ubah ke 8 agar Kalsel terlihat utuh

var map = L.map("map", {
    zoomControl: false
}).setView(defaultCenter, defaultZoom);

function getLeftUiOverlayOffsetX() {
    if (window.matchMedia && window.matchMedia('(max-width: 768px)').matches) {
        return 0;
    }

    const filterPanel = document.querySelector('.filter-panel');
    if (!filterPanel) {
        return 0;
    }

    const style = window.getComputedStyle(filterPanel);
    if (style.display === 'none' || style.visibility === 'hidden') {
        return 0;
    }

    const rect = filterPanel.getBoundingClientRect();
    if (!rect.width || rect.width < 80) {
        return 0;
    }

    return Math.round(Math.min(rect.width, window.innerWidth) / 2);
}

function applyDefaultViewOffset() {
    const offsetX = getLeftUiOverlayOffsetX();
    if (!offsetX) {
        return;
    }

    map.panBy([offsetX, 0], { animate: false });
}

function resetMapView() {
    map.setView(defaultCenter, defaultZoom, { animate: false });
    applyDefaultViewOffset();
}

map.whenReady(function () {
    applyDefaultViewOffset();
});

// Pindahkan tombol zoom ke pojok kanan bawah
L.control.zoom({
    position: 'topright'
}).addTo(map);

// Tombol Reset Peta (kembali ke posisi & zoom awal)
const ResetControl = L.Control.extend({
    options: { position: 'topright' },
    onAdd: function () {
        const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-reset');
        const link = L.DomUtil.create('a', 'leaflet-control-reset-link', container);

        link.href = '#';
        link.title = 'Reset Peta';
        link.setAttribute('role', 'button');
        link.setAttribute('aria-label', 'Reset Peta');
        link.innerHTML = '⟲';

        L.DomEvent.disableClickPropagation(container);
        L.DomEvent.on(link, 'click', function (e) {
            L.DomEvent.preventDefault(e);
            resetMapView();
        });

        return container;
    }
});

new ResetControl().addTo(map);

// Tambahkan graphic scale bar (leaflet-betterscale) untuk referensi jarak
const betterScaleFactory = (L.control && (L.control.betterscale || L.control.betterScale)) || null;
const scaleControlFactory = betterScaleFactory || L.control.scale;

window.scaleBarControl = scaleControlFactory({
    position: 'bottomleft',
    metric: true,
    imperial: false,
    maxWidth: 150,
    updateWhenIdle: true
}).addTo(map);

// Tambahkan mini map (overview map) pakai Leaflet.MiniMap
// Diletakkan setelah scale bar agar minimap tampil di atas scale bar (posisi sama: bottomleft).
if (L.Control && L.Control.MiniMap) {
    const miniMapLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png', {
        subdomains: 'abcd',
        maxZoom: 10,
        attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
    });

    window.miniMapControl = new L.Control.MiniMap(miniMapLayer, {
        position: 'bottomleft',
        width: 120,
        height: 120,
        zoomLevelOffset: -5,
        minimized: false,
        aimingRectOptions: {
            color: '#ff7800',
            weight: 2
        },
        shadowRectOptions: {
            color: '#ff7800',
            weight: 2,
            opacity: 0,
            fillOpacity: 0
        },
        zoomAnimation: false,
        mapOptions: {
            maxZoom: 10
        }
    }).addTo(map);

    const toggleMiniMap = document.getElementById('toggle-minimap-ui');
    const miniMapContainer = window.miniMapControl.getContainer && window.miniMapControl.getContainer();
    if (toggleMiniMap && miniMapContainer && !toggleMiniMap.checked) {
        miniMapContainer.classList.add('is-hidden');
    }
}

L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: '&copy; OpenStreetMap contributors',
}).addTo(map);

// Hapus teks "Leaflet" di attribution (attribution OSM tetap wajib ditampilkan)
map.attributionControl.setPrefix(false);

var markerLayer = L.layerGroup().addTo(map);
window.layerWilayahKalsel = null;
window.highlightWilayah = null;
window.tooltipWilayahAktif = null;
window.statusPolygonAktif = true;
window.statusPolygonWilayah = {};
window.defaultStatusPolygonWilayah = true;

function getStyleWilayah(feature) {
    var namaKab = getNamaWilayah(feature);

    return {
        fillColor: getColor(namaKab),
        weight: 1,
        opacity: 1,
        color: '#475569',
        dashArray: '3',
        fillOpacity: 0.5
    };
}

// 1. Fungsi untuk menentukan warna berdasarkan nama kabupaten
// Kamu bisa menyesuaikan list warna ini agar estetik
// Sumber data warna wilayah (dipakai oleh polygon & UI toggle)
const WILAYAH_CONFIG = {
    'banjarmasin': { regionId: 'banjarmasin', regionName: 'Banjarmasin', color: '#004a87' },
    'banjarbaru': { regionId: 'banjarbaru', regionName: 'Banjarbaru', color: '#0ea5e9' },
    'banjar': { regionId: 'banjar', regionName: 'Banjar', color: '#10b981' },
    'barito kuala': { regionId: 'barito kuala', regionName: 'Barito Kuala', color: '#f59e0b' },
    'tanah laut': { regionId: 'tanah laut', regionName: 'Tanah Laut', color: '#ef4444' },
    'tanah bumbu': { regionId: 'tanah bumbu', regionName: 'Tanah Bumbu', color: '#8b5cf6' },
    'kotabaru': { regionId: 'kotabaru', regionName: 'Kotabaru', color: '#ec4899' },
    'tapin': { regionId: 'tapin', regionName: 'Tapin', color: '#f97316' },
    'hulu sungai selatan': { regionId: 'hulu sungai selatan', regionName: 'Hulu Sungai Selatan', color: '#14b8a6' },
    'hulu sungai tengah': { regionId: 'hulu sungai tengah', regionName: 'Hulu Sungai Tengah', color: '#6366f1' },
    'hulu sungai utara': { regionId: 'hulu sungai utara', regionName: 'Hulu Sungai Utara', color: '#a855f7' },
    'balangan': { regionId: 'balangan', regionName: 'Balangan', color: '#fbbf24' },
    'tabalong': { regionId: 'tabalong', regionName: 'Tabalong', color: '#4ade80' }
};

window.wilayahConfig = WILAYAH_CONFIG;
window.wilayahRegistry = window.wilayahRegistry || {};

function getWarnaWilayahByKey(key) {
    return WILAYAH_CONFIG[key]?.color || '#94a3b8';
}

function getWarnaWilayah(namaWilayah) {
    return getWarnaWilayahByKey(getKeyWilayah(namaWilayah));
}

function getColor(namaWilayah) {
    return getWarnaWilayah(namaWilayah);
}

function getNamaWilayah(feature) {
    return feature?.properties?.nama ||
        feature?.properties?.NAMOBJ ||
        feature?.properties?.KAB_KOTA ||
        '';
}

function normalisasiTeksWilayah(teks) {
    return (teks || '')
        .toString()
        .toLowerCase()
        .replace(/kabupaten/g, '')
        .replace(/kota/g, '')
        .replace(/\s+/g, ' ')
        .trim();
}

function getKeyWilayah(teks) {
    return normalisasiTeksWilayah(teks);
}

function sinkronkanInteraktivitasPolygon(layer, visible) {
    const element = layer.getElement();

    if (!element) {
        return;
    }

    element.style.pointerEvents = visible ? 'auto' : 'none';
}

function aturTampilanPolygonWilayah(layer, visible) {
    if (visible) {
        window.layerWilayahKalsel.resetStyle(layer);
        sinkronkanInteraktivitasPolygon(layer, true);
        return;
    }

    if (window.highlightWilayah === layer) {
        window.resetHighlightWilayah();
    }

    layer.setStyle({
        fillOpacity: 0,
        opacity: 0,
        weight: 0
    });
    sinkronkanInteraktivitasPolygon(layer, false);
}

function cariWilayahPadaDataAlumni(keyword) {
    const kataKunci = normalisasiTeksWilayah(keyword);

    if (!kataKunci || !Array.isArray(window.alumniData)) {
        return false;
    }

    const alumniCocok = window.alumniData.filter(function (item) {
        const teksWilayah = [
            item?.kota || '',
            item?.provinsi || '',
            item?.alamat || ''
        ].join(' ');

        return normalisasiTeksWilayah(teksWilayah).includes(kataKunci);
    });

    if (alumniCocok.length === 0) {
        return false;
    }

    const bounds = [];

    alumniCocok.forEach(function (item) {
        const lat = parseFloat(item?.latitude);
        const lng = parseFloat(item?.longitude);

        if (Number.isFinite(lat) && Number.isFinite(lng)) {
            bounds.push([lat, lng]);
        }
    });

    if (bounds.length === 0) {
        return false;
    }

    window.resetHighlightWilayah();

    if (bounds.length === 1) {
        map.flyTo(bounds[0], 11, {
            animate: true,
            duration: 1.5
        });
    } else {
        map.fitBounds(bounds, {
            padding: [30, 30],
            maxZoom: 11
        });
    }

    return true;
}

window.cariWilayahDanZoom = function (keyword) {
    const kataKunci = normalisasiTeksWilayah(keyword);

    if (!kataKunci || !window.layerWilayahKalsel) {
        return cariWilayahPadaDataAlumni(keyword);
    }

    let wilayahCocok = null;

    window.layerWilayahKalsel.eachLayer(function (layer) {
        if (wilayahCocok) return;

        const namaWilayah = getNamaWilayah(layer.feature);
        const namaNormal = normalisasiTeksWilayah(namaWilayah);
        const aktif =
            window.statusPolygonWilayah[getKeyWilayah(namaWilayah)] !== false;

        if (!aktif) return;

        if (
            namaNormal === kataKunci ||
            namaNormal.includes(kataKunci) ||
            kataKunci.includes(namaNormal)
        ) {
            wilayahCocok = layer;
        }
    });

    if (!wilayahCocok) {
        return cariWilayahPadaDataAlumni(keyword);
    }

    window.resetHighlightWilayah();

    window.highlightWilayah = wilayahCocok;

    wilayahCocok.setStyle({
        fillOpacity: 0.8,
        weight: 2,
        color: '#1d4ed8',
        dashArray: null
    });

    map.fitBounds(wilayahCocok.getBounds(), {
        padding: [30, 30],
        maxZoom: 11
    });

    const namaWilayah = getNamaWilayah(wilayahCocok.feature);

    window.tooltipWilayahAktif = L.tooltip({
        permanent: false,
        direction: 'center',
        className: 'wilayah-search-tooltip'
    })
        .setLatLng(wilayahCocok.getBounds().getCenter())
        .setContent(namaWilayah)
        .addTo(map);

    return true;
};

window.resetHighlightWilayah = function () {
    if (window.tooltipWilayahAktif && map.hasLayer(window.tooltipWilayahAktif)) {
        map.removeLayer(window.tooltipWilayahAktif);
    }

    window.tooltipWilayahAktif = null;

    if (!window.layerWilayahKalsel || !window.highlightWilayah) {
        return;
    }

    window.layerWilayahKalsel.resetStyle(window.highlightWilayah);
    window.highlightWilayah = null;
};

window.perbaruiTampilanPolygon = function () {
    if (!window.layerWilayahKalsel) {
        return;
    }

    if (window.statusPolygonAktif) {
        if (!map.hasLayer(window.layerWilayahKalsel)) {
            window.layerWilayahKalsel.addTo(map);
        }

        window.layerWilayahKalsel.eachLayer(function (layer) {
            const namaWilayah = getNamaWilayah(layer.feature);
            const aktif =
                window.statusPolygonWilayah[getKeyWilayah(namaWilayah)] !== false;

            aturTampilanPolygonWilayah(layer, aktif);
        });

        if (typeof window.sinkronkanKontrolPolygonWilayah === 'function') {
            window.sinkronkanKontrolPolygonWilayah();
        }
    } else {
        window.resetHighlightWilayah();

        if (map.hasLayer(window.layerWilayahKalsel)) {
            map.removeLayer(window.layerWilayahKalsel);
        }

        if (typeof window.sinkronkanKontrolPolygonWilayah === 'function') {
            window.sinkronkanKontrolPolygonWilayah();
        }
    }
};

window.setStatusPolygonWilayah = function (namaWilayah, isVisible) {
    const key = getKeyWilayah(namaWilayah);

    window.statusPolygonWilayah[key] = isVisible;

    if (!window.layerWilayahKalsel) {
        if (typeof window.sinkronkanKontrolPolygonWilayah === 'function') {
            window.sinkronkanKontrolPolygonWilayah();
        }
        return;
    }

    if (isVisible && !window.statusPolygonAktif) {
        window.statusPolygonAktif = true;

        const masterToggle = document.getElementById('toggle-polygon-map');
        if (masterToggle) {
            masterToggle.checked = true;
        }

        if (typeof window.perbaruiTampilanPolygon === 'function') {
            window.perbaruiTampilanPolygon();
            return;
        }

        if (!map.hasLayer(window.layerWilayahKalsel)) {
            window.layerWilayahKalsel.addTo(map);
        }

        window.layerWilayahKalsel.eachLayer(function (layer) {
            const namaLayer = getNamaWilayah(layer.feature);
            const layerKey = getKeyWilayah(namaLayer);
            const aktif = window.statusPolygonWilayah[layerKey] !== false;
            aturTampilanPolygonWilayah(layer, aktif);
        });
    }

    window.layerWilayahKalsel.eachLayer(function (layer) {
        const namaLayer = getNamaWilayah(layer.feature);

        if (getKeyWilayah(namaLayer) !== key) {
            return;
        }

        if (!window.statusPolygonAktif) {
            return;
        }

        aturTampilanPolygonWilayah(layer, isVisible);
    });

    if (typeof window.sinkronkanKontrolPolygonWilayah === 'function') {
        window.sinkronkanKontrolPolygonWilayah();
    }
};

window.setSemuaStatusPolygonWilayah = function (isVisible) {
    window.defaultStatusPolygonWilayah = !!isVisible;

    const container = document.getElementById('polygon-wilayah-list');

    if (container) {
        container.querySelectorAll('.toggle-polygon-wilayah').forEach(function (input) {
            input.checked = !!isVisible;
        });
    }

    if (!window.layerWilayahKalsel) {
        window.statusPolygonWilayah = {};

        if (typeof window.sinkronkanKontrolPolygonWilayah === 'function') {
            window.sinkronkanKontrolPolygonWilayah();
        }

        return;
    }

    if (!isVisible) {
        window.resetHighlightWilayah();
    }

    window.layerWilayahKalsel.eachLayer(function (layer) {
        const namaWilayah = getNamaWilayah(layer.feature);
        const key = getKeyWilayah(namaWilayah);

        window.statusPolygonWilayah[key] = !!isVisible;

        if (!window.statusPolygonAktif) {
            return;
        }

        aturTampilanPolygonWilayah(layer, !!isVisible);
    });

    if (typeof window.sinkronkanKontrolPolygonWilayah === 'function') {
        window.sinkronkanKontrolPolygonWilayah();
    }
};

window.renderKontrolPolygonWilayah = function () {
    const container = document.getElementById('polygon-wilayah-list');

    if (!container || !window.layerWilayahKalsel) {
        return;
    }

    const wilayahList = [];

    window.layerWilayahKalsel.eachLayer(function (layer) {
        const namaWilayah = getNamaWilayah(layer.feature);
        const key = getKeyWilayah(namaWilayah);
        const color = getWarnaWilayahByKey(key);

        window.wilayahRegistry[key] = {
            regionId: key,
            regionName: namaWilayah,
            color,
            layer
        };

        if (!(key in window.statusPolygonWilayah)) {
            window.statusPolygonWilayah[key] = window.defaultStatusPolygonWilayah !== false;
        }

        wilayahList.push({
            key,
            nama: namaWilayah,
            color
        });
    });

    wilayahList.sort(function (a, b) {
        return a.nama.localeCompare(b.nama, 'id');
    });

    container.innerHTML = wilayahList.map(function (item) {
        const checked = window.statusPolygonWilayah[item.key] !== false ? 'checked' : '';
        const visible = window.statusPolygonAktif && window.statusPolygonWilayah[item.key] !== false;
        const visibleClass = visible ? 'is-wilayah-visible' : '';

        return `
            <label class="polygon-wilayah-item ${visibleClass}" data-wilayah-key="${item.key}" style="--wilayah-color: ${item.color};">
                <span class="polygon-wilayah-label">
                    <span class="polygon-wilayah-swatch" aria-hidden="true"></span>
                    <span class="polygon-wilayah-name">${item.nama}</span>
                </span>
                <span class="switch-kustom switch-mini">
                    <input
                        type="checkbox"
                        class="toggle-polygon-wilayah"
                        data-wilayah="${item.nama}"
                        ${checked}
                    >
                    <span class="slider-kustom"></span>
                </span>
            </label>
        `;
    }).join('');

    if (typeof window.sinkronkanKontrolPolygonWilayah === 'function') {
        window.sinkronkanKontrolPolygonWilayah();
    }
};

window.sinkronkanKontrolPolygonWilayah = function () {
    const container = document.getElementById('polygon-wilayah-list');
    if (!container) {
        return;
    }

    const items = container.querySelectorAll('.polygon-wilayah-item[data-wilayah-key]');

    items.forEach(function (item) {
        const key = item.dataset.wilayahKey || '';
        const visible = window.statusPolygonAktif && window.statusPolygonWilayah[key] !== false;
        item.classList.toggle('is-wilayah-visible', !!visible);
    });
};

window.previewHighlightWilayah = function (key, hover) {
    if (!window.layerWilayahKalsel) {
        return;
    }

    const registry = window.wilayahRegistry || {};
    const target = registry[key]?.layer || null;

    const isVisible =
        window.statusPolygonAktif &&
        window.statusPolygonWilayah[key] !== false;

    if (!hover) {
        if (window.previewWilayahLayer && window.highlightWilayah !== window.previewWilayahLayer) {
            window.layerWilayahKalsel.resetStyle(window.previewWilayahLayer);
        }

        window.previewWilayahLayer = null;
        window.previewWilayahKey = null;
        return;
    }

    if (!target || !isVisible) {
        return;
    }

    if (window.previewWilayahLayer && window.previewWilayahLayer !== target) {
        if (window.highlightWilayah !== window.previewWilayahLayer) {
            window.layerWilayahKalsel.resetStyle(window.previewWilayahLayer);
        }
    }

    window.previewWilayahLayer = target;
    window.previewWilayahKey = key;

    if (window.highlightWilayah === target) {
        return;
    }

    target.setStyle({
        weight: 2,
        color: '#0f172a',
        fillOpacity: 0.75
    });

    if (typeof target.bringToFront === 'function') {
        target.bringToFront();
    }
};

document.addEventListener('wilayah-panel-hover', function (e) {
    const key = e?.detail?.key || '';
    const hover = !!e?.detail?.hover;

    if (!key || typeof window.previewHighlightWilayah !== 'function') {
        return;
    }

    window.previewHighlightWilayah(key, hover);
});

// 2. Load GeoJSON Kalsel
fetch('/data/data_kalsel.geojson')
    .then(response => response.json())
    .then(geojsonData => {
        window.layerWilayahKalsel = L.geoJSON(geojsonData, {
            style: getStyleWilayah,
            onEachFeature: function (feature, layer) {
                // Efek hover: warna sedikit lebih terang saat mouse di atas wilayah
                layer.on({
                    mouseover: function (e) {
                        var layer = e.target;
                        const namaWilayah = getNamaWilayah(layer.feature);
                        const key = getKeyWilayah(namaWilayah);

                        document.dispatchEvent(new CustomEvent('wilayah-map-hover', {
                            detail: { key, hover: true }
                        }));

                         if (window.highlightWilayah === layer) {
                             return;
                         }

                        layer.setStyle({
                            fillOpacity: 0.7,
                            weight: 1
                        });
                    },
                    mouseout: function (e) {
                        var layer = e.target;
                        const namaWilayah = getNamaWilayah(layer.feature);
                        const key = getKeyWilayah(namaWilayah);

                        document.dispatchEvent(new CustomEvent('wilayah-map-hover', {
                            detail: { key, hover: false }
                        }));

                        if (window.highlightWilayah === layer) {
                            return;
                        }

                        window.layerWilayahKalsel.resetStyle(layer);
                    }
                });
            }
        });

        window.renderKontrolPolygonWilayah();
        window.perbaruiTampilanPolygon();
    })
    .catch(error => console.error('Error:', error));
