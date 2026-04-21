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

    initCustomSelect();

    filterDanTampilkanMarker();
});

function getCariBerdasarkanScopes() {
    const select = document.getElementById('search-category');

    if (!select) {
        return { nama: true, perusahaan: true, wilayah: true, isSemua: true };
    }

    const values = Array.from(select.selectedOptions || [])
        .map(o => o.value)
        .filter(Boolean);

    const isSemua = values.length === 0 || values.includes('semua');

    if (isSemua) {
        return { nama: true, perusahaan: true, wilayah: true, isSemua: true };
    }

    return {
        nama: values.includes('nama'),
        perusahaan: values.includes('perusahaan'),
        wilayah: values.includes('wilayah'),
        isSemua: false
    };
}

// ======================================================
// EVENT FILTER
// ======================================================
function bindFilterEvents() {

    document.getElementById('search-category')
        ?.addEventListener('change', function () {
            const keyword =
                document.getElementById('search-input')
                    ?.value.trim() || '';

            const scopes = getCariBerdasarkanScopes();

            if (
                (!scopes.wilayah || keyword === '') &&
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

    const scopes = getCariBerdasarkanScopes();

    const keyword =
        document.getElementById('search-input')
            ?.value.trim() || '';

    if (keyword === '' && typeof window.resetHighlightWilayah === 'function') {
        window.resetHighlightWilayah();
    }

    let berhasilZoom = false;

    if (scopes.wilayah && keyword !== '') {
        berhasilZoom =
            typeof window.cariWilayahDanZoom === 'function' &&
            window.cariWilayahDanZoom(keyword);
    } else if (typeof window.resetHighlightWilayah === 'function') {
        window.resetHighlightWilayah();
    }

    filterDanTampilkanMarker();

    // Tampilkan pesan khusus hanya jika mode pencarian memang wilayah saja
    const wilayahSaja = scopes.wilayah && !scopes.nama && !scopes.perusahaan;

    if (wilayahSaja && keyword !== '') {
        if (berhasilZoom) {
            const container =
                document.getElementById('search-results');

            if (container) {
                container.innerHTML =
                    `<div class="result-count">Menampilkan wilayah: ${keyword}</div>`;
            }
        } else {
            const container =
                document.getElementById('search-results');

            if (container && container.innerHTML.trim() === '') {
                container.innerHTML =
                    `<div class="result-empty">Tidak ada alumni di wilayah "${keyword}".</div>`;
            }
        }
    }
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
            triggerTextEl.textContent = select.multiple
                ? getMultiSelectTriggerText(select)
                : (select.options[select.selectedIndex]?.text || '');
        }

        options.forEach(optionEl => {
            const value = optionEl.dataset.value;
            const optionNode = Array.from(select.options).find(o => o.value === value);
            optionEl.classList.toggle('selected', !!optionNode?.selected);
        });
    }

    window.updateCustomSelectUI = updateCustomSelectUI;

    selects.forEach(select => {

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
            ? getMultiSelectTriggerText(select)
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
            item.textContent = option.text;

            item.addEventListener('click', function () {

                if (!select.multiple) {
                    select.value = this.dataset.value;

                    trigger.querySelector('span').textContent =
                        this.textContent;

                    list.querySelectorAll('.custom-option')
                        .forEach(x => x.classList.remove('selected'));

                    this.classList.add('selected');

                    list.classList.remove('open');
                    trigger.classList.remove('active');

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

    function normalisasiTeksWilayah(teks) {
        return (teks || '')
            .toString()
            .toLowerCase()
            .replace(/kab\.?/g, '')
            .replace(/kabupaten/g, '')
            .replace(/kota/g, '')
            .replace(/\s+/g, ' ')
            .trim();
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

    const keyword =
        document.getElementById('search-input')
            ?.value.toLowerCase() || '';

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
        bidangFilterIsSemua &&
        statusKerjaFilter === 'semua' &&
        tahunFilter === 'semua' &&
        angkatanFilter === 'semua' &&
        scopes.isSemua;

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

            const cocokNama = scopes.nama && n.includes(keyword);
            const cocokPerusahaan = scopes.perusahaan && p.includes(keyword);

            let cocokWilayah = false;
            if (scopes.wilayah) {
                // Pencocokan wilayah dibuat ketat (frasa utuh) agar tidak match "Banjar" -> "Banjarbaru"
                const kotaWilayah = item.kota || '';
                const provinsiWilayah = item.provinsi || '';

                cocokWilayah =
                    cocokFrasaWilayah(kotaWilayah, keyword) ||
                    (kotaWilayah === '' && cocokFrasaWilayah(alamatLengkap || '', keyword)) ||
                    cocokFrasaWilayah(provinsiWilayah, keyword) ||
                    cocokFrasaWilayah(teksWilayahPencarian, keyword);
            }

            cocokKeyword = cocokNama || cocokPerusahaan || cocokWilayah;
        }

        const cocokLinearitas =
            linearitasFilter === 'semua' ||
            linearitas === linearitasFilter;

        const cocokBidang =
            bidangFilterIsSemua ||
            bidangFilters.includes(bidang);

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
                direction: 'right',
                sticky: true,
                opacity: 0.95,
                offset: [12, 10],
                className: 'alumni-tooltip'
            }
        );

        // Delay tooltip supaya tidak langsung muncul saat kursor lewat marker
        const TOOLTIP_DELAY_MS = 650;
        let tooltipTimer = null;
        let lastMouseLatLng = null;
        let lastOriginalEvent = null;

        // Matikan open/close tooltip bawaan Leaflet agar bisa pakai delay,
        // tapi tetap biarkan mekanisme follow-mouse (sticky) Leaflet berjalan setelah tooltip terbuka.
        if (typeof marker._openTooltip === 'function') {
            marker.off('mouseover', marker._openTooltip, marker);
        }
        if (typeof marker._closeTooltip === 'function') {
            marker.off('mouseout', marker._closeTooltip, marker);
        }

        marker.on('mousemove', function (e) {
            if (!e || !e.originalEvent || typeof map === 'undefined' || !map) {
                return;
            }

            lastOriginalEvent = e.originalEvent;

            if (typeof map.mouseEventToLatLng === 'function') {
                lastMouseLatLng = map.mouseEventToLatLng(lastOriginalEvent);
            }
        });

        marker.on('mouseover', function (e) {
            clearTimeout(tooltipTimer);

            if (e && e.originalEvent && typeof map !== 'undefined' && map && typeof map.mouseEventToLatLng === 'function') {
                lastOriginalEvent = e.originalEvent;
                lastMouseLatLng = map.mouseEventToLatLng(lastOriginalEvent);
            }

            tooltipTimer = setTimeout(function () {
                if (lastMouseLatLng) {
                    marker.openTooltip(lastMouseLatLng);
                } else if (e && e.originalEvent && typeof map !== 'undefined' && map && typeof map.mouseEventToLatLng === 'function') {
                    marker.openTooltip(map.mouseEventToLatLng(e.originalEvent));
                } else {
                    marker.openTooltip();
                }

                // Paksa posisi tooltip langsung di lokasi kursor (tanpa harus menunggu mousemove berikutnya)
                const tooltip = marker.getTooltip && marker.getTooltip();
                if (tooltip && typeof tooltip._move === 'function' && lastOriginalEvent) {
                    tooltip._move({ originalEvent: lastOriginalEvent });
                }
            }, TOOLTIP_DELAY_MS);
        });

        marker.on('mouseout', function () {
            clearTimeout(tooltipTimer);
            marker.closeTooltip();
        });

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
    const totalEl = document.getElementById('legend-total-count');

    if (bekerjaEl) bekerjaEl.textContent = `(${jumlahBekerja} orang)`;
    if (belumEl) belumEl.textContent = `(${jumlahBelumBekerja} orang)`;
    if (totalEl) totalEl.textContent = `${jumlahBekerja + jumlahBelumBekerja} orang`;
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
