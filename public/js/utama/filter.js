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

    populateBidangFilter();
    populateAngkatanFilter();

    initCustomSelect();

    filterDanTampilkanMarker();
});

// ======================================================
// EVENT FILTER
// ======================================================
function bindFilterEvents() {

    document.getElementById('search-category')
        ?.addEventListener('change', function () {
            const kategori = this.value || 'semua';
            const keyword =
                document.getElementById('search-input')
                    ?.value.trim() || '';

            if (
                kategori !== 'wilayah' &&
                !(kategori === 'semua' && keyword !== '') &&
                typeof window.resetHighlightWilayah === 'function'
            ) {
                window.resetHighlightWilayah();
            }

            filterDanTampilkanMarker();
        });

    document.getElementById('filter-linearitas')
        ?.addEventListener('change', filterDanTampilkanMarker);

    document.getElementById('filter-bidang')
        ?.addEventListener('change', filterDanTampilkanMarker);

    document.getElementById('filter-status-kerja')
        ?.addEventListener('change', filterDanTampilkanMarker);

    document.getElementById('filter-tahun')
        ?.addEventListener('change', filterDanTampilkanMarker);

    document.getElementById('filter-angkatan')
        ?.addEventListener('change', filterDanTampilkanMarker);

    document.getElementById('btn-search')
        ?.addEventListener('click', handleSearchSubmit);

    document.getElementById('search-input')
        ?.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                handleSearchSubmit();
            }
        });

    document.getElementById('toggle-filter')
        ?.addEventListener('click', function () {
            document.getElementById('filter-body')
                ?.classList.toggle('hidden');
        });

    document.getElementById('toggle-advanced-filter')
        ?.addEventListener('click', function () {
            document.getElementById('advanced-filter-body')
                ?.classList.toggle('hidden');

            this.classList.toggle('active');
        });

    document.getElementById('btn-reset-filter')
        ?.addEventListener('click', resetSemuaFilter);
}

function handleSearchSubmit() {

    const kategori =
        document.getElementById('search-category')
            ?.value || 'semua';

    const keyword =
        document.getElementById('search-input')
            ?.value.trim() || '';

    if (keyword === '' && typeof window.resetHighlightWilayah === 'function') {
        window.resetHighlightWilayah();
    }

    if (kategori === 'wilayah' && keyword !== '') {

        const berhasilZoom =
            typeof window.cariWilayahDanZoom === 'function' &&
            window.cariWilayahDanZoom(keyword);

        filterDanTampilkanMarker();

        if (!berhasilZoom) {
            const container =
                document.getElementById('search-results');

            if (container && container.innerHTML.trim() === '') {
                container.innerHTML =
                    `<div class="result-empty">Tidak ada alumni di wilayah "${keyword}".</div>`;
            }
        }

        return;
    }

    if (kategori === 'semua' && keyword !== '') {

        const berhasilZoom =
            typeof window.cariWilayahDanZoom === 'function' &&
            window.cariWilayahDanZoom(keyword);

        if (berhasilZoom) {
            const container =
                document.getElementById('search-results');

            if (container) {
                container.innerHTML =
                    `<div class="result-count">Menampilkan wilayah: ${keyword}</div>`;
            }
        }
        else if (typeof window.resetHighlightWilayah === 'function') {
            window.resetHighlightWilayah();
        }
    }
    else if (typeof window.resetHighlightWilayah === 'function') {
        window.resetHighlightWilayah();
    }

    filterDanTampilkanMarker();
}

function populateBidangFilter() {

    const select =
        document.getElementById('filter-bidang');

    if (!select || !Array.isArray(alumniData)) {
        return;
    }

    const bidangList = [...new Set(
        alumniData
            .map(item => (item?.bidang || '').trim())
            .filter(Boolean)
    )].sort((a, b) => a.localeCompare(b, 'id'));

    bidangList.forEach(bidang => {
        const option = document.createElement('option');
        option.value = bidang;
        option.textContent = bidang;
        select.appendChild(option);
    });
}

function populateAngkatanFilter() {

    const select =
        document.getElementById('filter-angkatan');

    if (!select || !Array.isArray(alumniData)) {
        return;
    }

    const angkatanList = [...new Set(
        alumniData
            .map(item => String(item?.angkatan || '').trim())
            .filter(Boolean)
    )].sort((a, b) => Number(b) - Number(a));

    angkatanList.forEach(angkatan => {
        const option = document.createElement('option');
        option.value = angkatan;
        option.textContent = angkatan;
        select.appendChild(option);
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

    document.addEventListener('click', function () {
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

    select.value = value;

    const wrapper = select.closest('.custom-dropdown-wrapper');
    const trigger = wrapper?.querySelector('.custom-dropdown-trigger span');
    const options = wrapper?.querySelectorAll('.custom-option') || [];

    if (trigger && select.selectedIndex >= 0) {
        trigger.textContent = select.options[select.selectedIndex].text;
    }

    options.forEach(option => {
        option.classList.toggle('selected', option.dataset.value === value);
    });
};

window.resetSemuaFilter = function () {

    if (typeof window.syncCustomSelectValue === 'function') {
        window.syncCustomSelectValue('search-category', 'semua');
        window.syncCustomSelectValue('filter-linearitas', 'semua');
        window.syncCustomSelectValue('filter-bidang', 'semua');
        window.syncCustomSelectValue('filter-status-kerja', 'semua');
        window.syncCustomSelectValue('filter-tahun', 'semua');
        window.syncCustomSelectValue('filter-angkatan', 'semua');
    } else {
        const ids = [
            'search-category',
            'filter-linearitas',
            'filter-bidang',
            'filter-status-kerja',
            'filter-tahun',
            'filter-angkatan'
        ];

        ids.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.value = 'semua';
            }
        });
    }

    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.value = '';
    }

    if (typeof window.resetHighlightWilayah === 'function') {
        window.resetHighlightWilayah();
    }

    filterDanTampilkanMarker();
};

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

    const bidangFilter =
        document.getElementById('filter-bidang')
            ?.value || 'semua';

    const statusKerjaFilter =
        document.getElementById('filter-status-kerja')
            ?.value || 'semua';

    const tahunFilter =
        document.getElementById('filter-tahun')
            ?.value || 'semua';

    const angkatanFilter =
        document.getElementById('filter-angkatan')
            ?.value || 'semua';

    window.wadahNormal.clearLayers();
    window.wadahCluster.clearLayers();

    arrayMarker = [];

    let hasilHTML = '';
    let jumlah = 0;
    let jumlahBekerja = 0;
    let jumlahBelumBekerja = 0;

    const isDefaultState =
        keyword === '' &&
        linearitasFilter === 'semua' &&
        bidangFilter === 'semua' &&
        statusKerjaFilter === 'semua' &&
        tahunFilter === 'semua' &&
        angkatanFilter === 'semua' &&
        kategori !== 'wilayah';

    alumniData.forEach(function (item, index) {

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
            const p = perusahaan.toLowerCase();
            const teksWilayahPencarian = [
                item.kota || '',
                item.provinsi || '',
                alamatLengkap || '',
                perusahaan || ''
            ].join(' ').toLowerCase();

            if (kategori === 'nama') {
                cocokKeyword = n.includes(keyword);
            }
            else if (kategori === 'perusahaan') {
                cocokKeyword = p.includes(keyword);
            }
            else if (kategori === 'wilayah') {
                cocokKeyword =
                    teksWilayahPencarian.includes(keyword);
            }
            else {
                cocokKeyword =
                    n.includes(keyword) ||
                    p.includes(keyword) ||
                    teksWilayahPencarian.includes(keyword);
            }
        }

        const cocokLinearitas =
            linearitasFilter === 'semua' ||
            linearitas === linearitasFilter;

        const cocokBidang =
            bidangFilter === 'semua' ||
            bidang === bidangFilter;

        const cocokStatusKerja =
            statusKerjaFilter === 'semua' ||
            statusKerja === statusKerjaFilter;

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

        const icon = L.icon({
            iconUrl: statusKerja === 'Belum Bekerja'
                ? '/img/icon alumni nganggur.png'
                : '/img/icon alumni kerja.png',
            iconSize: [34, 48],
            iconAnchor: [17, 48],
            popupAnchor: [0, -42]
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
        const tooltipTempat =
            statusKerja === 'Belum Bekerja'
                ? 'Belum Bekerja'
                : (perusahaan && perusahaan.trim() ? perusahaan : 'Tempat kerja belum diisi');

        marker.bindTooltip(
            `${nama} - ${tooltipTempat}`,
            {
                direction: 'top',
                sticky: false,
                opacity: 0.95,
                offset: [0, -10],
                className: 'alumni-tooltip'
            }
        );

        window.wadahNormal.addLayer(marker);
        window.wadahCluster.addLayer(marker);

        arrayMarker[index] = marker;

        if (statusKerja === 'Belum Bekerja') {
            jumlahBelumBekerja++;
        } else {
            jumlahBekerja++;
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

    perbaruiLegendaStatus(jumlahBekerja, jumlahBelumBekerja);
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

function perbaruiLegendaStatus(jumlahBekerja, jumlahBelumBekerja) {

    const bekerjaEl = document.getElementById('legend-bekerja-count');
    const belumEl = document.getElementById('legend-belum-count');

    if (bekerjaEl) bekerjaEl.textContent = `(${jumlahBekerja} orang)`;
    if (belumEl) belumEl.textContent = `(${jumlahBelumBekerja} orang)`;
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
