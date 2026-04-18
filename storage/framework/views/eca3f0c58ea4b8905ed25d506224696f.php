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
        
        <button id="toggle-filter" class="toggle-btn" title="Tampilkan/Sembunyikan Filter">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
        </button>
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
                        <option value="semua">Semua (Nama & Tempat Kerja)</option>
                        <option value="nama">Nama Alumni Saja</option>
                        <option value="perusahaan">Tempat Kerja Saja</option>
                    </select>
                </div>

                <div class="filter-section">
                    <label class="filter-label">Wilayah (Kota/Kab):</label>
                    <input type="text" id="filter-wilayah" class="custom-input" placeholder="Contoh: Banjarbaru">
                </div>

                <div class="filter-section">
                    <label class="filter-label">Kesesuaian Bidang:</label>
                    <select id="filter-linearitas" class="custom-select">
                        <option value="semua">Semua Kesesuaian</option>
                        <option value="Linier">Linier</option>
                        <option value="Tidak Linier">Tidak Linier</option>
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
            </div>
        </div>
        <div id="search-results" class="results-container"></div>
    </div>
</div>
<?php /**PATH D:\Aplikasi_Skripsi\gis_alumni_3\resources\views/utama/filter-panel.blade.php ENDPATH**/ ?>