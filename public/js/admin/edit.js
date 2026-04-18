/* ==========================================
   CONFIG
========================================== */
const config = window.editConfig || {};

const DEFAULT_LAT = config.oldLat ?? -3.316694;
const DEFAULT_LNG = config.oldLng ?? 114.590111;
const PEKERJAAN_URL = config.pekerjaanUrl ?? "/admin/pekerjaan";

const TYPE_DELAY = 800;

/* ==========================================
   GLOBAL
========================================== */
let mapProfil, markerProfil;
let mapTambah, markerTambah;
let mapEdit, markerEdit;

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

/* ==========================================
   MAP
========================================== */
function createMap(id, lat, lng) {

    const map = L.map(id).setView([lat, lng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    const marker = L.marker([lat, lng], {
        draggable: true
    }).addTo(map);

    return { map, marker };
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
    }, 300);
};

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

    $('edit_lat').value = data.latitude;
    $('edit_lng').value = data.longitude;

    $('form-edit-pekerjaan').action =
        `${PEKERJAAN_URL}/${data.id}`;

    $('modal-edit-pekerjaan').classList.add('active');

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

/* ==========================================
   INIT
========================================== */
document.addEventListener('DOMContentLoaded', () => {

    showStep(0);

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
    initTooltip();
});