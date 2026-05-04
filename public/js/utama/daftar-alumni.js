document.addEventListener("DOMContentLoaded", function() {
    
    // Elemen DOM
    const btnDaftarMhs = document.getElementById('btn-daftar-mhs'); // Tombol di sidebar
    const btnOpenDirektoriRail = document.getElementById('open-direktori'); // Tombol di navigation rail
    const direktoriModal = document.getElementById('direktori-modal-overlay');
    const btnCloseDirektori = document.getElementById('close-direktori-modal');
    const listContainer = document.getElementById('list-direktori-modal');
    const inputSearch = document.getElementById('search-direktori');
    const isIdleCallbackSupported = typeof window.requestIdleCallback === 'function';

    let renderToken = 0;
    let searchDebounceTimer = null;

    const bukaModalDirektori = function(e) {
        e?.preventDefault?.();

        if (!direktoriModal) {
            return;
        }

        // Opsional: Tutup sidebar utama (expanded) jika sedang terbuka
        const sidebarUtama = document.getElementById('main-sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        if(sidebarUtama) sidebarUtama.classList.remove('active');
        if(sidebarOverlay) sidebarOverlay.classList.remove('active');

        // Munculkan Modal Direktori & cetak daftarnya
        direktoriModal.classList.add('active');
        cetakDaftarAlumni(''); // Cetak semua (tanpa filter) di awal
    };

    // 1. Buka Modal Direktori dari Sidebar & Navigation Rail
    btnDaftarMhs?.addEventListener('click', bukaModalDirektori);
    btnOpenDirektoriRail?.addEventListener('click', bukaModalDirektori);

    // 2. Tutup Modal Direktori
    if(btnCloseDirektori) {
        btnCloseDirektori.addEventListener('click', function() {
            direktoriModal.classList.remove('active');
        });
    }

    // 3. Mesin Cetak Kartu dengan Fitur Pencarian Real-Time
    function cetakDaftarAlumni(kataKunci) {
        if (!listContainer) {
            return;
        }

        const token = ++renderToken;
        const keyword = (kataKunci || '').toString().trim().toLowerCase();

        // Kosongkan dulu + beri indikator ringan supaya tidak terlihat nge-freeze.
        listContainer.innerHTML =
            `<p style="text-align:center; color:#64748b; margin-top:16px;">Memuat direktori...</p>`;

        const indices = [];
        const data = Array.isArray(alumniData) ? alumniData : [];

        for (let i = 0; i < data.length; i++) {
            const alumni = data[i] || {};
            const namaAlumni = (alumni.nama || '').toString();

            if (!keyword || namaAlumni.toLowerCase().includes(keyword)) {
                indices.push(i);
            }
        }

        if (!indices.length) {
            listContainer.innerHTML =
                `<p style="text-align:center; color:#ef4444; margin-top:20px;">Nama "${kataKunci}" tidak ditemukan.</p>`;
            return;
        }

        listContainer.innerHTML = '';

        const chunkSize = 60;
        let cursor = 0;

        const renderChunk = () => {
            if (token !== renderToken) {
                return;
            }

            const end = Math.min(indices.length, cursor + chunkSize);
            const htmlParts = [];

            for (let idx = cursor; idx < end; idx++) {
                const index = indices[idx];
                const alumni = data[index] || {};
                const namaAlumni = (alumni.nama || '').toString();
                const namaPerusahaan = (alumni.perusahaan || 'Belum Bekerja').toString();

                const avatarUrl =
                    'https://ui-avatars.com/api/?name=' +
                    encodeURIComponent(namaAlumni) +
                    '&background=004a87&color=fff&size=60';

                htmlParts.push(`
                    <div class="dir-card-modal">
                        <div class="dir-content-left">
                            <img loading="lazy" src="${avatarUrl}" class="dir-avatar-modal" alt="Avatar">
                            <div class="dir-info-modal">
                                <h4>${escapeHtml(namaAlumni)}</h4>
                                <p>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 5px; opacity: 0.7;"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                                    ${escapeHtml(namaPerusahaan)}
                                </p>
                            </div>
                        </div>

                        <div class="dir-actions-right">
                            <button class="btn-icon-bulat btn-lokasi" onclick="lihatLokasiDariModal(${index})" title="Lihat Lokasi di Peta">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            </button>
                            <button class="btn-icon-bulat btn-detail" onclick="bukaProfilAlumni(${index})" title="Lihat Profil Lengkap">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            </button>
                        </div>
                    </div>
                `);
            }

            listContainer.insertAdjacentHTML('beforeend', htmlParts.join(''));
            cursor = end;

            if (cursor < indices.length) {
                if (isIdleCallbackSupported) {
                    window.requestIdleCallback(renderChunk, { timeout: 200 });
                } else {
                    window.requestAnimationFrame(renderChunk);
                }
            }
        };

        // Render pertama setelah frame berikutnya supaya modal sempat tampil dulu (mengurangi freeze).
        window.requestAnimationFrame(renderChunk);
    }

    // 4. Deteksi Ketikan di Kolom Pencarian
    if(inputSearch) {
        inputSearch.addEventListener('input', function() {
            // Debounce supaya tidak render ulang tiap karakter (mengurangi lag)
            const value = this.value;
            if (searchDebounceTimer) {
                clearTimeout(searchDebounceTimer);
            }
            searchDebounceTimer = setTimeout(function () {
                cetakDaftarAlumni(value);
            }, 120);
        });
    }

    // 6. Logika Klik Tombol Lokasi -> Tutup Modal & Terbang ke Marker
    window.lihatLokasiDariModal = function(index) {
        // 1. Tutup modal direktori
        document.getElementById('direktori-modal-overlay').classList.remove('active');
        
        // 2. (Opsional tapi penting): Reset filter utama agar marker pasti ada di peta
        // Jika sebelumnya peta difilter, bisa jadi marker alumni ini disembunyikan
        if (typeof window.resetSemuaFilter === 'function') {
            window.resetSemuaFilter();
        } else if(typeof filterDanTampilkanMarker === 'function') {
            filterDanTampilkanMarker();
        }

        // 3. Panggil fungsi terbangKeLokasi yang sudah kita buat di filter.js
        setTimeout(function() {
            if(typeof terbangKeLokasi === 'function') {
                terbangKeLokasi(index);
            }
        }, 300); // Beri jeda sedikit agar peta selesai memuat ulang marker
    };

    function escapeHtml(str) {
        return (str || '').toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

});
