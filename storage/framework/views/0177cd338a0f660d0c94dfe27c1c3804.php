<?php $__env->startPush('styles'); ?>
        <link rel="stylesheet" href="<?php echo e(asset('css/admin/import.css')); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <header class="top-header glass-panel">
        <h1>Import Data Alumni</h1>
        <p style="font-size: 13px; color: #64748b;">Gunakan file format .xlsx untuk unggah masal</p>
    </header>

    <div class="import-container" >
        <div class="glass-panel" style="padding: 30px;">
            <div id="drop-area" onclick="document.getElementById('file-input').click()">
                <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 15px; display: block; color: var(--pilkom-blue-dark); opacity: 0.6;">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                <p style="font-weight: 600; color: #1e293b;">Tarik & Lepas file Excel di sini</p>
                <p style="font-size: 12px;">Atau klik untuk memilih file dari komputer</p>
                <input type="file" id="file-input" accept=".xlsx, .xls" style="display:none;">
                <div style="display:flex; align-items:center; justify-content:center; gap:10px; flex-wrap:wrap;">
                    <span class="custom-file-label" id="file-name-display">Pilih File Alumni</span>
                    <button
                        type="button"
                        id="btn-cancel-import"
                        style="display:none; border:none; border-radius:12px; padding:10px 14px; font-weight:800; cursor:pointer; background:#e2e8f0; color:#0f172a;"
                        title="Batalkan file yang dipilih"
                    >
                        Cancel
                    </button>
                </div>
            </div>

            <div class="table-container" id="table-wrapper" style="display: none; overflow-x: auto; max-width: 100%; border-radius: 8px;">
                <table id="preview-table" style="min-width: 1300px;"> <!-- Min-width supaya kolom tidak berdempetan -->
                    <thead>
                        <tr id="preview-head-row"></tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <button id="btn-import" class="btn-tambah" style="display:none; width: 100%; margin-top: 25px; justify-content: center; padding: 15px;">
                Mulai Import Data
            </button>

            <div id="import-progress" class="glass-panel" style="display:none; margin-top: 20px; padding: 16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:10px;">
                    <div style="font-weight:600; color:#0f172a;">Memproses import...</div>
                    <div id="import-progress-text" style="font-size:12px; color:#64748b;">0/0</div>
                </div>
                <div style="width:100%; height:10px; background:#e2e8f0; border-radius:999px; overflow:hidden;">
                    <div id="import-progress-bar" style="width:0%; height:100%; background:var(--pilkom-blue-dark); border-radius:999px;"></div>
                </div>
                <div id="import-progress-subtext" style="margin-top:10px; font-size:12px; color:#64748b;">Menyiapkan data...</div>
            </div>

            <div id="import-result" class="result-success" style="display:none;">
                <h4 style="margin-bottom: 5px;">Import Selesai!</h4>
                <p id="result-text"></p>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('scripts'); ?>
    <script src="<?php echo e(asset('js/admin/import.js')); ?>">
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('admin.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Aplikasi_Skripsi\gis_alumni_4\resources\views/admin/import/import-excel.blade.php ENDPATH**/ ?>