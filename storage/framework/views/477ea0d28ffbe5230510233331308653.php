<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(asset('css/admin-style.css')); ?>">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

    <?php echo $__env->yieldPushContent('styles'); ?>

    <style>
    body {
        overflow: hidden; /* Mencegah scroll double di browser */
    }
    .main-content {
        overflow-y: auto;
        height: 100vh;
        display: flex; /* Tambahkan ini */
        flex-direction: column; /* Tambahkan ini */
    }

    /* MODAL OVERLAY */
.profil-modal-overlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0, 41, 75, 0.5);
    backdrop-filter: blur(12px);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    padding: 20px;
    transition: all 0.3s ease;
}
.profil-modal-overlay.active { display: flex; animation: fadeIn 0.3s ease; }

/* MODAL CARD */
.profil-modal-card {
    background: rgba(255, 255, 255, 0.95);
    width: 100%;
    max-width: 600px;
    border-radius: 35px;
    padding: 40px;
    position: relative;
    box-shadow: 0 30px 60px rgba(0,0,0,0.2);
    border: 1px solid rgba(255,255,255,0.8);
}

/* AVATAR */

.profil-avatar-outer {
    margin-top: -85px; /* Membuat foto agak 'keluar' sedikit */
    margin-bottom: 20px;
}
.profil-avatar-outer img {
    width: 120px; height: 120px;
    border-radius: 30px;
    object-fit: cover;
    border: 6px solid #fff;
    box-shadow: 0 15px 30px rgba(0,0,0,0.15);
}
/* BADGES */
.badge-nim { background: #f1f5f9; color: #64748b; padding: 5px 12px; border-radius: 10px; font-size: 11px; font-weight: 700; }
.badge-tahun { background: rgba(0, 74, 135, 0.1); color: #004a87; padding: 5px 12px; border-radius: 10px; font-size: 11px; font-weight: 700; }

/* INFO GROUPS */
.row-grid { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 20px; 
    margin-top: 15px;
}
.info-group {
    display: flex;
    align-items: center; /* Ikon dan teks sejajar tengah */
    gap: 15px;
    background: #f8fafc;
    padding: 15px;
    border-radius: 20px;
    border: 1px solid #f1f5f9;
}
.info-icon {
    font-size: 24px; /* Perbesar emoji */
    min-width: 45px;
    height: 45px;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.03);
}
.info-text label {
    display: block;
    font-size: 10px;
    color: #94a3b8;
    text-transform: uppercase;
    font-weight: 800;
    margin-bottom: 2px;
    letter-spacing: 0.8px;
}

.info-text p {
    margin: 0;
    color: #1e293b;
    font-weight: 700;
    font-size: 14px;
    line-height: 1.2;
}
/* CLOSE BUTTONS */
.btn-tutup-modal {
    width: 100%;
    padding: 16px;
    border: none;
    border-radius: 18px;
    background: linear-gradient(135deg, #004a87 0%, #00335d 100%);
    color: white;
    font-weight: 700;
    font-size: 15px;
    box-shadow: 0 10px 20px rgba(0, 74, 135, 0.2);
    cursor: pointer;
    transition: 0.3s;
}

.btn-tutup-modal:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 30px rgba(0, 74, 135, 0.3);
}

.close-modal-btn {
    position: absolute;
    top: 25px;
    right: 30px;
    font-size: 24px;
    color: #cbd5e1;
    cursor: pointer;
    background: none;
    border: none;
}
/* STATUS BADGE */
.profil-modal-footer {
    margin-top: 30px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
}

.status-badge {
    padding: 8px 20px;
    border-radius: 12px;
    font-weight: 800;
    font-size: 12px;
}
.status-linier { background: #dcfce7; color: #166534; }
.status-tidak { background: #fee2e2; color: #991b1b; }

@keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
</style>
</head>

<body>
    <?php echo $__env->make('admin.komponen.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('admin.komponen.modal-profil', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <main class="main-content">
        <?php echo $__env->yieldContent('content'); ?>
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Script dari halaman -->
    <?php echo $__env->yieldPushContent('scripts'); ?>
    <?php if(session('success')): ?>
    <script>
    Swal.fire({
        icon: 'success',
        title: 'Data berhasil disimpan',
        text: '<?php echo e(session("success")); ?>',
        confirmButtonColor:'#004a87',
        timer:2000,
        showConfirmButton:false
    });
    </script>
    <?php endif; ?>

    <?php if(session('error')): ?>
    <script>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '<?php echo e(session("error")); ?>'
    });
    </script>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropdownBtns = document.querySelectorAll('.dropdown-btn');

        dropdownBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const parent = this.closest('.nav-dropdown');
                parent.classList.toggle('active');
            });
        });

        // Auto open jika di halaman tertentu
        if (window.location.href.includes('create') || window.location.href.includes('import')) {
            const activeDropdown = document.querySelector('.nav-dropdown');
            if (activeDropdown) activeDropdown.classList.add('active');
        }
    });

    function showAlumniDetail(alumni) {
    const modal = document.getElementById('profil-modal-overlay');
    
    // Set Data Identitas & Skripsi
    document.getElementById('modal-nama').innerText = alumni.nama_lengkap;
    document.getElementById('modal-nim').innerText = 'NIM: ' + alumni.nim;
    document.getElementById('modal-tahun').innerText = 'Lulusan ' + alumni.tahun_lulus;
    document.getElementById('modal-skripsi').innerText = alumni.judul_skripsi || "Belum menginputkan judul skripsi.";

    // Set Foto
    const avatar = document.getElementById('modal-avatar');
    if(alumni.foto_profil) {
        avatar.src = "/storage/" + alumni.foto_profil;
    } else {
        avatar.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(alumni.nama_lengkap)}&background=004a87&color=fff&size=128`;
    }

    // Set Data Pekerjaan
    if(alumni.pekerjaan) {
        document.getElementById('modal-perusahaan').innerText = alumni.pekerjaan.nama_perusahaan || '-';
        document.getElementById('modal-jabatan').innerText = alumni.pekerjaan.jabatan || '-';
        document.getElementById('modal-bidang').innerText = alumni.pekerjaan.bidang_pekerjaan || '-';
        document.getElementById('modal-gaji').innerText = alumni.pekerjaan.gaji || 'Tidak dicantumkan';
        
        // Linearitas Badge
        const linBadge = document.getElementById('modal-linearitas');
        linBadge.innerText = alumni.pekerjaan.linearitas;
        linBadge.className = 'status-badge ' + (alumni.pekerjaan.linearitas === 'Linier' ? 'status-linier' : 'status-tidak');

        // LinkedIn
        const lnLink = document.getElementById('modal-linkedin');
        if(alumni.pekerjaan.link_linkedin) {
            lnLink.href = alumni.pekerjaan.link_linkedin;
            document.getElementById('linkedin-container').style.display = 'flex';
        } else {
            document.getElementById('linkedin-container').style.display = 'none';
        }
    } else {
        // Jika belum ada data pekerjaan
        document.getElementById('modal-perusahaan').innerText = 'Belum bekerja';
        document.getElementById('modal-jabatan').innerText = '-';
        document.getElementById('modal-bidang').innerText = '-';
        document.getElementById('modal-gaji').innerText = '-';
        document.getElementById('linkedin-container').style.display = 'none';
        document.getElementById('modal-linearitas').style.display = 'none';
    }

    modal.classList.add('active');
}

// Handler Tutup Modal
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('profil-modal-overlay');
    const closeBtns = ['close-profil-modal', 'btn-tutup-bawah'];
    
    closeBtns.forEach(id => {
        const btn = document.getElementById(id);
        if(btn) btn.onclick = () => modal.classList.remove('active');
    });

    // Tutup saat klik area luar
    window.onclick = (event) => {
        if (event.target == modal) modal.classList.remove('active');
    };
});
    </script>
    
</body>
</html><?php /**PATH D:\gis-alumni\resources\views/admin/layout.blade.php ENDPATH**/ ?>