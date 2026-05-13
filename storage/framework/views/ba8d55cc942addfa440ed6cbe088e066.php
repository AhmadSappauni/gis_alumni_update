<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Testing Nominatim Geocoding</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root {
            --bg: #f1f5f9;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --blue: #004a87;
            --blue-2: #0ea5e9;
            --shadow: 0 10px 25px rgba(2, 6, 23, 0.08);
            --radius: 12px;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        a { color: var(--blue); }

        .container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 22px 16px 40px;
        }

        .header {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 16px;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 900;
            letter-spacing: -0.02em;
        }
        .header p {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1.15fr;
            gap: 14px;
            align-items: start;
        }

        @media (max-width: 980px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: var(--card);
            border: 1px solid rgba(15, 23, 42, 0.06);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 16px;
        }

        .card h2 {
            margin: 0 0 12px;
            font-size: 14px;
            font-weight: 900;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: #0f172a;
        }

        .row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .muted { color: var(--muted); }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
        .small { font-size: 12px; }

        .input {
            flex: 1;
            min-width: 260px;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
            outline: none;
            font-size: 14px;
        }
        .input:focus {
            border-color: rgba(14, 165, 233, 0.6);
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.15);
        }

        .btn {
            border: none;
            border-radius: 12px;
            padding: 12px 14px;
            font-weight: 800;
            cursor: pointer;
            font-size: 13px;
            line-height: 1;
            user-select: none;
            white-space: nowrap;
        }
        .btn-primary { background: var(--blue); color: #fff; }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
        .btn-secondary { background: #e2e8f0; color: #0f172a; }
        .btn-ghost { background: #eff6ff; color: #1d4ed8; border: 1px solid rgba(29, 78, 216, 0.15); }

        .divider {
            height: 1px;
            background: rgba(15, 23, 42, 0.06);
            margin: 14px 0;
        }

        .checkbox {
            display: inline-flex;
            gap: 10px;
            align-items: center;
            font-size: 12px;
            color: #0f172a;
            font-weight: 700;
            cursor: pointer;
            user-select: none;
        }
        .checkbox input {
            width: 16px;
            height: 16px;
        }

        details {
            margin-top: 12px;
            border: 1px dashed rgba(15, 23, 42, 0.12);
            border-radius: 12px;
            padding: 10px 12px;
            background: rgba(248, 250, 252, 0.75);
        }
        summary {
            cursor: pointer;
            font-weight: 900;
            font-size: 12px;
            color: #0f172a;
            outline: none;
        }
        details[open] summary {
            margin-bottom: 10px;
        }

        .builder-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: 1fr 1fr;
        }
        @media (max-width: 520px) {
            .builder-grid {
                grid-template-columns: 1fr;
            }
        }

        .results {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-height: 420px;
            overflow: auto;
            padding-right: 4px;
        }

        .result-item {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px;
            background: #fff;
        }
        .result-item.is-selected {
            border-color: rgba(14, 165, 233, 0.65);
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.15);
        }

        .result-title {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            justify-content: space-between;
        }
        .result-title strong {
            font-size: 13px;
            line-height: 1.35;
        }

        .badge-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .badge {
            font-size: 11px;
            font-weight: 800;
            padding: 4px 8px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #0f172a;
            border: 1px solid rgba(15, 23, 42, 0.06);
        }

        .actions {
            display: inline-flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .mini-btn {
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: #fff;
            border-radius: 10px;
            padding: 8px 10px;
            font-weight: 900;
            font-size: 12px;
            cursor: pointer;
        }
        .mini-btn.primary {
            background: rgba(0, 74, 135, 0.08);
            border-color: rgba(0, 74, 135, 0.18);
            color: #003b6c;
        }

        .map-wrap {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(15, 23, 42, 0.06);
        }
        #map { width: 100%; min-height: 480px; }

        .kv {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }
        @media (max-width: 520px) {
            .kv { grid-template-columns: 1fr; }
        }

        .kv-item {
            border: 1px solid rgba(15, 23, 42, 0.06);
            background: rgba(248, 250, 252, 0.75);
            border-radius: 12px;
            padding: 10px 12px;
        }
        .kv-label {
            font-size: 11px;
            color: var(--muted);
            font-weight: 800;
            margin-bottom: 6px;
        }
        .kv-value {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            word-break: break-word;
        }

        .toast {
            position: fixed;
            right: 16px;
            bottom: 16px;
            background: #0f172a;
            color: #fff;
            border-radius: 12px;
            padding: 12px 14px;
            box-shadow: 0 10px 25px rgba(2, 6, 23, 0.25);
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 0.2s ease, transform 0.2s ease;
            max-width: 360px;
            z-index: 9999;
            font-size: 13px;
            font-weight: 700;
            pointer-events: none;
        }
        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }
        .toast.success { background: #065f46; }
        .toast.warning { background: #92400e; }
        .toast.error { background: #991b1b; }

        .footer-note {
            margin-top: 10px;
            font-size: 12px;
            color: var(--muted);
            line-height: 1.4;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Testing Nominatim Geocoding</h1>
        <p>Cari alamat dan pilih koordinat yang paling sesuai, lalu salin latitude/longitude.</p>
    </div>

    <div class="grid">
        <div style="display:flex; flex-direction:column; gap:14px;">
            <div class="card">
                <h2>Pencarian</h2>
                <div class="row">
                    <input id="query" class="input" type="text" placeholder="Contoh: Jl. Brigjen Hasan Basri, Banjarmasin" autocomplete="off" />
                    <button id="btn-search" class="btn btn-primary" type="button">Search</button>
                    <button id="btn-clear" class="btn btn-secondary" type="button">Bersihkan</button>
                </div>
                <div class="row" style="margin-top:10px; justify-content:space-between;">
                    <label class="checkbox">
                        <input type="checkbox" id="toggle-clean-rtrw" checked />
                        Bersihkan RT/RW sebelum cari
                    </label>
                    <div id="status-text" class="muted small"></div>
                </div>

                <details>
                    <summary>Format query bantu (opsional)</summary>
                    <div class="builder-grid">
                        <input id="part-alamat" class="input" type="text" placeholder="alamat/jalan" />
                        <input id="part-desa" class="input" type="text" placeholder="desa/kelurahan" />
                        <input id="part-kecamatan" class="input" type="text" placeholder="kecamatan" />
                        <input id="part-kota" class="input" type="text" placeholder="kota/kabupaten" />
                        <input id="part-provinsi" class="input" type="text" placeholder="provinsi" />
                        <button id="btn-combine" class="btn btn-ghost" type="button">Gabungkan Alamat</button>
                    </div>
                    <div class="footer-note">
                        Tombol ini mengisi input pencarian utama menjadi:
                        <span class="mono">alamat, desa, kecamatan, kota/kabupaten, provinsi, Indonesia</span>.
                    </div>
                </details>
            </div>

            <div class="card">
                <h2>Deteksi Tempat (Koordinat)</h2>
                <div class="row">
                    <input id="rev-coord" class="input" type="text" placeholder="Koordinat (contoh: -3.316694, 114.590111)" inputmode="text" />
                </div>
                <div class="row" style="margin-top:10px; justify-content:space-between;">
                    <div class="row">
                        <button id="btn-reverse" class="btn btn-primary" type="button">Reverse</button>
                        <button id="btn-geolocate" class="btn btn-ghost" type="button">Lokasi Saya</button>
                        <button id="btn-use-selected" class="btn btn-secondary" type="button">Pakai Terpilih</button>
                    </div>
                    <div class="muted small">Reverse via endpoint aplikasi</div>
                </div>
                <div class="footer-note">
                    Isi format <span class="mono">lat, lon</span> (dipisah koma), atau klik <b>Lokasi Saya</b>. Hasil akan mengisi panel <b>Detail Terpilih</b> dan memusatkan peta.
                </div>
            </div>

            <div class="card">
                <h2>Hasil Pencarian</h2>
                <div id="results-empty" class="muted small">Belum ada hasil. Ketik alamat lalu klik Search.</div>
                <div id="results-list" class="results" style="display:none;"></div>
            </div>

            <div class="card">
                <h2>GeoJSON</h2>
                <div class="row" style="justify-content:space-between; margin-bottom:10px;">
                    <div class="muted small">FeatureCollection (Point) dari hasil Nominatim.</div>
                    <button id="btn-copy-geojson" class="btn btn-ghost" type="button">Salin GeoJSON</button>
                </div>
                <textarea
                    id="geojson-output"
                    class="input mono"
                    style="min-height: 220px; width: 100%; resize: vertical;"
                    readonly
                    placeholder="GeoJSON akan muncul setelah pencarian..."
                ></textarea>
            </div>

            <div class="card">
                <h2>Detail Terpilih</h2>
                <div id="selected-empty" class="muted small">Belum ada hasil yang dipilih.</div>

                <div id="selected-panel" style="display:none;">
                    <div style="font-weight:900; font-size:14px; line-height:1.35;" id="sel-display-name"></div>
                    <div class="kv">
                        <div class="kv-item">
                            <div class="kv-label">Latitude</div>
                            <div class="kv-value mono" id="sel-lat">-</div>
                        </div>
                        <div class="kv-item">
                            <div class="kv-label">Longitude</div>
                            <div class="kv-value mono" id="sel-lon">-</div>
                        </div>
                        <div class="kv-item">
                            <div class="kv-label">Class</div>
                            <div class="kv-value" id="sel-class">-</div>
                        </div>
                        <div class="kv-item">
                            <div class="kv-label">Type</div>
                            <div class="kv-value" id="sel-type">-</div>
                        </div>
                        <div class="kv-item">
                            <div class="kv-label">Importance</div>
                            <div class="kv-value mono" id="sel-importance">-</div>
                        </div>
                    </div>

                    <div class="row" style="margin-top:12px;">
                        <button class="btn btn-ghost" type="button" id="btn-copy-lat">Salin Latitude</button>
                        <button class="btn btn-ghost" type="button" id="btn-copy-lon">Salin Longitude</button>
                        <button class="btn btn-primary" type="button" id="btn-copy-coord">Salin Koordinat</button>
                        <button class="btn btn-secondary" type="button" id="btn-copy-address">Salin Alamat Lengkap</button>
                    </div>

                    <div class="divider"></div>

                    <div class="kv">
                        <div class="kv-item">
                            <div class="kv-label">Road</div>
                            <div class="kv-value" id="sel-road">-</div>
                        </div>
                        <div class="kv-item">
                            <div class="kv-label">Village/Suburb</div>
                            <div class="kv-value" id="sel-village">-</div>
                        </div>
                        <div class="kv-item">
                            <div class="kv-label">City/County</div>
                            <div class="kv-value" id="sel-city">-</div>
                        </div>
                        <div class="kv-item">
                            <div class="kv-label">State</div>
                            <div class="kv-value" id="sel-state">-</div>
                        </div>
                        <div class="kv-item">
                            <div class="kv-label">Postcode</div>
                            <div class="kv-value mono" id="sel-postcode">-</div>
                        </div>
                        <div class="kv-item">
                            <div class="kv-label">Country</div>
                            <div class="kv-value" id="sel-country">-</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Peta</h2>
            <div class="map-wrap">
                <div id="map"></div>
            </div>
            <div class="footer-note">
                Peta: &copy; OpenStreetMap contributors. Pencarian: Nominatim Search API (manual, limit 10, <span class="mono">countrycodes=id</span>).
                Gunakan secara wajar (hindari spam request).
            </div>
        </div>
    </div>
</div>

<div id="toast" class="toast" role="status" aria-live="polite"></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search';
    const REVERSE_ENDPOINT = <?php echo json_encode(route('nominatim.reverse'), 15, 512) ?>;
    const DEFAULT_CENTER = [-3.316694, 114.590111];
    const DEFAULT_ZOOM = 11;

    const elQuery = document.getElementById('query');
    const btnSearch = document.getElementById('btn-search');
    const btnClear = document.getElementById('btn-clear');
    const toggleClean = document.getElementById('toggle-clean-rtrw');
    const statusText = document.getElementById('status-text');

    const resultsEmpty = document.getElementById('results-empty');
    const resultsList = document.getElementById('results-list');
    const geojsonOutput = document.getElementById('geojson-output');
    const btnCopyGeojson = document.getElementById('btn-copy-geojson');

    const selectedEmpty = document.getElementById('selected-empty');
    const selectedPanel = document.getElementById('selected-panel');
    const selDisplayName = document.getElementById('sel-display-name');
    const selLat = document.getElementById('sel-lat');
    const selLon = document.getElementById('sel-lon');
    const selClass = document.getElementById('sel-class');
    const selType = document.getElementById('sel-type');
    const selImportance = document.getElementById('sel-importance');
    const selRoad = document.getElementById('sel-road');
    const selVillage = document.getElementById('sel-village');
    const selCity = document.getElementById('sel-city');
    const selState = document.getElementById('sel-state');
    const selPostcode = document.getElementById('sel-postcode');
    const selCountry = document.getElementById('sel-country');

    const btnCopyLat = document.getElementById('btn-copy-lat');
    const btnCopyLon = document.getElementById('btn-copy-lon');
    const btnCopyCoord = document.getElementById('btn-copy-coord');
    const btnCopyAddress = document.getElementById('btn-copy-address');

    const partAlamat = document.getElementById('part-alamat');
    const partDesa = document.getElementById('part-desa');
    const partKecamatan = document.getElementById('part-kecamatan');
    const partKota = document.getElementById('part-kota');
    const partProvinsi = document.getElementById('part-provinsi');
    const btnCombine = document.getElementById('btn-combine');

    const revCoord = document.getElementById('rev-coord');
    const btnReverse = document.getElementById('btn-reverse');
    const btnGeolocate = document.getElementById('btn-geolocate');
    const btnUseSelected = document.getElementById('btn-use-selected');

    const toast = document.getElementById('toast');

    const map = L.map('map', { zoomControl: true }).setView(DEFAULT_CENTER, DEFAULT_ZOOM);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const markersLayer = L.featureGroup().addTo(map);
    const markerById = new Map();

    let currentResults = [];
    let selectedItem = null;
    let reverseMarker = null;

    let lastSearchAt = 0;
    let currentAbort = null;

    function showToast(message, variant) {
        toast.className = 'toast';
        if (variant) toast.classList.add(variant);
        toast.textContent = String(message || '');
        toast.classList.add('show');
        window.clearTimeout(window.__toastTimer);
        window.__toastTimer = window.setTimeout(() => {
            toast.classList.remove('show');
        }, 2200);
    }

    function setStatus(message) {
        statusText.textContent = message ? String(message) : '';
    }

    function escapeHtml(value) {
        const text = String(value ?? '');
        return text
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function formatNumber(num, decimals) {
        if (!Number.isFinite(num)) return '-';
        const d = Number.isFinite(decimals) ? decimals : 7;
        return Number(num).toFixed(d);
    }

    function parseLat(value) {
        const n = parseFloat(value);
        return Number.isFinite(n) ? n : null;
    }

    function parseLon(value) {
        const n = parseFloat(value);
        return Number.isFinite(n) ? n : null;
    }

    function parseCoordText(value) {
        const raw = String(value ?? '').trim();
        if (!raw) {
            return { lat: null, lon: null, error: 'Koordinat masih kosong.' };
        }

        if (!raw.includes(',')) {
            return { lat: null, lon: null, error: 'Pakai format "lat, lon" (dipisah koma).' };
        }

        const parts = raw
            .split(',')
            .map((p) => String(p || '').trim())
            .filter(Boolean);

        if (parts.length !== 2) {
            return { lat: null, lon: null, error: 'Format koordinat tidak valid. Contoh: -3.316694, 114.590111' };
        }

        const lat = parseLat(parts[0]);
        const lon = parseLon(parts[1]);
        if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
            return { lat: null, lon: null, error: 'Koordinat tidak valid.' };
        }

        return { lat, lon, error: '' };
    }

    function buildReverseUrl(lat, lon) {
        const u = new URL(REVERSE_ENDPOINT, window.location.origin);
        u.searchParams.set('lat', String(lat));
        u.searchParams.set('lon', String(lon));
        u.searchParams.set('zoom', '18');
        return u.toString();
    }

    function normalizeReverse(data) {
        if (!data || typeof data !== 'object') return null;

        const lat = parseLat(data.lat);
        const lon = parseLon(data.lon);
        if (!Number.isFinite(lat) || !Number.isFinite(lon)) return null;

        return {
            ...data,
            lat,
            lon,
            class: data.class ?? data.category ?? '',
            type: data.type ?? '',
            address: data.address ?? {}
        };
    }

    function setReverseMarker(item, lat, lon) {
        if (reverseMarker) {
            markersLayer.removeLayer(reverseMarker);
            reverseMarker = null;
        }

        reverseMarker = L.marker([lat, lon]).addTo(markersLayer);
        reverseMarker.bindPopup(makeMarkerPopup(item, lat, lon));
    }

    function cleanRtRw(text) {
        if (!text) return '';

        let out = String(text);

        // RT/RW 01/02, RT.01/RW.02, Rt 01 Rw 02, dll.
        out = out.replace(/\brt\.?\s*\/\s*rw\.?\s*\d{1,3}\s*\/\s*\d{1,3}\b/gi, ' ');
        out = out.replace(/\brt\/rw\s*\d{1,3}\s*\/\s*\d{1,3}\b/gi, ' ');
        out = out.replace(/\brt\.?\s*\d{1,3}\b/gi, ' ');
        out = out.replace(/\brw\.?\s*\d{1,3}\b/gi, ' ');

        // Rapikan koma dan spasi
        out = out.replace(/\s*,\s*/g, ', ');
        out = out.replace(/,\s*,+/g, ', ');
        out = out.replace(/\s+/g, ' ');
        out = out.trim().replace(/^,/, '').replace(/,$/, '').trim();

        return out;
    }

    async function copyText(text) {
        const value = String(text ?? '');
        if (!value) {
            showToast('Tidak ada teks untuk disalin.', 'warning');
            return false;
        }

        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(value);
                showToast('Berhasil disalin.', 'success');
                return true;
            }
        } catch (_) {
            // fallback below
        }

        try {
            const textarea = document.createElement('textarea');
            textarea.value = value;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.left = '-9999px';
            textarea.style.top = '0';
            document.body.appendChild(textarea);
            textarea.select();
            const ok = document.execCommand('copy');
            document.body.removeChild(textarea);
            if (ok) {
                showToast('Berhasil disalin.', 'success');
                return true;
            }
        } catch (_) {}

        showToast('Gagal menyalin. Coba manual.', 'error');
        return false;
    }

    function buildSearchUrl(query) {
        const params = new URLSearchParams({
            q: query,
            format: 'json',
            addressdetails: '1',
            limit: '10',
            countrycodes: 'id'
        });
        return `${NOMINATIM_URL}?${params.toString()}`;
    }

    function clearMarkers() {
        markersLayer.clearLayers();
        markerById.clear();
        reverseMarker = null;
    }

    function toGeoJsonFeature(item) {
        const lat = parseLat(item?.lat);
        const lon = parseLon(item?.lon);
        if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
            return null;
        }

        const props = {
            display_name: item?.display_name ?? null,
            class: item?.class ?? null,
            type: item?.type ?? null,
            importance: (item?.importance !== undefined && item?.importance !== null) ? Number(item.importance) : null,
            place_id: item?.place_id ?? null,
            osm_type: item?.osm_type ?? null,
            osm_id: item?.osm_id ?? null,
            address: item?.address ?? null,
        };

        return {
            type: 'Feature',
            geometry: {
                type: 'Point',
                // GeoJSON: [longitude, latitude]
                coordinates: [lon, lat]
            },
            properties: props
        };
    }

    function updateGeoJsonOutput(items) {
        if (!geojsonOutput) return;

        const features = (items || [])
            .map(toGeoJsonFeature)
            .filter(Boolean);

        const fc = {
            type: 'FeatureCollection',
            features
        };

        geojsonOutput.value = JSON.stringify(fc, null, 2);
    }

    function clearSelected() {
        selectedItem = null;
        selectedEmpty.style.display = 'block';
        selectedPanel.style.display = 'none';

        document.querySelectorAll('.result-item.is-selected').forEach((el) => {
            el.classList.remove('is-selected');
        });
    }

    function renderResults(items) {
        currentResults = Array.isArray(items) ? items : [];
        resultsList.innerHTML = '';

        updateGeoJsonOutput(currentResults);

        if (!currentResults.length) {
            resultsEmpty.style.display = 'block';
            resultsList.style.display = 'none';
            return;
        }

        resultsEmpty.style.display = 'none';
        resultsList.style.display = 'flex';

        currentResults.forEach((item, index) => {
            const lat = parseLat(item?.lat);
            const lon = parseLon(item?.lon);
            const coordText = `${formatNumber(lat, 7)}, ${formatNumber(lon, 7)}`;
            const importance = Number(item?.importance);
            const importanceText = Number.isFinite(importance) ? importance.toFixed(4) : '-';

            const div = document.createElement('div');
            div.className = 'result-item';
            div.dataset.id = String(item.__id);

            div.innerHTML = `
                <div class="result-title">
                    <div style="display:flex; gap:10px; align-items:flex-start;">
                        <span class="badge">#${index + 1}</span>
                        <strong>${escapeHtml(item?.display_name || '-')}</strong>
                    </div>
                    <div class="actions">
                        <button class="mini-btn primary" data-action="select" data-id="${item.__id}">Pilih</button>
                        <button class="mini-btn" data-action="copy-coord" data-id="${item.__id}">Salin Koordinat</button>
                    </div>
                </div>
                <div class="badge-row">
                    <span class="badge mono">${escapeHtml(coordText)}</span>
                    <span class="badge">${escapeHtml(item?.class || '-')} / ${escapeHtml(item?.type || '-')}</span>
                    <span class="badge mono">importance: ${escapeHtml(importanceText)}</span>
                </div>
            `;

            resultsList.appendChild(div);
        });
    }

    function makeMarkerPopup(item, lat, lon) {
        const wrap = document.createElement('div');
        wrap.style.fontFamily = 'Inter, system-ui, sans-serif';
        wrap.style.maxWidth = '260px';

        const title = document.createElement('div');
        title.style.fontWeight = '900';
        title.style.fontSize = '13px';
        title.style.marginBottom = '8px';
        title.textContent = item?.display_name || '-';

        const coords = document.createElement('div');
        coords.className = 'mono';
        coords.style.fontSize = '12px';
        coords.textContent = `${formatNumber(lat, 7)}, ${formatNumber(lon, 7)}`;

        wrap.appendChild(title);
        wrap.appendChild(coords);
        return wrap;
    }

    function renderMarkers(items) {
        clearMarkers();

        const bounds = [];

        (items || []).forEach((item) => {
            const lat = parseLat(item?.lat);
            const lon = parseLon(item?.lon);
            if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
                return;
            }

            const marker = L.marker([lat, lon]);
            marker.bindPopup(makeMarkerPopup(item, lat, lon));
            marker.on('click', () => {
                selectItemById(item.__id, true);
            });
            marker.addTo(markersLayer);
            markerById.set(String(item.__id), marker);
            bounds.push([lat, lon]);
        });

        if (bounds.length === 1) {
            map.setView(bounds[0], 16, { animate: true });
        } else if (bounds.length > 1) {
            map.fitBounds(bounds, { padding: [24, 24], maxZoom: 16 });
        } else {
            map.setView(DEFAULT_CENTER, DEFAULT_ZOOM, { animate: true });
        }
    }

    function getAddressField(address, keys) {
        if (!address || typeof address !== 'object') return null;
        for (const key of keys) {
            const v = address[key];
            if (typeof v === 'string' && v.trim() !== '') {
                return v.trim();
            }
        }
        return null;
    }

    function setSelectedPanel(item) {
        const lat = parseLat(item?.lat);
        const lon = parseLon(item?.lon);

        selectedItem = item;
        selectedEmpty.style.display = 'none';
        selectedPanel.style.display = 'block';

        selDisplayName.textContent = item?.display_name || '-';
        selLat.textContent = formatNumber(lat, 7);
        selLon.textContent = formatNumber(lon, 7);
        selClass.textContent = item?.class || '-';
        selType.textContent = item?.type || '-';

        const importance = Number(item?.importance);
        selImportance.textContent = Number.isFinite(importance) ? importance.toFixed(6) : '-';

        const addr = item?.address || null;
        selRoad.textContent = getAddressField(addr, ['road', 'residential', 'pedestrian', 'path']) || '-';
        selVillage.textContent = getAddressField(addr, ['village', 'suburb', 'hamlet', 'neighbourhood']) || '-';
        selCity.textContent = getAddressField(addr, ['city', 'town', 'county']) || '-';
        selState.textContent = getAddressField(addr, ['state']) || '-';
        selPostcode.textContent = getAddressField(addr, ['postcode']) || '-';
        selCountry.textContent = getAddressField(addr, ['country']) || '-';
    }

    function selectItemById(id, panMap) {
        const item = currentResults.find((r) => String(r.__id) === String(id));
        if (!item) return;

        document.querySelectorAll('.result-item').forEach((el) => {
            el.classList.toggle('is-selected', el.dataset.id === String(id));
        });

        setSelectedPanel(item);

        const marker = markerById.get(String(id));
        const lat = parseLat(item?.lat);
        const lon = parseLon(item?.lon);

        if (marker) {
            marker.openPopup();
        }

        if (panMap && Number.isFinite(lat) && Number.isFinite(lon)) {
            map.flyTo([lat, lon], Math.max(16, map.getZoom()), { animate: true, duration: 0.9 });
        }
    }

    async function doSearch() {
        const raw = (elQuery.value || '').trim();
        if (!raw) {
            showToast('Alamat masih kosong.', 'warning');
            elQuery.focus();
            return;
        }

        let query = raw;
        if (toggleClean.checked) {
            query = cleanRtRw(query);
        }

        if (!query) {
            showToast('Alamat kosong setelah pembersihan RT/RW.', 'warning');
            elQuery.focus();
            return;
        }

        // Throttle 1 request/detik
        const now = Date.now();
        const elapsed = now - lastSearchAt;
        if (elapsed >= 0 && elapsed < 1000) {
            setStatus('Menunggu rate limit...');
            await new Promise((r) => setTimeout(r, 1000 - elapsed));
        }
        lastSearchAt = Date.now();

        if (currentAbort) {
            try { currentAbort.abort(); } catch (_) {}
        }
        currentAbort = new AbortController();

        btnSearch.disabled = true;
        setStatus('Mencari...');

        try {
            clearSelected();
            clearMarkers();

            const url = buildSearchUrl(query);
            const res = await fetch(url, {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                signal: currentAbort.signal
            });

            if (!res.ok) {
                throw new Error(`Request gagal (${res.status}).`);
            }

            const data = await res.json();
            const items = Array.isArray(data) ? data : [];

            if (!items.length) {
                renderResults([]);
                renderMarkers([]);
                showToast('Tidak ada hasil dari Nominatim.', 'warning');
                return;
            }

            const normalized = items.map((item, idx) => ({
                ...item,
                __id: idx + 1
            }));

            renderResults(normalized);
            renderMarkers(normalized);
            showToast(`Ditemukan ${normalized.length} hasil.`, 'success');
        } catch (err) {
            if (err && err.name === 'AbortError') {
                return;
            }
            console.error(err);
            renderResults([]);
            renderMarkers([]);
            clearSelected();
            showToast(err?.message || 'Fetch gagal.', 'error');
        } finally {
            btnSearch.disabled = false;
            setStatus('');
        }
    }

    async function doReverse(coordText) {
        const parsed = parseCoordText(coordText);
        if (!Number.isFinite(parsed.lat) || !Number.isFinite(parsed.lon)) {
            showToast(parsed.error || 'Koordinat tidak valid.', 'error');
            return;
        }

        btnReverse.disabled = true;
        setStatus('Reverse geocoding...');

        try {
            const url = buildReverseUrl(parsed.lat, parsed.lon);
            const res = await fetch(url, {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            });

            const payload = await res.json().catch(() => null);
            if (!res.ok) {
                const msg =
                    payload?.message ||
                    payload?.error ||
                    `Request gagal (${res.status}).`;
                throw new Error(msg);
            }

            const item = normalizeReverse(payload);
            if (!item) {
                throw new Error('Response reverse tidak valid.');
            }

            setSelectedPanel(item);
            setReverseMarker(item, item.lat, item.lon);
            map.setView([item.lat, item.lon], 17, { animate: true });
            if (reverseMarker) reverseMarker.openPopup();
            showToast('Reverse berhasil.', 'success');
        } catch (err) {
            console.error(err);
            showToast(err?.message || 'Reverse gagal.', 'error');
        } finally {
            btnReverse.disabled = false;
            setStatus('');
        }
    }

    function clearAll() {
        elQuery.value = '';
        revCoord.value = '';
        partAlamat.value = '';
        partDesa.value = '';
        partKecamatan.value = '';
        partKota.value = '';
        partProvinsi.value = '';

        renderResults([]);
        renderMarkers([]);
        clearSelected();
        if (reverseMarker) {
            markersLayer.removeLayer(reverseMarker);
            reverseMarker = null;
        }
        map.setView(DEFAULT_CENTER, DEFAULT_ZOOM, { animate: true });
        if (geojsonOutput) geojsonOutput.value = '';
        showToast('Tampilan dibersihkan.', 'success');
    }

    btnSearch.addEventListener('click', doSearch);
    btnClear.addEventListener('click', clearAll);
    btnReverse.addEventListener('click', () => doReverse(revCoord.value));

    btnGeolocate.addEventListener('click', () => {
        if (!navigator.geolocation) {
            showToast('Browser tidak mendukung geolocation.', 'error');
            return;
        }

        btnGeolocate.disabled = true;
        setStatus('Mengambil lokasi perangkat...');

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const lat = pos?.coords?.latitude;
                const lon = pos?.coords?.longitude;
                revCoord.value = `${formatNumber(parseFloat(lat), 7)}, ${formatNumber(parseFloat(lon), 7)}`;
                btnGeolocate.disabled = false;
                setStatus('');
                doReverse(revCoord.value);
            },
            (err) => {
                console.error(err);
                btnGeolocate.disabled = false;
                setStatus('');
                showToast(err?.message || 'Gagal mengambil lokasi.', 'error');
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    });

    btnUseSelected.addEventListener('click', () => {
        if (!selectedItem) return showToast('Belum ada hasil terpilih.', 'warning');
        const lat = parseLat(selectedItem?.lat);
        const lon = parseLon(selectedItem?.lon);
        if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
            showToast('Koordinat hasil terpilih tidak valid.', 'error');
            return;
        }
        revCoord.value = `${formatNumber(lat, 7)}, ${formatNumber(lon, 7)}`;
        doReverse(revCoord.value);
    });

    elQuery.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            doSearch();
        }
    });

    revCoord.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            doReverse(revCoord.value);
        }
    });

    resultsList.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;

        const action = btn.dataset.action;
        const id = btn.dataset.id;

        if (action === 'select') {
            selectItemById(id, true);
            return;
        }

        if (action === 'copy-coord') {
            const item = currentResults.find((r) => String(r.__id) === String(id));
            const lat = parseLat(item?.lat);
            const lon = parseLon(item?.lon);
            if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
                showToast('Koordinat tidak valid.', 'error');
                return;
            }
            copyText(`${formatNumber(lat, 7)}, ${formatNumber(lon, 7)}`);
        }
    });

    btnCopyLat.addEventListener('click', () => {
        if (!selectedItem) return showToast('Belum ada hasil terpilih.', 'warning');
        copyText(String(selectedItem?.lat ?? ''));
    });
    btnCopyLon.addEventListener('click', () => {
        if (!selectedItem) return showToast('Belum ada hasil terpilih.', 'warning');
        copyText(String(selectedItem?.lon ?? ''));
    });
    btnCopyCoord.addEventListener('click', () => {
        if (!selectedItem) return showToast('Belum ada hasil terpilih.', 'warning');
        const lat = parseLat(selectedItem?.lat);
        const lon = parseLon(selectedItem?.lon);
        if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
            showToast('Koordinat tidak valid.', 'error');
            return;
        }
        copyText(`${formatNumber(lat, 7)}, ${formatNumber(lon, 7)}`);
    });
    btnCopyAddress.addEventListener('click', () => {
        if (!selectedItem) return showToast('Belum ada hasil terpilih.', 'warning');
        copyText(String(selectedItem?.display_name ?? ''));
    });

    if (btnCopyGeojson) {
        btnCopyGeojson.addEventListener('click', () => {
            if (!geojsonOutput || !geojsonOutput.value) {
                showToast('GeoJSON masih kosong.', 'warning');
                return;
            }
            copyText(geojsonOutput.value);
        });
    }

    btnCombine.addEventListener('click', () => {
        const parts = [
            partAlamat.value,
            partDesa.value,
            partKecamatan.value,
            partKota.value,
            partProvinsi.value,
            'Indonesia'
        ]
            .map((v) => String(v || '').trim())
            .filter(Boolean);

        const unique = [];
        const seen = new Set();
        parts.forEach((p) => {
            const key = p.toLowerCase();
            if (seen.has(key)) return;
            seen.add(key);
            unique.push(p);
        });

        elQuery.value = unique.join(', ');
        showToast('Alamat digabungkan ke input pencarian.', 'success');
        elQuery.focus();
    });
</script>
</body>
</html>
<?php /**PATH D:\Aplikasi_Skripsi\gis_alumni_5\resources\views/nominatim.blade.php ENDPATH**/ ?>