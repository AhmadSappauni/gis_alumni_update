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
        overflow: hidden; 
    }
    .main-content {
        overflow-y: auto;
        height: 100vh;
        display: flex;
        flex-direction: column;
    }

</style>
</head>

<body>
    <?php echo $__env->make('admin.komponen.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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
</html><?php /**PATH D:\Aplikasi_Skripsi\gis-alumni\resources\views/admin/layout.blade.php ENDPATH**/ ?>