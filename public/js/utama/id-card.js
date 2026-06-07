document.addEventListener("DOMContentLoaded", function() {

    // =====================================================================
    // LOGIKA MODAL ID CARD PROFIL ALUMNI
    // =====================================================================

    function nilaiTeks(value) {
        const text = (value || '').toString().trim();
        return text && text !== '-' && text.toLowerCase() !== 'null' ? text : '';
    }

    function normalisasiUrlLinkedIn(value) {
        const raw = nilaiTeks(value);
        if (!raw) {
            return '';
        }

        const withScheme = /^https?:\/\//i.test(raw) ? raw : `https://${raw}`;

        try {
            const url = new URL(withScheme);
            if (!['http:', 'https:'].includes(url.protocol)) {
                return '';
            }

            return url.toString();
        } catch (_) {
            return '';
        }
    }

    function setHidden(el, hidden) {
        if (el) {
            el.hidden = hidden;
        }
    }

    function setEditDataLink(alumni) {
        const editLinkEl = document.getElementById('btn-edit-data-alumni');
        if (!editLinkEl) {
            return;
        }

        const alumniId = (alumni?.alumni_id ?? alumni?.id ?? '').toString().trim();
        const urlTemplate = editLinkEl.dataset.editUrlTemplate || '';

        if (!alumniId || !urlTemplate) {
            editLinkEl.href = '#';
            setHidden(editLinkEl, true);
            return;
        }

        editLinkEl.href = urlTemplate.replace('__ALUMNI_ID__', encodeURIComponent(alumniId));
        setHidden(editLinkEl, false);
    }

    function isiModalProfil(alumni) {
        const data = alumni || {};

        let namaAlumni = data.nama || 'Nama Alumni';
        let nim = (data.nim || '').toString().trim();
        let statusKerja = (data.status || '').toString().trim();
        let isBekerja = statusKerja === 'Bekerja';

        let namaPerusahaan = (data.perusahaan || '').toString().trim();
        let jabatan = (data.jabatan || '').toString().trim();
        let linearitas = (
            data.linearitas ||
            data.kesesuaian_bidang ||
            data.kesesuaian_bidang_ilmu ||
            ''
        ).toString().trim();
        let bidangPekerjaan = (data.bidang_pekerjaan || data.bidang || '').toString().trim();
        const judulSkripsi = nilaiTeks(
            data.judul_skripsi ||
            data.skripsi ||
            data.judul_tugas_akhir ||
            data.judul_ta
        );
        const linkLinkedIn = normalisasiUrlLinkedIn(
            data.link_linkedin ||
            data.linkedin ||
            data.linkedin_url ||
            data.linkedIn
        );

        const alamatRaw = (data.alamat || data.alamat_kantor || data.alamat_perusahaan || data.alamat_lengkap_perusahaan || data.alamat_alumni || '').toString().trim();
        const kota = (data.kota || data.kota_perusahaan || data.kota_kampus || '').toString().trim();
        const provinsi = (data.provinsi || data.provinsi_perusahaan || data.provinsi_kampus || '').toString().trim();

        const lokasiParts = [];
        if (kota) lokasiParts.push(kota);
        if (provinsi && provinsi !== kota) lokasiParts.push(provinsi);

        const lokasiRingkas = lokasiParts.join(', ');

        let alamatTampil = '';
        if (alamatRaw && lokasiRingkas) {
            alamatTampil = `${alamatRaw}, ${lokasiRingkas}`;
        } else if (alamatRaw) {
            alamatTampil = alamatRaw;
        } else if (lokasiRingkas) {
            alamatTampil = lokasiRingkas;
        } else {
            alamatTampil = '-';
        }

        if (!bidangPekerjaan) bidangPekerjaan = '-';
        if (!jabatan) jabatan = '-';

        // --- PROSES 1: INJEKSI DATA KE HTML ---
        document.getElementById('modal-nama').textContent = namaAlumni;
        document.getElementById('modal-nim').textContent = `NIM: ${nim || '-'}`;
        document.getElementById('modal-tahun').textContent = "Lulusan Tahun " + (data.tahun_lulus || '-');

        const lokasiLabelEl = document.getElementById('modal-lokasi-label');
        const alamatLabelEl = document.getElementById('modal-alamat-label');

        if (isBekerja) {
            if (lokasiLabelEl) lokasiLabelEl.textContent = 'Tempat Kerja';
            document.getElementById('modal-perusahaan').textContent = namaPerusahaan || '-';
            if (alamatLabelEl) alamatLabelEl.textContent = 'Alamat Kantor';

            if (!linearitas) {
                linearitas = 'Tidak Erat';
            }
        } else {
            if (lokasiLabelEl) lokasiLabelEl.textContent = 'Status';
            document.getElementById('modal-perusahaan').textContent = 'Belum Bekerja';
            if (alamatLabelEl) alamatLabelEl.textContent = 'Domisili Alumni';

            // Untuk alumni belum bekerja, jabatan/bidang/linearitas tampil "-"
            jabatan = '-';
            bidangPekerjaan = '-';
            linearitas = '-';
        }

        document.getElementById('modal-alamat').textContent = alamatTampil || '-';
        document.getElementById('modal-jabatan').textContent = jabatan || '-';
        document.getElementById('modal-bidang-pekerjaan').textContent = bidangPekerjaan || '-';

        const extraInfoEl = document.getElementById('modal-extra-info');
        const skripsiCardEl = document.getElementById('modal-skripsi-card');
        const judulSkripsiEl = document.getElementById('modal-judul-skripsi');
        const linkedInCardEl = document.getElementById('modal-linkedin-card');

        if (judulSkripsiEl) {
            judulSkripsiEl.textContent = judulSkripsi;
        }

        if (linkedInCardEl) {
            linkedInCardEl.href = linkLinkedIn || '#';
        }

        setHidden(skripsiCardEl, !judulSkripsi);
        setHidden(linkedInCardEl, !linkLinkedIn);
        setHidden(extraInfoEl, !judulSkripsi && !linkLinkedIn);
        setEditDataLink(data);

        // Buat avatar berdasarkan nama
        let avatarUrl = 'https://ui-avatars.com/api/?name=' + namaAlumni.replace(/\s+/g, '+') + '&background=004a87&color=fff&size=100';
        document.getElementById('modal-avatar').src = avatarUrl;

        // --- PROSES 2: MENGATUR LENCANA LINEARITAS ---
        let badgeLinearitas = document.getElementById('modal-linearitas');
        badgeLinearitas.textContent = linearitas || '-';

        // Hapus class lama jika ada, lalu tambahkan class yang sesuai
        badgeLinearitas.className = "status-badge"; // Reset class dasar

        if (!linearitas || linearitas === '-' || linearitas === 'Belum Ada Data') {
            badgeLinearitas.classList.add("status-tidak");
            badgeLinearitas.style.background = "#e2e8f0";
            badgeLinearitas.style.color = "#475569";
        } else if (linearitas === 'Sangat Erat') {
            badgeLinearitas.classList.add("status-sangat");
            badgeLinearitas.style.background = "#dcfce7";
            badgeLinearitas.style.color = "#166534";
        } else if (linearitas === 'Erat') {
            badgeLinearitas.classList.add("status-erat");
            badgeLinearitas.style.background = "#dbeafe";
            badgeLinearitas.style.color = "#1d4ed8";
        } else if (linearitas === 'Cukup Erat') {
            badgeLinearitas.classList.add("status-cukup");
            badgeLinearitas.style.background = "#fef3c7";
            badgeLinearitas.style.color = "#b45309";
        } else if (linearitas === 'Kurang Erat') {
            badgeLinearitas.classList.add("status-kurang");
            badgeLinearitas.style.background = "#fed7aa";
            badgeLinearitas.style.color = "#c2410c";
        } else {
            badgeLinearitas.classList.add("status-tidak");
            badgeLinearitas.style.background = "#fee2e2";
            badgeLinearitas.style.color = "#b91c1c";
        }

        // --- PROSES 3: TAMPILKAN MODALNYA ---
        document.getElementById('profil-modal-overlay').classList.add('active');

        // --- PROSES 4: TUTUP MODAL LAIN (Opsional) ---
        let direktoriModal = document.getElementById('direktori-modal-overlay');
        if(direktoriModal) {
            direktoriModal.classList.remove('active');
        }
    }

    // 1. Fungsi Utama: Mengisi Data dan Memunculkan Modal
    // Kita gunakan window. agar fungsi ini bisa dipanggil dari mana saja 
    // (misalnya dari klik tombol di peta atau dari daftar alumni)
    window.bukaProfilAlumni = function(index) {
        // Ambil data spesifik alumni berdasarkan index
        const arr = Array.isArray(alumniData) ? alumniData : [];
        const alumni = arr[index];
        isiModalProfil(alumni);
    };

    window.bukaProfilAlumniById = function(alumniId) {
        const id = (alumniId || '').toString();
        if (!id) {
            return;
        }

        let alumni = null;

        if (window.alumniDataById && window.alumniDataById[id]) {
            alumni = window.alumniDataById[id];
        } else if (Array.isArray(alumniData)) {
            alumni = alumniData.find(x => ((x?.alumni_id ?? x?.id ?? '') + '') === id) || null;
        }

        if (!alumni) {
            return;
        }

        isiModalProfil(alumni);
    };

    // 2. Logika Menutup Modal ID Card
    const btnTutupAtas = document.getElementById('close-profil-modal');
    const btnTutupBawah = document.getElementById('btn-tutup-bawah');
    const overlayModal = document.getElementById('profil-modal-overlay');

    // Fungsi tutup
    function tutupProfil() {
        overlayModal.classList.remove('active');
    }

    // Pasang aksi klik
    if(btnTutupAtas) btnTutupAtas.addEventListener('click', tutupProfil);
    if(btnTutupBawah) btnTutupBawah.addEventListener('click', tutupProfil);

});
