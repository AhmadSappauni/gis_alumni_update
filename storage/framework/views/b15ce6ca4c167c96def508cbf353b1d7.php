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
                <span class="custom-file-label" id="file-name-display">Pilih File Alumni</span>
            </div>

            <div class="table-container" id="table-wrapper" style="display: none; overflow-x: auto; max-width: 100%; border-radius: 8px;">
                <table id="preview-table" style="min-width: 1300px;"> <!-- Min-width supaya kolom tidak berdempetan -->
                    <thead>
                        <tr>
                            <th>NIM</th>
                            <th>Nama</th>
                            <th>Tahun Lulus</th>
                            <th>Perusahaan</th>
                            <th>Jabatan</th>
                            <th>Alamat instansi</th>
                            <th>Status</th>
                            <th>Email</th>
                            <th>No HP</th>
                            <th>Yudisium</th>
                            <th>TOEFL</th>
                            <th>Masa Tunggu (Hari)</th>
                            <th>Gaji</th>
                            <th>Linearitas</th>
                            <th>Studi Lanjut</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <button id="btn-import" class="btn-tambah" style="display:none; width: 100%; margin-top: 25px; justify-content: center; padding: 15px;">
                Mulai Import Data
            </button>

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

<?php echo $__env->make('admin.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Aplikasi_Skripsi\gis_alumni_2\resources\views/admin/import/import-excel.blade.php ENDPATH**/ ?>