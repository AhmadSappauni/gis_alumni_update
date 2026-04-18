// ======================================================
// FILTER.JS FINAL PROFESSIONAL
// Support:
// ✔ Search alumni / perusahaan
// ✔ Filter linearitas
// ✔ Filter tahun lulus
// ✔ Filter wilayah
// ✔ Sidebar hasil pencarian
// ✔ Marker normal + cluster
// ✔ Status kerja / belum kerja
// ✔ Lokasi perusahaan / domisili otomatis
// ======================================================

let arrayMarker = [];

// ======================================================
// WADAH MARKER
// ======================================================
window.wadahNormal = L.featureGroup();

window.wadahCluster = L.markerClusterGroup({
    chunkedLoading: true,
    spiderfyOnMaxZoom: true,
    showCoverageOnHover: false,
    maxClusterRadius: 50
});

window.statusClusterAktif = false;

// ======================================================
// SAAT DOM READY
// ======================================================
document.addEventListener("DOMContentLoaded", function () {

    bindFilterEvents();

    initCustomSelect();

    filterDanTampilkanMarker();
});

// ======================================================
// EVENT FILTER
// ======================================================
function bindFilterEvents() {

    document.getElementById('search-category')
        ?.addEventListener('change', filterDanTampilkanMarker);

    document.getElementById('filter-linearitas')
        ?.addEventListener('change', filterDanTampilkanMarker);

    document.getElementById('filter-tahun')
        ?.addEventListener('change', filterDanTampilkanMarker);

    document.getElementById('filter-wilayah')
        ?.addEventListener('input', filterDanTampilkanMarker);

    document.getElementById('btn-search')
        ?.addEventListener('click', filterDanTampilkanMarker);

    document.getElementById('search-input')
        ?.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                filterDanTampilkanMarker();
            }
        });

    document.getElementById('toggle-filter')
        ?.addEventListener('click', function () {
            document.getElementById('filter-body')
                ?.classList.toggle('hidden');
        });
}

// ======================================================
// CUSTOM SELECT PREMIUM
// ======================================================
function initCustomSelect() {

    const selects = document.querySelectorAll('.custom-select');

    selects.forEach(select => {

        const wrapper = document.createElement('div');
        wrapper.className = 'custom-dropdown-wrapper';

        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);

        select.style.display = 'none';

        const trigger = document.createElement('div');
        trigger.className = 'custom-dropdown-trigger';

        trigger.innerHTML =
            `<span>${select.options[select.selectedIndex].text}</span>
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
            item.textContent = option.text;

            item.addEventListener('click', function () {

                select.value = this.dataset.value;

                trigger.querySelector('span').textContent =
                    this.textContent;

                list.querySelectorAll('.custom-option')
                    .forEach(x => x.classList.remove('selected'));

                this.classList.add('selected');

                list.classList.remove('open');
                trigger.classList.remove('active');

                select.dispatchEvent(new Event('change'));
            });

            list.appendChild(item);
        });

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

    document.addEventListener('click', function () {
        document.querySelectorAll('.custom-dropdown-options')
            .forEach(x => x.classList.remove('open'));

        document.querySelectorAll('.custom-dropdown-trigger')
            .forEach(x => x.classList.remove('active'));
    });
}

// ======================================================
// TAMPILAN PETA
// ======================================================
window.perbaruiTampilanPeta = function () {

    if (map.hasLayer(window.wadahNormal)) {
        map.removeLayer(window.wadahNormal);
    }

    if (map.hasLayer(window.wadahCluster)) {
        map.removeLayer(window.wadahCluster);
    }

    if (window.statusClusterAktif) {
        map.addLayer(window.wadahCluster);
    } else {
        map.addLayer(window.wadahNormal);
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

        default:
            return 'status-tidak';
    }
}

// ======================================================
// FILTER UTAMA
// ======================================================
function filterDanTampilkanMarker() {

    const keyword =
        document.getElementById('search-input')
            ?.value.toLowerCase() || '';

    const kategori =
        document.getElementById('search-category')
            ?.value || 'semua';

    const linearitasFilter =
        document.getElementById('filter-linearitas')
            ?.value || 'semua';

    const tahunFilter =
        document.getElementById('filter-tahun')
            ?.value || 'semua';

    const wilayahFilter =
        document.getElementById('filter-wilayah')
            ?.value.toLowerCase() || '';

    window.wadahNormal.clearLayers();
    window.wadahCluster.clearLayers();

    arrayMarker = [];

    let hasilHTML = '';
    let jumlah = 0;

    const isDefaultState =
        keyword === '' &&
        linearitasFilter === 'semua' &&
        tahunFilter === 'semua' &&
        wilayahFilter === '';

    alumniData.forEach(function (item, index) {

        const nama = item.alumni?.nama_lengkap || '';
        const perusahaan = item.perusahaan?.nama_perusahaan || '-';
        const jabatan = item.jabatan || '-';
        const tahunLulus = item.alumni?.akademik?.tahun_lulus || '-';

        const statusKerja =
            item.status_kerja || 'Belum Bekerja';

        const linearitas =
            item.perusahaan?.linearitas || 'Tidak Erat';

        let latitude = null;
        let longitude = null;
        let alamatLengkap = '';

        // =====================================
        // Tentukan sumber lokasi
        // =====================================
        if (statusKerja === 'Bekerja') {

            latitude =
                parseFloat(item.perusahaan?.lokasi_utama?.latitude);

            longitude =
                parseFloat(item.perusahaan?.lokasi_utama?.longitude);

            alamatLengkap =
                item.perusahaan?.lokasi_utama?.alamat_lengkap || '';

        } else {

            latitude =
                parseFloat(item.alumni?.alamat?.latitude);

            longitude =
                parseFloat(item.alumni?.alamat?.longitude);

            alamatLengkap =
                item.alumni?.alamat?.alamat_lengkap || '';
        }

        if (!latitude || !longitude) return;

        // =====================================
        // FILTER
        // =====================================
        let cocokKeyword = true;

        if (keyword !== '') {

            const n = nama.toLowerCase();
            const p = perusahaan.toLowerCase();

            if (kategori === 'nama') {
                cocokKeyword = n.includes(keyword);
            }
            else if (kategori === 'perusahaan') {
                cocokKeyword = p.includes(keyword);
            }
            else {
                cocokKeyword =
                    n.includes(keyword) ||
                    p.includes(keyword);
            }
        }

        const cocokLinearitas =
            linearitasFilter === 'semua' ||
            linearitas === linearitasFilter;

        let cocokTahun = true;

        if (tahunFilter !== 'semua') {

            const selisih =
                new Date().getFullYear() -
                parseInt(tahunLulus);

            cocokTahun =
                selisih >= 0 &&
                selisih <= parseInt(tahunFilter);
        }

        const teksWilayah =
            (alamatLengkap + ' ' + perusahaan).toLowerCase();

        const cocokWilayah =
            wilayahFilter === '' ||
            teksWilayah.includes(wilayahFilter);

        if (
            !cocokKeyword ||
            !cocokLinearitas ||
            !cocokTahun ||
            !cocokWilayah
        ) return;

        // =====================================
        // WARNA MARKER
        // =====================================
        let warna;

        if (statusKerja === 'Belum Bekerja') {
            warna = 'grey';
        } else {
            warna = getMarkerColor(linearitas);
        }

        const icon = L.icon({
            iconUrl:
                'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-' +
                warna + '.png',

            shadowUrl:
                'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',

            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34]
        });

        const marker =
            L.marker([latitude, longitude], { icon });

        const avatar =
            'https://ui-avatars.com/api/?name=' +
            encodeURIComponent(nama) +
            '&background=004a87&color=fff&size=60&rounded=true';

        const infoKerja =
            statusKerja === 'Belum Bekerja'
                ?
                `
                <div class="info-row">
                    <span class="icon">🏠</span>
                    <span>Domisili Saat Ini</span>
                </div>

                <div class="info-row">
                    <span class="icon">📌</span>
                    <span>${alamatLengkap}</span>
                </div>
                `
                :
                `
                <div class="info-row">
                    <span class="icon">🏢</span>
                    <span><b>${perusahaan}</b></span>
                </div>

                <div class="info-row">
                    <span class="icon">💼</span>
                    <span>${jabatan}</span>
                </div>
                `;

        const popup = `
            <div class="premium-popup">

                <div class="popup-cover"></div>

                <div class="popup-avatar">
                    <img src="${avatar}">
                </div>

                <div class="popup-body">

                    <h3 class="popup-name">${nama}</h3>

                    <span class="popup-year">
                        Lulusan Tahun ${tahunLulus}
                    </span>

                    <div class="popup-info">
                        ${infoKerja}
                    </div>

                    <div class="popup-footer">
                        <span class="popup-badge">
                            ${statusKerja === 'Belum Bekerja'
                                ? 'Belum Bekerja'
                                : linearitas}
                        </span>
                    </div>

                </div>
            </div>
        `;

        marker.bindPopup(popup);

        window.wadahNormal.addLayer(marker);
        window.wadahCluster.addLayer(marker);

        arrayMarker[index] = marker;

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
                            ? '🏠 Belum Bekerja'
                            : '🏢 ' + perusahaan
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

    window.perbaruiTampilanPeta();

    const container =
        document.getElementById('search-results');

    if (!container) return;

    if (isDefaultState) {
        container.innerHTML = '';
    }
    else if (jumlah > 0) {
        container.innerHTML =
            `<div class="result-count">
                Ditemukan ${jumlah} Alumni
             </div>` + hasilHTML;
    }
    else {
        container.innerHTML =
            `<div class="result-empty">
                Data tidak ditemukan.
             </div>`;
    }
}

// ======================================================
// FLY TO MARKER
// ======================================================
function terbangKeLokasi(index) {

    const marker = arrayMarker[index];

    if (!marker) return;

    const posisi = marker.getLatLng();

    map.flyTo(posisi, 16, {
        animate: true,
        duration: 1.5
    });

    setTimeout(function () {
        marker.openPopup();
    }, 350);
}