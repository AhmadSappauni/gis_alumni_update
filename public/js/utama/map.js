// 1. Inisialisasi Peta & Base Map
var map = L.map("map", {
    zoomControl: false 
}).setView([-3.316694, 114.590111], 8); // Zoom saya ubah ke 8 agar Kalsel terlihat utuh

// Pindahkan tombol zoom ke pojok kanan bawah
L.control.zoom({
    position: 'bottomright'
}).addTo(map);

L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: '&copy; OpenStreetMap contributors',
}).addTo(map);

var markerLayer = L.layerGroup().addTo(map);
window.layerWilayahKalsel = null;
window.highlightWilayah = null;
window.tooltipWilayahAktif = null;
window.statusPolygonAktif = true;
window.statusPolygonWilayah = {};

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
function getColor(d) {
    return d === 'Banjarmasin'  ? '#004a87' :
           d === 'Banjarbaru'   ? '#0ea5e9' :
           d === 'Banjar'       ? '#10b981' :
           d === 'Barito Kuala' ? '#f59e0b' :
           d === 'Tanah Laut'   ? '#ef4444' :
           d === 'Tanah Bumbu'  ? '#8b5cf6' :
           d === 'Kotabaru'     ? '#ec4899' :
           d === 'Tapin'        ? '#f97316' :
           d === 'Hulu Sungai Selatan' ? '#14b8a6' :
           d === 'Hulu Sungai Tengah'  ? '#6366f1' :
           d === 'Hulu Sungai Utara'   ? '#a855f7' :
           d === 'Balangan'     ? '#fbbf24' :
           d === 'Tabalong'     ? '#4ade80' :
                                  '#94a3b8'; // Warna default jika nama tidak cocok
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
    } else {
        window.resetHighlightWilayah();

        if (map.hasLayer(window.layerWilayahKalsel)) {
            map.removeLayer(window.layerWilayahKalsel);
        }
    }
};

window.setStatusPolygonWilayah = function (namaWilayah, isVisible) {
    const key = getKeyWilayah(namaWilayah);

    window.statusPolygonWilayah[key] = isVisible;

    if (!window.layerWilayahKalsel) {
        return;
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

        if (!(key in window.statusPolygonWilayah)) {
            window.statusPolygonWilayah[key] = true;
        }

        wilayahList.push({
            key,
            nama: namaWilayah
        });
    });

    wilayahList.sort(function (a, b) {
        return a.nama.localeCompare(b.nama, 'id');
    });

    container.innerHTML = wilayahList.map(function (item) {
        const checked = window.statusPolygonWilayah[item.key] !== false ? 'checked' : '';

        return `
            <label class="polygon-wilayah-item">
                <span class="polygon-wilayah-name">${item.nama}</span>
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
};

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
