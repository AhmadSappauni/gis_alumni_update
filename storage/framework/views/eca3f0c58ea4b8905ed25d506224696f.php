<div class="filter-panel">

    <div class="panel-header">
        <div class="header-left" style="display: flex; align-items: center; gap: 12px;">
            <button id="open-sidebar" class="hamburger-btn" title="Buka Menu Utama">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
            </button>
            <div class="header-text">
                <h3>GIS ALUMNI</h3>
                <p>Pendidikan Komputer ULM</p>
            </div>
        </div>
        
        <div class="panel-actions">
            <button id="toggle-filter" class="toggle-btn" title="Tampilkan/Sembunyikan Filter">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
            </button>
            
            <button id="collapse-filter-panel" class="collapse-panel-btn" title="Sembunyikan/Tampilkan Panel Filter" aria-expanded="true">
                <svg class="icon-collapse" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                <svg class="icon-expand" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
        </div>
    </div>

    <div class="search-container">
        <div class="search-box">
            <input type="text" id="search-input" placeholder="Ketik nama atau tempat kerja...">
            <button id="btn-search" class="search-action-btn" title="Klik untuk mencari">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </button>
        </div>
    </div>
    <div class="scrollable-content">
        <div id="filter-body" class="filter-body hidden">
            <div class="filter-body-inner">

                <div class="filter-section">
                    <label class="filter-label">Cari Berdasarkan:</label>
                    <select id="search-category" class="custom-select">
                        <option value="semua">Semua</option>
                        <option value="nama">Nama</option>
                        <option value="perusahaan">Tempat Kerja</option>
                        <option value="wilayah">Wilayah</option>
                    </select>
                </div>

                <div class="filter-section">
                    <label class="filter-label">Bidang Kerja:</label>
                    <select id="filter-bidang" class="custom-select">
                        <option value="semua">Semua Bidang Kerja</option>
                    </select>
                </div>

                <div class="filter-section">
                    <label class="filter-label">Tahun Lulus:</label>
                    <select id="filter-tahun" class="custom-select">
                        <option value="semua">Semua Tahun</option>
                        <option value="0">Tahun Ini</option>
                        <option value="1">1 Tahun Terakhir (Tahun Lalu)</option>
                        <option value="3">3 Tahun Terakhir</option>
                        <option value="5">5 Tahun Terakhir</option>
                    </select>
                </div>

                <div class="advanced-filter">
                    <button id="toggle-advanced-filter" type="button" class="advanced-filter-toggle">
                        <span>Filter Lanjutan</span>
                        <span class="advanced-filter-arrow"></span>
                    </button>

                    <div id="advanced-filter-body" class="advanced-filter-body hidden">
                        <div class="advanced-filter-content">
                            <div class="filter-section">
                                <label class="filter-label">Kesesuaian Bidang:</label>
                                <select id="filter-linearitas" class="custom-select">
                                    <option value="semua">Semua Kesesuaian</option>
                                    <option value="Sangat Erat">Sangat Erat</option>
                                    <option value="Erat">Erat</option>
                                    <option value="Cukup Erat">Cukup Erat</option>
                                    <option value="Kurang Erat">Kurang Erat</option>
                                    <option value="Tidak Erat">Tidak Erat</option>
                                </select>
                            </div>

                            <div class="filter-section">
                                <label class="filter-label">Status Kerja:</label>
                                <select id="filter-status-kerja" class="custom-select" multiple>
                                    <option value="semua">Semua Status</option>
                                    <option value="bekerja" selected data-icon="<?php echo e(asset('img/icon alumni kerja.png')); ?>">Sedang Bekerja</option>
                                    <option value="belum_bekerja" selected data-icon="<?php echo e(asset('img/icon alumni nganggur.png')); ?>">Belum Bekerja</option>
                                    <option value="studi_lanjut" data-icon="<?php echo e(asset('img/Icon studi lanjut.png')); ?>">Studi Lanjut</option>
                                </select>
                            </div>

                            <div class="filter-section">
                                <label class="filter-label">Angkatan:</label>
                                <select id="filter-angkatan" class="custom-select">
                                    <option value="semua">Semua Angkatan</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="filter-actions">
                    <button id="btn-reset-filter" type="button" class="reset-filter-btn">
                        Reset Filter
                    </button>
                </div>
            </div>
        </div>
        <div id="search-results" class="results-container"></div>
    </div>
</div>
<?php /**PATH D:\Aplikasi_Skripsi\gis_alumni_3\resources\views/utama/filter-panel.blade.php ENDPATH**/ ?>