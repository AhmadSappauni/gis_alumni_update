<aside class="sidebar glass-panel">
    <div class="sidebar-brand">
        <h2>WebGIS<br>Pil<span>kom</span></h2>
        <p>Pendidikan Komputer</p>
    </div>

    <nav class="nav-menu">
        <a href="<?php echo e(route('admin.alumni.index')); ?>" class="nav-item <?php echo e(request()->routeIs('admin.alumni.index') ? 'active' : ''); ?>">
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
            Direktori Alumnis
        </a>

        <div class="nav-dropdown <?php echo e(request()->routeIs('admin.alumni.create') || request()->routeIs('admin.alumni.import') ? 'active' : ''); ?>">
            <button class="nav-item dropdown-btn">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Tambah Alumni</span>
                <svg class="arrow-icon" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div class="dropdown-container">
                <a href="<?php echo e(route('admin.alumni.create')); ?>" class="dropdown-item <?php echo e(request()->routeIs('admin.alumni.create') ? 'current' : ''); ?>">
                    <div class="dot"></div> Tambah Perorang
                </a>
                <a href="<?php echo e(route('admin.alumni.import')); ?>" class="dropdown-item <?php echo e(request()->routeIs('admin.alumni.import') ? 'current' : ''); ?>">
                    <div class="dot"></div> Import Excel
                </a>
            </div>
        </div>

        <a href="<?php echo e(route('admin.statistik')); ?>" class="nav-item <?php echo e(request()->routeIs('admin.statistik') ? 'active' : ''); ?>">
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            Statistik Alumni
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="/" class="btn-kembali">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali ke WebGIS
        </a>
    </div>
</aside>
<?php /**PATH D:\Aplikasi_Skripsi\gis_alumni_3\resources\views/admin/komponen/sidebar.blade.php ENDPATH**/ ?>