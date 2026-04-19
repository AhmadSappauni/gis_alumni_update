document.addEventListener("DOMContentLoaded", function() {

    // =====================================================================
    // LOGIKA MODAL ID CARD PROFIL ALUMNI
    // =====================================================================

    // 1. Fungsi Utama: Mengisi Data dan Memunculkan Modal
    // Kita gunakan window. agar fungsi ini bisa dipanggil dari mana saja 
    // (misalnya dari klik tombol di peta atau dari daftar alumni)
    window.bukaProfilAlumni = function(index) {
        // Ambil data spesifik alumni berdasarkan index
        let alumni = alumniData[index];
        let namaAlumni = alumni.nama || 'Nama Alumni';
        let namaPerusahaan = alumni.perusahaan || 'Belum Bekerja';
        let jabatan = alumni.jabatan || '-';
        let linearitas = alumni.linearitas || 'Belum Ada Data';
        
        // --- PROSES 1: INJEKSI DATA KE HTML ---
        document.getElementById('modal-nama').textContent = namaAlumni;
        document.getElementById('modal-tahun').textContent = "Lulusan Tahun " + alumni.tahun_lulus;
        document.getElementById('modal-perusahaan').textContent = namaPerusahaan;
        document.getElementById('modal-jabatan').textContent = jabatan;
        
        // Buat avatar berdasarkan nama
        let avatarUrl = 'https://ui-avatars.com/api/?name=' + namaAlumni.replace(/\s+/g, '+') + '&background=004a87&color=fff&size=100';
        document.getElementById('modal-avatar').src = avatarUrl;
        
        // --- PROSES 2: MENGATUR LENCANA LINEARITAS ---
        let badgeLinearitas = document.getElementById('modal-linearitas');
        badgeLinearitas.textContent = linearitas;
        
        // Hapus class lama jika ada, lalu tambahkan class yang sesuai
        badgeLinearitas.className = "status-badge"; // Reset class dasar
        
        if (linearitas === 'Sangat Erat') {
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
        // Jika ID Card ini dibuka dari Buku Direktori, kita tutup dulu Buku Direktorinya
        // agar tidak ada dua modal saling tumpang tindih
        let direktoriModal = document.getElementById('direktori-modal-overlay');
        if(direktoriModal) {
            direktoriModal.classList.remove('active');
        }
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
