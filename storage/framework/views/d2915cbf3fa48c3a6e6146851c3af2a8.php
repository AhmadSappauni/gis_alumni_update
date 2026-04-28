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
            <button class="btn-export btn-export-xml" type="button" onclick="alert('Fitur export sedang disiapkan')">
                Export XML
            </button>
        </div>
    </header>

    <section class="glass-panel statistik-filter">
        <?php
            $hasInitialFilters = collect($initialFilters ?? [])
                ->filter(fn ($v) => $v !== null && trim((string) $v) !== '')
                ->isNotEmpty();
        ?>

        <div class="filter-head">
            <button id="stat-filter-toggle" class="btn-filter-toggle" type="button" aria-controls="stat-filter-panel"
                aria-expanded="<?php echo e($hasInitialFilters ? 'true' : 'false'); ?>">
                Filter Statistik
            </button>
        </div>

        <div id="stat-filter-panel" class="filter-panel" <?php echo e($hasInitialFilters ? '' : 'hidden'); ?>>
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
        </div>
    </section>

    <section id="stat-insight" class="statistik-insight glass-panel" hidden>
        <div class="insight-head">
            <div class="insight-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2a7 7 0 0 0-4 12c.3.2.5.6.5 1v1.5A1.5 1.5 0 0 0 10 18h4a1.5 1.5 0 0 0 1.5-1.5V15c0-.4.2-.8.5-1A7 7 0 0 0 12 2z"></path>
                    <path d="M9.5 22h5"></path>
                </svg>
            </div>
            <div class="insight-meta">
                <div class="insight-title">Insight Utama</div>
                <div class="insight-subtitle">Ringkasan cepat dari data yang ditampilkan</div>
            </div>
        </div>
        <ul id="stat-insight-list" class="insight-list"></ul>
    </section>

    <section class="statistik-kpi">
        <div class="kpi-grid">
            <div class="kpi-card glass-panel kpi-accent-blue">
                <div class="kpi-top">
                    <div class="kpi-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21V7"></path>
                            <path d="M16 21V3"></path>
                            <path d="M12 21v-8"></path>
                            <path d="M8 21v-4"></path>
                            <path d="M4 21v-6"></path>
                        </svg>
                    </div>
                    <div class="kpi-label">Total Alumni</div>
                </div>
                <div class="kpi-value" id="kpi-total">0</div>
                <div class="kpi-sub">Jumlah alumni terdata</div>
            </div>

            <div class="kpi-card glass-panel kpi-accent-green">
                <div class="kpi-top">
                    <div class="kpi-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 7h-7"></path>
                            <path d="M14 17H5"></path>
                            <circle cx="17" cy="17" r="3"></circle>
                            <circle cx="7" cy="7" r="3"></circle>
                        </svg>
                    </div>
                    <div class="kpi-label">Alumni Bekerja</div>
                </div>
                <div class="kpi-value" id="kpi-bekerja">0</div>
                <div class="kpi-sub" id="kpi-bekerja-sub"></div>
            </div>

            <div class="kpi-card glass-panel kpi-accent-red">
                <div class="kpi-top">
                    <div class="kpi-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M15 9l-6 6"></path>
                            <path d="M9 9l6 6"></path>
                        </svg>
                    </div>
                    <div class="kpi-label">Belum Bekerja</div>
                </div>
                <div class="kpi-value" id="kpi-belum">0</div>
                <div class="kpi-sub">Perlu dukungan karier</div>
            </div>

            <div class="kpi-card glass-panel kpi-accent-purple">
                <div class="kpi-top">
                    <div class="kpi-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 10v6M2 10v6"></path>
                            <path d="M6 12h12"></path>
                            <path d="M7 10h10"></path>
                            <path d="M8 8h8"></path>
                            <path d="M9 6h6"></path>
                        </svg>
                    </div>
                    <div class="kpi-label">Studi Lanjut</div>
                </div>
                <div class="kpi-value" id="kpi-studi">0</div>
                <div class="kpi-sub">Melanjutkan pendidikan</div>
            </div>

            <div class="kpi-card glass-panel kpi-accent-yellow">
                <div class="kpi-top">
                    <div class="kpi-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19h16"></path>
                            <path d="M4 15h10"></path>
                            <path d="M4 11h16"></path>
                            <path d="M4 7h10"></path>
                        </svg>
                    </div>
                    <div class="kpi-label">Multi-Job</div>
                </div>
                <div class="kpi-value" id="kpi-multi">0</div>
                <div class="kpi-sub">Memiliki >1 pekerjaan</div>
            </div>

            <div class="kpi-card glass-panel kpi-accent-sky">
                <div class="kpi-top">
                    <div class="kpi-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 6v6l3 2"></path>
                        </svg>
                    </div>
                    <div class="kpi-label">Rata-rata Masa Tunggu</div>
                </div>
                <div class="kpi-value"><span id="kpi-masatunggu">-</span> <span class="kpi-unit">bulan</span></div>
                <div class="kpi-sub">Estimasi waktu mendapat kerja</div>
            </div>
        </div>
    </section>

    <section class="statistik-charts">
        <div class="chart-section">
            <div class="chart-section-head">
                <div class="chart-section-title">Ringkasan Ketenagakerjaan</div>
                <div class="chart-section-sub">Gambaran status kerja dan masa tunggu</div>
            </div>
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
                        <div class="chart-title">Masa Tunggu Kerja</div>
                        <div class="chart-subtitle">Kelompok masa tunggu (pekerjaan aktif)</div>
                    </div>
                    <div class="chart-body">
                        <canvas id="chart-masa-tunggu"></canvas>
                        <div class="chart-empty" data-empty-for="chart-masa-tunggu" hidden>Belum ada data untuk ditampilkan.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="chart-section">
            <div class="chart-section-head">
                <div class="chart-section-title">Relevansi Karier</div>
                <div class="chart-section-sub">Kesesuaian bidang ilmu dan bidang pekerjaan dominan</div>
            </div>
            <div class="chart-grid">
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
            </div>
        </div>

        <div class="chart-section">
            <div class="chart-section-head">
                <div class="chart-section-title">Persebaran Wilayah</div>
                <div class="chart-section-sub">Wilayah kerja dan kampus studi lanjut terbanyak</div>
            </div>
            <div class="chart-grid">
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
                        <div class="chart-title">Top Kampus Studi Lanjut</div>
                        <div class="chart-subtitle">5 kampus terbanyak (unik per alumni)</div>
                    </div>
                    <div class="chart-body">
                        <canvas id="chart-top-kampus"></canvas>
                        <div class="chart-empty" data-empty-for="chart-top-kampus" hidden>Belum ada data untuk ditampilkan.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="chart-section">
            <div class="chart-section-head">
                <div class="chart-section-title">Tren Alumni</div>
                <div class="chart-section-sub">Perubahan jumlah alumni dan keterserapan kerja per angkatan</div>
            </div>
            <div class="chart-grid">
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