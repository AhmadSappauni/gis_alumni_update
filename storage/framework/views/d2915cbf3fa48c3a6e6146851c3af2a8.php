<?php $__env->startPush('styles'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('css/admin/statistik.css')); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <header class="top-header glass-panel statistik-header">
        <div class="header-left">
            <h1>Statistik Alumni</h1>
            <p class="statistik-subtitle">Dashboard analisis tracer study alumni Pendidikan Komputer</p>
        </div>

        <div class="header-right statistik-actions">
            <button class="btn-export" type="button" disabled title="Export PDF (segera hadir)">
                Export PDF
            </button>
            <button class="btn-export" type="button" disabled title="Export Excel (segera hadir)">
                Export Excel
            </button>
        </div>
    </header>

    <section class="glass-panel statistik-filter">
        <div class="filter-grid">
            <div class="filter-item">
                <label>Angkatan</label>
                <select id="stat-filter-angkatan">
                    <option value="">Semua</option>
                    <?php $__currentLoopData = $angkatanOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($opt); ?>" <?php echo e((string)($initialFilters['angkatan'] ?? '') === (string)$opt ? 'selected' : ''); ?>><?php echo e($opt); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="filter-item">
                <label>Tahun Lulus</label>
                <select id="stat-filter-tahun-lulus">
                    <option value="">Semua</option>
                    <?php $__currentLoopData = $tahunLulusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($opt); ?>" <?php echo e((string)($initialFilters['tahun_lulus'] ?? '') === (string)$opt ? 'selected' : ''); ?>><?php echo e($opt); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="filter-item">
                <label>Jenis Kelamin</label>
                <select id="stat-filter-jenis-kelamin">
                    <option value="">Semua</option>
                    <?php $__currentLoopData = $jenisKelaminOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($opt); ?>" <?php echo e((string)($initialFilters['jenis_kelamin'] ?? '') === (string)$opt ? 'selected' : ''); ?>><?php echo e($opt); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="filter-item">
                <label>Status Alumni</label>
                <select id="stat-filter-status-alumni">
                    <option value="">Semua</option>
                    <option value="bekerja" <?php echo e((string)($initialFilters['status_alumni'] ?? '') === 'bekerja' ? 'selected' : ''); ?>>Bekerja</option>
                    <option value="belum_bekerja" <?php echo e((string)($initialFilters['status_alumni'] ?? '') === 'belum_bekerja' ? 'selected' : ''); ?>>Belum Bekerja</option>
                    <option value="studi_lanjut" <?php echo e((string)($initialFilters['status_alumni'] ?? '') === 'studi_lanjut' ? 'selected' : ''); ?>>Studi Lanjut</option>
                </select>
            </div>
            <div class="filter-item">
                <label>Bidang Pekerjaan</label>
                <select id="stat-filter-bidang">
                    <option value="">Semua</option>
                    <?php $__currentLoopData = $bidangOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($opt); ?>" <?php echo e((string)($initialFilters['bidang_pekerjaan'] ?? '') === (string)$opt ? 'selected' : ''); ?>><?php echo e($opt); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="filter-item">
                <label>Wilayah Kerja</label>
                <select id="stat-filter-wilayah">
                    <option value="">Semua</option>
                    <?php $__currentLoopData = $wilayahOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($opt); ?>" <?php echo e((string)($initialFilters['wilayah'] ?? '') === (string)$opt ? 'selected' : ''); ?>><?php echo e($opt); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
        </div>

        <div class="filter-actions">
            <button id="stat-apply" class="btn-apply" type="button">Terapkan Filter</button>
            <button id="stat-reset" class="btn-reset" type="button">Reset</button>
            <div id="stat-loading" class="stat-loading" aria-live="polite" style="display:none;">Memuat data…</div>
        </div>
    </section>

    <section class="statistik-kpi">
        <div class="kpi-grid">
            <div class="kpi-card glass-panel">
                <div class="kpi-label">Total Alumni</div>
                <div class="kpi-value" id="kpi-total">0</div>
            </div>
            <div class="kpi-card glass-panel">
                <div class="kpi-label">Alumni Bekerja</div>
                <div class="kpi-value" id="kpi-bekerja">0</div>
            </div>
            <div class="kpi-card glass-panel">
                <div class="kpi-label">Alumni Belum Bekerja</div>
                <div class="kpi-value" id="kpi-belum">0</div>
            </div>
            <div class="kpi-card glass-panel">
                <div class="kpi-label">Alumni Studi Lanjut</div>
                <div class="kpi-value" id="kpi-studi">0</div>
            </div>
            <div class="kpi-card glass-panel">
                <div class="kpi-label">Alumni Multi-Job</div>
                <div class="kpi-value" id="kpi-multi">0</div>
            </div>
            <div class="kpi-card glass-panel">
                <div class="kpi-label">Rata-rata Masa Tunggu</div>
                <div class="kpi-value" id="kpi-masatunggu">-</div>
                <div class="kpi-note">bulan</div>
            </div>
        </div>
    </section>

    <section class="statistik-charts">
        <div class="chart-grid">
            <div class="chart-card glass-panel">
                <div class="chart-head">
                    <div class="chart-title">Status Alumni</div>
                    <div class="chart-subtitle">Distribusi status alumni (unik)</div>
                </div>
                <div class="chart-body">
                    <canvas id="chart-status"></canvas>
                    <div class="chart-empty" data-empty-for="chart-status" hidden>Belum ada data untuk ditampilkan.</div>
                </div>
            </div>

            <div class="chart-card glass-panel">
                <div class="chart-head">
                    <div class="chart-title">Kesesuaian Bidang Ilmu</div>
                    <div class="chart-subtitle">Linearitas pekerjaan utama (aktif)</div>
                </div>
                <div class="chart-body">
                    <canvas id="chart-linearitas"></canvas>
                    <div class="chart-empty" data-empty-for="chart-linearitas" hidden>Belum ada data untuk ditampilkan.</div>
                </div>
            </div>

            <div class="chart-card glass-panel">
                <div class="chart-head">
                    <div class="chart-title">Top 5 Bidang Pekerjaan</div>
                    <div class="chart-subtitle">Bidang terbanyak (pekerjaan aktif)</div>
                </div>
                <div class="chart-body">
                    <canvas id="chart-top-bidang"></canvas>
                    <div class="chart-empty" data-empty-for="chart-top-bidang" hidden>Belum ada data untuk ditampilkan.</div>
                </div>
            </div>

            <div class="chart-card glass-panel">
                <div class="chart-head">
                    <div class="chart-title">Top 5 Wilayah Kerja</div>
                    <div class="chart-subtitle">Kota/provinsi terbanyak (pekerjaan aktif)</div>
                </div>
                <div class="chart-body">
                    <canvas id="chart-top-wilayah"></canvas>
                    <div class="chart-empty" data-empty-for="chart-top-wilayah" hidden>Belum ada data untuk ditampilkan.</div>
                </div>
            </div>

            <div class="chart-card glass-panel">
                <div class="chart-head">
                    <div class="chart-title">Masa Tunggu Kerja</div>
                    <div class="chart-subtitle">Kelompok masa tunggu (pekerjaan aktif)</div>
                </div>
                <div class="chart-body">
                    <canvas id="chart-masa-tunggu"></canvas>
                    <div class="chart-empty" data-empty-for="chart-masa-tunggu" hidden>Belum ada data untuk ditampilkan.</div>
                </div>
            </div>

            <div class="chart-card glass-panel">
                <div class="chart-head">
                    <div class="chart-title">Studi Lanjut per Jenjang</div>
                    <div class="chart-subtitle">Jenjang studi lanjut (unik per alumni)</div>
                </div>
                <div class="chart-body">
                    <canvas id="chart-studi-jenjang"></canvas>
                    <div class="chart-empty" data-empty-for="chart-studi-jenjang" hidden>Belum ada data untuk ditampilkan.</div>
                </div>
            </div>

            <div class="chart-card glass-panel">
                <div class="chart-head">
                    <div class="chart-title">Top Kampus Studi Lanjut</div>
                    <div class="chart-subtitle">5 kampus terbanyak (unik per alumni)</div>
                </div>
                <div class="chart-body">
                    <canvas id="chart-top-kampus"></canvas>
                    <div class="chart-empty" data-empty-for="chart-top-kampus" hidden>Belum ada data untuk ditampilkan.</div>
                </div>
            </div>

            <div class="chart-card glass-panel chart-card-wide">
                <div class="chart-head">
                    <div class="chart-title">Tren Alumni per Angkatan</div>
                    <div class="chart-subtitle">Jumlah alumni per angkatan</div>
                </div>
                <div class="chart-body">
                    <canvas id="chart-tren-angkatan"></canvas>
                    <div class="chart-empty" data-empty-for="chart-tren-angkatan" hidden>Belum ada data untuk ditampilkan.</div>
                </div>
            </div>

            <div class="chart-card glass-panel chart-card-wide">
                <div class="chart-head">
                    <div class="chart-title">Tren Keterserapan Kerja per Angkatan</div>
                    <div class="chart-subtitle">Bekerja vs belum bekerja per angkatan</div>
                </div>
                <div class="chart-body">
                    <canvas id="chart-tren-serap"></canvas>
                    <div class="chart-empty" data-empty-for="chart-tren-serap" hidden>Belum ada data untuk ditampilkan.</div>
                </div>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        window.__STATISTIK_ENDPOINT__ = <?php echo json_encode(route('admin.statistik.data'), 15, 512) ?>;
    </script>
    <script src="<?php echo e(asset('js/admin/statistik.js')); ?>"></script>
<?php $__env->stopPush(); ?>


<?php echo $__env->make('admin.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Aplikasi_Skripsi\gis_alumni_3\resources\views/admin/statistik/index.blade.php ENDPATH**/ ?>