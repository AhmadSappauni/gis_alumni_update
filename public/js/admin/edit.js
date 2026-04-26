/* ==========================================
   CONFIG
========================================== */
const config = window.editConfig || {};

const DEFAULT_LAT = config.oldLat ?? -3.316694;
const DEFAULT_LNG = config.oldLng ?? 114.590111;
const PEKERJAAN_URL = config.pekerjaanUrl ?? "/admin/pekerjaan";
const STUDI_LANJUT_BASE_URL = config.studiLanjutBaseUrl ?? "";
const INITIAL_TAB = config.initialTab ?? "";

const TYPE_DELAY = 800;

/* ==========================================
   GLOBAL
========================================== */
let mapProfil, markerProfil;
let mapTambah, markerTambah;
let mapEdit, markerEdit;
let mapStudiTambah, markerStudiTambah;
let mapStudiEdit, markerStudiEdit;

let typingTimer;
let currentTabStep = 0;

/* ==========================================
   SHORTCUT
========================================== */
const $ = (id) => document.getElementById(id);

/* ==========================================
   FETCH JSON
========================================== */
async function getJSON(url) {
    const res = await fetch(url);
    return await res.json();
}

/* ==========================================
   GEOCODE
========================================== */
async function searchLocation(keyword) {

    let hasil = await getJSON(`/admin/geocode?q=${encodeURIComponent(keyword)}&wilayah=kalsel`);

    if (!hasil.length)
        hasil = await getJSON(`/admin/geocode?q=${encodeURIComponent(keyword)}&wilayah=indonesia`);

    if (!hasil.length)
        hasil = await getJSON(`/admin/geocode?q=${encodeURIComponent(keyword)}`);

    return hasil;
}

async function reverseLocation(lat, lng) {
    return await getJSON(
        `/admin/geocode?type=reverse&lat=${lat}&lng=${lng}&zoom=18&addressdetails=1`
    );
}

async function searchLocationCampus(keyword) {

    let hasil = await getJSON(`/admin/geocode?q=${encodeURIComponent(keyword)}&wilayah=indonesia`);

    if (!hasil.length) {
        hasil = await getJSON(`/admin/geocode?q=${encodeURIComponent(keyword)}`);
    }

    return hasil;
}

/* ==========================================
   MAP
========================================== */
function createMap(id, lat, lng) {

    const map = L.map(id).setView([lat, lng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Hapus teks "Leaflet" di attribution (attribution OSM tetap wajib ditampilkan)
    map.attributionControl.setPrefix(false);

    const marker = L.marker([lat, lng], {
        draggable: true
    }).addTo(map);

    return { map, marker };
}

function parseAddressParts(address) {
    const a = address || {};

    const alamat = [
        a.road || a.pedestrian || a.path,
        a.suburb || a.village || a.hamlet,
    ]
        .filter(Boolean)
        .join(', ');

    const kota =
        a.city || a.town || a.county || a.municipality || a.village || a.hamlet || a.state_district || '';

    const provinsi = a.state || '';

    return { alamat, kota, provinsi };
}

async function fillCampusFromReverse(lat, lng, prefix) {
    try {
        const data = await reverseLocation(lat, lng);
        const parts = parseAddressParts(data.address);

        const alamatEl = $(`${prefix}_alamat_kampus`);
        const kotaEl = $(`${prefix}_kota_kampus`);
        const provEl = $(`${prefix}_provinsi_kampus`);

        if (alamatEl && !alamatEl.value) alamatEl.value = parts.alamat || alamatEl.value;
        if (kotaEl && !kotaEl.value) kotaEl.value = parts.kota || kotaEl.value;
        if (provEl && !provEl.value) provEl.value = parts.provinsi || provEl.value;
    } catch (e) {
        console.error(e);
    }
}

function syncCampusMarker(mapObj, markerObj, lat, lng) {
    if (!mapObj || !markerObj) return;
    mapObj.setView([lat, lng], 14);
    markerObj.setLatLng([lat, lng]);
}

function bindCampusLatLngInputs(prefix, mapObj, markerObj) {
    const latEl = $(`${prefix}_lat`);
    const lngEl = $(`${prefix}_lng`);

    if (!latEl || !lngEl) return;

    const handler = () => {
        const lat = parseFloat(latEl.value);
        const lng = parseFloat(lngEl.value);

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            return;
        }

        syncCampusMarker(mapObj, markerObj, lat, lng);
    };

    latEl.addEventListener('change', handler);
    lngEl.addEventListener('change', handler);
}

function initMapStudiTambah() {
    if (mapStudiTambah) {
        const latNow = parseFloat($('studi_lat')?.value);
        const lngNow = parseFloat($('studi_lng')?.value);
        if (Number.isFinite(latNow) && Number.isFinite(lngNow)) {
            syncCampusMarker(mapStudiTambah, markerStudiTambah, latNow, lngNow);
        }
        setTimeout(() => mapStudiTambah.invalidateSize(), 300);
        return;
    }

    const obj = createMap('map-studi-tambah', DEFAULT_LAT, DEFAULT_LNG);
    mapStudiTambah = obj.map;
    markerStudiTambah = obj.marker;

    if ($('studi_lat') && !$('studi_lat').value) $('studi_lat').value = DEFAULT_LAT;
    if ($('studi_lng') && !$('studi_lng').value) $('studi_lng').value = DEFAULT_LNG;

    markerStudiTambah.on('dragend', e => {
        const pos = e.target.getLatLng();
        $('studi_lat').value = pos.lat;
        $('studi_lng').value = pos.lng;
        fillCampusFromReverse(pos.lat, pos.lng, 'studi');
    });

    bindCampusLatLngInputs('studi', mapStudiTambah, markerStudiTambah);
    setTimeout(() => mapStudiTambah.invalidateSize(), 300);
}

function initMapStudiEdit(lat, lng) {
    const centerLat = Number.isFinite(lat) ? lat : DEFAULT_LAT;
    const centerLng = Number.isFinite(lng) ? lng : DEFAULT_LNG;

    if (!mapStudiEdit) {
        const obj = createMap('map-studi-edit', centerLat, centerLng);
        mapStudiEdit = obj.map;
        markerStudiEdit = obj.marker;

        markerStudiEdit.on('dragend', e => {
            const pos = e.target.getLatLng();
            $('edit_studi_lat').value = pos.lat;
            $('edit_studi_lng').value = pos.lng;
            fillCampusFromReverse(pos.lat, pos.lng, 'edit');
        });

        bindCampusLatLngInputs('edit_studi', mapStudiEdit, markerStudiEdit);
    } else {
        syncCampusMarker(mapStudiEdit, markerStudiEdit, centerLat, centerLng);
    }

    setTimeout(() => mapStudiEdit.invalidateSize(), 300);
}

function buildCampusQuery(prefix) {
    const kampus = ($(`${prefix}_kampus`)?.value || '').trim();
    const alamat = ($(`${prefix}_alamat_kampus`)?.value || '').trim();
    const kota = ($(`${prefix}_kota_kampus`)?.value || '').trim();
    const prov = ($(`${prefix}_provinsi_kampus`)?.value || '').trim();

    return [kampus, alamat, kota, prov].filter(Boolean).join(', ');
}

async function fillAddress(lat, lng, textareaId, latId, lngId) {

    $(textareaId).value = "⏳ Mengambil alamat...";
    $(latId).value = lat;
    $(lngId).value = lng;

    try {
        const data = await reverseLocation(lat, lng);

        const a = data.address || {};

        const alamat = [
            a.road || a.pedestrian || a.path,
            a.suburb || a.village || a.hamlet,
            a.city || a.town || a.county,
            a.state,
            a.country
        ]
        .filter(Boolean)
        .join(', ');

        $(textareaId).value =
            alamat ||
            data.display_name ||
            "Alamat tidak ditemukan";

    } catch (error) {
        $(textareaId).value = "⚠ Gagal mengambil alamat";
        console.error(error);
    }
}

/* ==========================================
   SEARCH BIND
========================================== */
function bindSearch(inputId, mapObj, markerObj, textareaId, latId, lngId) {

    const input = $(inputId);
    const box = $(textareaId);

    if (!input) return;

    input.addEventListener('keyup', function () {

        clearTimeout(typingTimer);

        const keyword = this.value.trim();

        if (keyword.length < 3) {
            box.value = "⚠ Ketik minimal 3 huruf...";
            return;
        }

        box.value = " Sedang mencari lokasi...";

        typingTimer = setTimeout(async () => {

            try {

                const hasil = await searchLocation(keyword);

                if (!hasil.length) {
                    box.value = " Lokasi tidak ditemukan";
                    return;
                }

                const lat = hasil[0].lat;
                const lng = hasil[0].lon;

                mapObj.setView([lat, lng], 14);
                markerObj.setLatLng([lat, lng]);

                $(latId).value = lat;
                $(lngId).value = lng;

                box.value =  hasil[0].display_name;

            } catch {
                box.value = "⚠ Gagal mencari lokasi";
            }

        }, TYPE_DELAY);
    });
}

/* ==========================================
   TAB
========================================== */
window.switchTab = function (tabId, btn) {

    document.querySelectorAll('.tab-pane').forEach(tab => {
        tab.classList.remove('active');
    });

    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('active');
    });

    $(tabId).classList.add('active');
    btn.classList.add('active');

    setTimeout(() => {
        if (mapProfil) mapProfil.invalidateSize();
        if (mapTambah) mapTambah.invalidateSize();
        if (mapEdit) mapEdit.invalidateSize();
        if (mapStudiTambah) mapStudiTambah.invalidateSize();
        if (mapStudiEdit) mapStudiEdit.invalidateSize();
    }, 300);
};

function activateInitialTab() {
    const key = (INITIAL_TAB || "").toString();

    const tabMap = {
        profil: "tab-profil",
        "tab-profil": "tab-profil",
        karir: "tab-karir",
        pekerjaan: "tab-karir",
        "tab-karir": "tab-karir",
        studi: "tab-studi",
        "studi-lanjut": "tab-studi",
        "tab-studi": "tab-studi"
    };

    const tabId = tabMap[key];
    if (!tabId) {
        return;
    }

    const btn = document.querySelector(`.tab-btn[data-tab="${tabId}"]`);
    if (!btn) {
        return;
    }

    window.switchTab(tabId, btn);
}

/* ==========================================
   WIZARD
========================================== */
function showStep(n) {

    const steps = document.querySelectorAll('.form-step');
    const indicators = document.querySelectorAll('.step');

    steps.forEach((el, i) => {
        el.style.display = 'none';
        el.classList.remove('active');

        if (indicators[i]) indicators[i].classList.remove('active');
    });

    if (steps[n]) {
        steps[n].style.display = 'block';
        steps[n].classList.add('active');

        if (indicators[n]) indicators[n].classList.add('active');
    }

    $('progress-bar').style.width =
        (n / (steps.length - 1)) * 100 + "%";

    $('prevBtn').style.display =
        n === 0 ? 'none' : 'inline';

    if (n === steps.length - 1) {
        $('nextBtn').innerHTML = " Simpan Profil";
        $('nextBtn').onclick = () => $('wizardForm').submit();
    } else {
        $('nextBtn').innerHTML = "Lanjut";
        $('nextBtn').onclick = () => nextPrev(1);
    }

    setTimeout(() => {
        if (mapProfil) mapProfil.invalidateSize();
    }, 300);
}

window.nextPrev = function (n) {

    const steps = document.querySelectorAll('.form-step');

    if (n === 1) {

        const required =
            steps[currentTabStep].querySelectorAll(
                'input[required], textarea[required], select[required]'
            );

        for (const el of required) {
            if (!el.checkValidity()) {
                el.reportValidity();
                return;
            }
        }
    }

    currentTabStep += n;
    showStep(currentTabStep);
};

/* ==========================================
   MODAL TAMBAH
========================================== */
window.openModalKerja = function () {

    $('modal-pekerjaan').classList.add('active');
    toggleTanggalSelesai('tambah_is_current', 'tambah_tanggal_selesai');

    if (!mapTambah) {

        const obj = createMap('map-kerja-tambah', DEFAULT_LAT, DEFAULT_LNG);

        mapTambah = obj.map;
        markerTambah = obj.marker;

        markerTambah.on('dragend', e => {
            const pos = e.target.getLatLng();
            fillAddress(pos.lat, pos.lng, 'tambah_alamat', 'tambah_lat', 'tambah_lng');
        });

        bindSearch(
            'tambah_kota',
            mapTambah,
            markerTambah,
            'tambah_alamat',
            'tambah_lat',
            'tambah_lng'
        );
    }

    setTimeout(() => mapTambah.invalidateSize(), 300);
};

window.closeModalKerja = function () {
    $('modal-pekerjaan').classList.remove('active');
};

/* ==========================================
   MODAL EDIT
========================================== */
window.editPekerjaan = function (data) {

    $('edit_perusahaan').value = data.nama_perusahaan ?? '';
    $('edit_jabatan').value = data.jabatan ?? '';
    $('edit_bidang').value = data.bidang_pekerjaan ?? '';
    $('edit_linearitas').value = data.linearitas ?? '';
    $('edit_kota').value = data.kota ?? '';
    $('edit_alamat').value = data.alamat_lengkap ?? '';
    $('edit_gaji').value = data.gaji ?? '';
    $('edit_linkedin').value = data.link_linkedin ?? '';
    $('edit_tanggal_mulai').value = data.tanggal_mulai ?? '';
    $('edit_tanggal_selesai').value = data.tanggal_selesai ?? '';
    $('edit_masa_tunggu').value = data.masa_tunggu ?? '';
    $('edit_is_current').checked = Boolean(data.is_current);

    $('edit_lat').value = data.latitude;
    $('edit_lng').value = data.longitude;

    $('form-edit-pekerjaan').action =
        `${PEKERJAAN_URL}/${data.id}`;

    $('modal-edit-pekerjaan').classList.add('active');
    toggleTanggalSelesai('edit_is_current', 'edit_tanggal_selesai');

    const lat = data.latitude || DEFAULT_LAT;
    const lng = data.longitude || DEFAULT_LNG;

    if (!mapEdit) {

        const obj = createMap('map-kerja-edit', lat, lng);

        mapEdit = obj.map;
        markerEdit = obj.marker;

        markerEdit.on('dragend', e => {
            const pos = e.target.getLatLng();
            fillAddress(pos.lat, pos.lng, 'edit_alamat', 'edit_lat', 'edit_lng');
        });

        bindSearch(
            'edit_kota',
            mapEdit,
            markerEdit,
            'edit_alamat',
            'edit_lat',
            'edit_lng'
        );

    } else {
        mapEdit.setView([lat, lng], 15);
        markerEdit.setLatLng([lat, lng]);
    }

    setTimeout(() => mapEdit.invalidateSize(), 300);
};

window.closeEditModalKerja = function () {
    $('modal-edit-pekerjaan').classList.remove('active');
};

/* ==========================================
   MODAL STUDI LANJUT
========================================== */
window.openModalStudi = function () {
    const modal = $('modal-studi');
    if (modal) modal.classList.add('active');
    initMapStudiTambah();
};

window.closeModalStudi = function () {
    const modal = $('modal-studi');
    if (modal) modal.classList.remove('active');
};

window.editStudiLanjut = function (data) {
    $('edit_kampus').value = data.kampus ?? '';
    $('edit_alamat_kampus').value = data.alamat_kampus ?? '';
    $('edit_kota_kampus').value = data.kota_kampus ?? '';
    $('edit_provinsi_kampus').value = data.provinsi_kampus ?? '';
    $('edit_jenjang').value = data.jenjang ?? 'S2';
    $('edit_program_studi').value = data.program_studi ?? '';
    $('edit_tahun_masuk').value = data.tahun_masuk ?? '';
    $('edit_tahun_lulus').value = data.tahun_lulus ?? '';
    $('edit_status_studi').value = data.status ?? 'Sedang Berjalan';

    $('edit_studi_lat').value = data.latitude ?? '';
    $('edit_studi_lng').value = data.longitude ?? '';

    const form = $('form-edit-studi');
    if (form && STUDI_LANJUT_BASE_URL) {
        form.action = `${STUDI_LANJUT_BASE_URL}/${data.id}`;
    }

    const modal = $('modal-edit-studi');
    if (modal) modal.classList.add('active');

    const lat = parseFloat(data.latitude);
    const lng = parseFloat(data.longitude);
    initMapStudiEdit(lat, lng);
};

window.closeEditModalStudi = function () {
    const modal = $('modal-edit-studi');
    if (modal) modal.classList.remove('active');
};

/* ==========================================
   GEOCODE STUDI LANJUT
========================================== */
window.cariLokasiKampusTambah = async function () {
    const q = buildCampusQuery('studi');

    if (!q || q.length < 3) {
        alert('Isi minimal nama kampus, lalu klik cari lokasi.');
        return;
    }

    const statusEl = $('studi_cari_status');
    if (statusEl) {
        statusEl.textContent = 'Sedang mencari kampus...';
        statusEl.style.color = '#64748b';
    }

    try {
        const hasil = await searchLocationCampus(q);

        if (!hasil.length) {
            if (statusEl) {
                statusEl.textContent = 'Kampus tidak ditemukan.';
                statusEl.style.color = '#ef4444';
            }
            alert('Lokasi kampus tidak ditemukan.');
            return;
        }

        const item = hasil[0];
        const lat = parseFloat(item.lat);
        const lng = parseFloat(item.lon);

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            if (statusEl) {
                statusEl.textContent = 'Kampus ditemukan, tapi koordinat tidak valid.';
                statusEl.style.color = '#ef4444';
            }
            alert('Koordinat tidak valid dari hasil pencarian.');
            return;
        }

        $('studi_lat').value = lat;
        $('studi_lng').value = lng;

        syncCampusMarker(mapStudiTambah, markerStudiTambah, lat, lng);

        const parts = parseAddressParts(item.address);
        const alamatEl = $('studi_alamat_kampus');
        const kotaEl = $('studi_kota_kampus');
        const provEl = $('studi_provinsi_kampus');

        if (alamatEl && parts.alamat) alamatEl.value = alamatEl.value || parts.alamat;
        if (kotaEl && parts.kota) kotaEl.value = kotaEl.value || parts.kota;
        if (provEl && parts.provinsi) provEl.value = provEl.value || parts.provinsi;

        if (statusEl) {
            statusEl.textContent = 'Kampus ditemukan.';
            statusEl.style.color = '#10b981';
        }

        setTimeout(() => mapStudiTambah && mapStudiTambah.invalidateSize(), 100);
    } catch (e) {
        console.error(e);
        if (statusEl) {
            statusEl.textContent = 'Gagal mencari kampus.';
            statusEl.style.color = '#ef4444';
        }
        alert('Gagal mencari lokasi kampus.');
    }
};

window.cariLokasiKampusEdit = async function () {
    const q = buildCampusQuery('edit');

    if (!q || q.length < 3) {
        alert('Isi minimal nama kampus, lalu klik cari lokasi.');
        return;
    }

    const statusEl = $('edit_cari_status');
    if (statusEl) {
        statusEl.textContent = 'Sedang mencari kampus...';
        statusEl.style.color = '#64748b';
    }

    try {
        const hasil = await searchLocationCampus(q);

        if (!hasil.length) {
            if (statusEl) {
                statusEl.textContent = 'Kampus tidak ditemukan.';
                statusEl.style.color = '#ef4444';
            }
            alert('Lokasi kampus tidak ditemukan.');
            return;
        }

        const item = hasil[0];
        const lat = parseFloat(item.lat);
        const lng = parseFloat(item.lon);

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            if (statusEl) {
                statusEl.textContent = 'Kampus ditemukan, tapi koordinat tidak valid.';
                statusEl.style.color = '#ef4444';
            }
            alert('Koordinat tidak valid dari hasil pencarian.');
            return;
        }

        $('edit_studi_lat').value = lat;
        $('edit_studi_lng').value = lng;

        syncCampusMarker(mapStudiEdit, markerStudiEdit, lat, lng);

        const parts = parseAddressParts(item.address);
        const alamatEl = $('edit_alamat_kampus');
        const kotaEl = $('edit_kota_kampus');
        const provEl = $('edit_provinsi_kampus');

        if (alamatEl && parts.alamat) alamatEl.value = alamatEl.value || parts.alamat;
        if (kotaEl && parts.kota) kotaEl.value = kotaEl.value || parts.kota;
        if (provEl && parts.provinsi) provEl.value = provEl.value || parts.provinsi;

        if (statusEl) {
            statusEl.textContent = 'Kampus ditemukan.';
            statusEl.style.color = '#10b981';
        }

        setTimeout(() => mapStudiEdit && mapStudiEdit.invalidateSize(), 100);
    } catch (e) {
        console.error(e);
        if (statusEl) {
            statusEl.textContent = 'Gagal mencari kampus.';
            statusEl.style.color = '#ef4444';
        }
        alert('Gagal mencari lokasi kampus.');
    }
};

/* ==========================================
   DELETE SWEETALERT
========================================== */
function initDeleteButton() {

    document.querySelectorAll('.btn-delete-swal')
        .forEach(btn => {

            btn.addEventListener('click', function () {

                const form =
                    this.closest('.form-hapus-pekerjaan');

                Swal.fire({
                    title: 'Hapus pekerjaan?',
                    text: 'Data akan dihapus permanen.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then(result => {

                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
}

function initDeleteStudiButton() {
    document.querySelectorAll('.btn-delete-studi-swal')
        .forEach(btn => {
            btn.addEventListener('click', function () {
                const form = this.closest('.form-hapus-studi');

                Swal.fire({
                    title: 'Hapus studi lanjut?',
                    text: 'Data akan dihapus permanen.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then(result => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
}

/* ==========================================
   TOOLTIP
========================================== */
function initTooltip() {

    if (typeof tippy !== 'undefined') {
        tippy('[title]', {
            theme: 'material',
            placement: 'top'
        });
    }
}

function toggleTanggalSelesai(checkboxId, inputId) {
    const checkbox = $(checkboxId);
    const input = $(inputId);

    if (!checkbox || !input) {
        return;
    }

    input.disabled = checkbox.checked;

    if (checkbox.checked) {
        input.value = '';
        input.style.backgroundColor = '#f1f5f9';
    } else {
        input.style.backgroundColor = '#ffffff';
    }
}

function initTanggalPekerjaanToggle() {
    const pasangan = [
        ['tambah_is_current', 'tambah_tanggal_selesai'],
        ['edit_is_current', 'edit_tanggal_selesai']
    ];

    pasangan.forEach(([checkboxId, inputId]) => {
        const checkbox = $(checkboxId);

        if (!checkbox) {
            return;
        }

        checkbox.addEventListener('change', function () {
            toggleTanggalSelesai(checkboxId, inputId);
        });

        toggleTanggalSelesai(checkboxId, inputId);
    });
}

function resetEditFotoInput() {
    const input = $('edit-foto');
    const preview = $('edit-preview-foto');
    const resetBtn = $('btn-reset-edit-foto');

    if (input) {
        input.value = '';
    }

    if (preview) {
        preview.src = preview.dataset.defaultSrc || '/default.png';
    }

    if (resetBtn) {
        resetBtn.classList.remove('active');
    }
}

function initEditFotoUpload() {
    const input = $('edit-foto');
    const preview = $('edit-preview-foto');
    const resetBtn = $('btn-reset-edit-foto');

    if (!input || !preview || !resetBtn) {
        return;
    }

    input.addEventListener('change', function (e) {
        const file = e.target.files?.[0];

        if (!file) {
            resetEditFotoInput();
            return;
        }

        if (!file.type.startsWith('image/')) {
            resetEditFotoInput();
            alert('File foto harus berupa gambar.');
            return;
        }

        const reader = new FileReader();

        reader.onload = function (event) {
            preview.src = event.target?.result || preview.dataset.defaultSrc || '/default.png';
            resetBtn.classList.add('active');
        };

        reader.readAsDataURL(file);
    });

    resetBtn.addEventListener('click', resetEditFotoInput);
}

/* ==========================================
   INIT
========================================== */
document.addEventListener('DOMContentLoaded', () => {

    showStep(0);
    activateInitialTab();

    const obj = createMap(
        'map-tambah',
        DEFAULT_LAT,
        DEFAULT_LNG
    );

    mapProfil = obj.map;
    markerProfil = obj.marker;

    markerProfil.on('dragend', e => {
        const pos = e.target.getLatLng();
        fillAddress(pos.lat, pos.lng, 'alamat_lengkap', 'lat', 'lng');
    });

    mapProfil.on('click', e => {
        markerProfil.setLatLng(e.latlng);
        fillAddress(e.latlng.lat, e.latlng.lng, 'alamat_lengkap', 'lat', 'lng');
    });

    bindSearch(
        'kota',
        mapProfil,
        markerProfil,
        'alamat_lengkap',
        'lat',
        'lng'
    );

    fillAddress(
        DEFAULT_LAT,
        DEFAULT_LNG,
        'alamat_lengkap',
        'lat',
        'lng'
    );

    initDeleteButton();
    initDeleteStudiButton();
    initTooltip();
    initEditFotoUpload();
    initTanggalPekerjaanToggle();
});
