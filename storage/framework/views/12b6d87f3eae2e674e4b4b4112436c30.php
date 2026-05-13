<?php $__env->startPush('styles'); ?>
<link rel="stylesheet" href="<?php echo e(asset('css/admin/filter.css')); ?>">
<style >
/* Membuat area scroll halus */
.table-scroll {
    max-height: 480px;
    overflow-y: auto;
}

#list-view {
    overflow: visible; /* penting */
}
.table-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.table-scroll::-webkit-scrollbar {
    width: 6px;
}
.table-scroll::-webkit-scrollbar-thumb {
    background: rgba(0, 74, 135, 0.1);
    border-radius: 10px;
}
.table-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 74, 135, 0.3);
}

/* Memastikan header tabel tetap menempel di atas saat di-scroll */
.alumni-table thead th {
    position: sticky;
    top: 0;
    background: #f1f5f9; /* Sesuaikan dengan warna background card */
    box-shadow: inset 0 -1px 0 rgba(0,0,0,0.05);
}


</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <header class="top-header glass-panel">
        <div class="header-left">
            <h1>Data Alumni</h1>
        </div>
        
        <div class="header-center">
            <div class="search-wrapper">
                <form id="alumniSearchForm" method="GET" action="<?php echo e(route('admin.alumni.index')); ?>" class="search-box-mini" style="display:flex; align-items:center; gap:8px;">
                    <?php $__currentLoopData = request()->except(['search', 'page']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if(is_scalar($value) && $value !== ''): ?>
                            <input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($value); ?>">
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    <button type="button" title="Cari" style="all: unset; cursor: pointer; display:flex; align-items:center;">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                    <input type="text" id="alumniSearch" name="search" value="<?php echo e(request('search')); ?>"
                        placeholder="Cari alumni berdasarkan nama atau NIM..." autocomplete="off">
                </form>

                <a id="resetSearchLink" href="<?php echo e(route('admin.alumni.index', request()->except(['search', 'page']))); ?>"
                    style="margin-left: 8px; font-size: 12px; font-weight: 700; color: #004a87; text-decoration: none; <?php echo e(request()->filled('search') ? '' : 'display:none;'); ?>">
                    Reset
                </a>
                
                <div class="filter-dropdown">
                    <button class="filter-btn" onclick="toggleFilterMenu()" title="Filter Data">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                    </button>
                    <div id="filterMenu" class="filter-menu glass-panel">
                        <div class="filter-group">
                            <label>Angkatan</label>
                            <select id="filterAngkatan" onchange="applyFilters()">
                                <option value="">Semua Angkatan</option>
                                <?php $__currentLoopData = ($angkatanOptions ?? collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $angkatan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($angkatan); ?>" <?php echo e((string) request('angkatan') === (string) $angkatan ? 'selected' : ''); ?>><?php echo e($angkatan); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Tahun Lulus</label>
                            <select id="filterTahun" onchange="applyFilters()">
                                <option value="">Semua Tahun</option>
                                <?php $__currentLoopData = ($tahunLulusOptions ?? collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tahun): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($tahun); ?>" <?php echo e((string) request('tahun_lulus') === (string) $tahun ? 'selected' : ''); ?>><?php echo e($tahun); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Linearitas Pekerjaan</label>
                            <select id="filterLinear" onchange="applyFilters()">
                                <option value="">Semua Linearitas</option>
                                <option value="Sangat Erat" <?php echo e(request('linearitas') === 'Sangat Erat' ? 'selected' : ''); ?>>Sangat Erat</option>
                                <option value="Erat" <?php echo e(request('linearitas') === 'Erat' ? 'selected' : ''); ?>>Erat</option>
                                <option value="Cukup Erat" <?php echo e(request('linearitas') === 'Cukup Erat' ? 'selected' : ''); ?>>Cukup Erat</option>
                                <option value="Kurang Erat" <?php echo e(request('linearitas') === 'Kurang Erat' ? 'selected' : ''); ?>>Kurang Erat</option>
                                <option value="Tidak Erat" <?php echo e(request('linearitas') === 'Tidak Erat' ? 'selected' : ''); ?>>Tidak Erat</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Bidang Pekerjaan</label>
                            <select id="filterBidang" onchange="applyFilters()">
                                <option value="">Semua Bidang</option>
                                <?php $__currentLoopData = ($bidangOptions ?? collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bidang): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($bidang); ?>" <?php echo e((string) request('bidang_pekerjaan') === (string) $bidang ? 'selected' : ''); ?>><?php echo e($bidang); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Kelengkapan Data</label>
                            <select id="filterKelengkapan" onchange="applyFilters()">
                                <option value="">Semua</option>
                                <option value="complete" <?php echo e(request('kelengkapan') === 'complete' ? 'selected' : ''); ?>>Data Lengkap</option>
                                <option value="incomplete" <?php echo e(request('kelengkapan') === 'incomplete' ? 'selected' : ''); ?>>Belum Lengkap</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Bagian Belum Lengkap</label>
                            <select id="filterKelengkapanBagian" onchange="applyFilters()">
                                <option value="">Semua Bagian</option>
                                <option value="data_diri" <?php echo e(request('kelengkapan_bagian') === 'data_diri' ? 'selected' : ''); ?>>Data Diri</option>
                                <option value="pekerjaan" <?php echo e(request('kelengkapan_bagian') === 'pekerjaan' ? 'selected' : ''); ?>>Pekerjaan</option>
                                <option value="studi_lanjut" <?php echo e(request('kelengkapan_bagian') === 'studi_lanjut' ? 'selected' : ''); ?>>Studi Lanjut</option>
                            </select>
                        </div>
                        <button onclick="resetFilters()" id="reset-filter">
                            Reset Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="header-right">
            <div class="view-switcher">
                <button onclick="switchView('card')" id="btn-card" class="view-btn active" title="Tampilan Card">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 14a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1v-5zM14 14a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1h-4a1 1 0 01-1-1v-5z"></path>
                    </svg>
                </button>
                <button onclick="switchView('list')" id="btn-list" class="view-btn" title="Tampilan List">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <div id="alumniResults">
        <?php echo $__env->make('admin.komponen.content', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('scripts'); ?>
<script src="<?php echo e(asset('js/admin/filter-data.js')); ?>"></script>
<script>
function confirmDelete(id, nim,nama,) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Data alumni " + nama + " (NIM: " + nim + ") akan dihapus permanen!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Buat form dinamis
            let form = document.createElement('form');
            form.action = `/admin/alumni/${id}`;
            form.method = 'POST';
            form.innerHTML = `
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
            `;
            document.body.appendChild(form);
            form.submit();
        }
    })
}

function initBulkDeleteAlumni() {
    const checkboxes = Array.from(document.querySelectorAll('.alumni-bulk-checkbox'));
    const selectAllTop = document.getElementById('select-all-alumni');
    const actionBar = document.getElementById('bulk-action-bar');
    const selectLabel = document.getElementById('bulk-select-label');
    const headerCell = document.getElementById('bulk-checkbox-header');
    const rowCells = Array.from(document.querySelectorAll('.bulk-checkbox-cell'));
    const countLabel = document.getElementById('selected-alumni-count');
    const deleteBtn = document.getElementById('btn-delete-selected');
    const form = document.getElementById('bulk-delete-form');
    const inputs = document.getElementById('bulk-delete-inputs');

    if (!checkboxes.length || !selectAllTop || !actionBar || !selectLabel || !headerCell || !countLabel || !deleteBtn || !form || !inputs) {
        return;
    }

    const totalAll = Number(actionBar.dataset.total || 0);
    let selectAllGlobal = false;

    function updateBulkDeleteState() {
        const selected = checkboxes.filter(cb => cb.checked);
        const selectedCount = selectAllGlobal ? (totalAll || selected.length) : selected.length;
        const allChecked = selectAllGlobal || (selected.length > 0 && selected.length === checkboxes.length);

        countLabel.textContent = `${selectedCount} dipilih`;
        deleteBtn.disabled = selectedCount === 0;
        deleteBtn.style.cursor = selectedCount === 0 ? 'not-allowed' : 'pointer';
        deleteBtn.style.opacity = selectedCount === 0 ? '.6' : '1';

        selectAllTop.checked = allChecked;

        if (selectAllGlobal) {
            selectAllTop.indeterminate = false;
        } else {
            selectAllTop.indeterminate = selected.length > 0 && selected.length < checkboxes.length;
        }
    }

    function setAllChecked(checked) {
        checkboxes.forEach(cb => {
            cb.checked = checked;
        });

        updateBulkDeleteState();
    }

    async function enableSelectAllGlobal() {
        const countText = totalAll ? `${totalAll}` : 'semua';
        const result = await Swal.fire({
            title: 'Pilih semua data?',
            text: `Ini akan memilih ${countText} data alumni di semua halaman.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#004a87',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Pilih Semua',
            cancelButtonText: 'Batal',
            reverseButtons: true
        });

        if (!result.isConfirmed) {
            selectAllTop.checked = false;
            return;
        }

        selectAllGlobal = true;
        setAllChecked(true);
    }

    function disableSelectAllGlobal() {
        selectAllGlobal = false;
        setAllChecked(false);
    }

    selectAllTop.addEventListener('change', function () {
        if (this.checked) {
            enableSelectAllGlobal();
        } else {
            disableSelectAllGlobal();
        }
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            if (selectAllGlobal && !this.checked) {
                // jika user mulai uncheck saat mode global aktif, kembalikan ke mode biasa
                selectAllGlobal = false;
            }
            updateBulkDeleteState();
        });
    });

    deleteBtn.addEventListener('click', function () {
        const selected = checkboxes.filter(cb => cb.checked);

        if (!selectAllGlobal && !selected.length) {
            return;
        }

        const deleteCount = selectAllGlobal ? (totalAll || selected.length) : selected.length;

        Swal.fire({
            title: 'Hapus data terpilih?',
            text: `${deleteCount} data alumni akan dihapus permanen.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus Semua!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            if (selectAllGlobal) {
                inputs.innerHTML = `<input type="hidden" name="select_all" value="1">`;
            } else {
                inputs.innerHTML = selected
                    .map(cb => `<input type="hidden" name="ids[]" value="${cb.value}">`)
                    .join('');
            }

            form.submit();
        });
    });

    // default state: checkboxes always visible
    actionBar.style.display = 'flex';
    selectLabel.style.display = 'flex';
    headerCell.style.display = 'table-cell';
    rowCells.forEach(cell => {
        cell.style.display = 'table-cell';
    });

    updateBulkDeleteState();
}

document.addEventListener('DOMContentLoaded', initBulkDeleteAlumni);

</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('admin.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Aplikasi_Skripsi\gis_alumni_5\resources\views/admin/index.blade.php ENDPATH**/ ?>