<div class="map-navigation-rail" aria-label="Navigasi">
    <button class="rail-btn" id="open-sidebar" type="button" title="Menu" aria-label="Menu">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>

    <button
        class="rail-btn"
        id="open-direktori"
        type="button"
        title="Direktori Alumni"
        aria-label="Direktori Alumni"
    >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20"></path>
            <path d="M20 2H6.5A2.5 2.5 0 0 0 4 4.5v15"></path>
            <path d="M8 6h8"></path>
            <path d="M8 10h8"></path>
            <path d="M8 14h6"></path>
        </svg>
    </button>

    <a
        href="{{ route('statistik.index') }}"
        class="rail-btn {{ request()->routeIs('statistik.*') ? 'active' : '' }}"
        title="Statistik Alumni"
        aria-label="Statistik Alumni"
    >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="12" y1="20" x2="12" y2="10"></line>
            <line x1="18" y1="20" x2="18" y2="4"></line>
            <line x1="6" y1="20" x2="6" y2="16"></line>
        </svg>
    </a>
</div>

<div class="filter-panel" aria-label="Pencarian dan filter peta">
    <div class="map-search-panel">
        <div class="map-search-input-wrap">
            <input type="text" id="search-input" placeholder="Ketik nama atau tempat kerja..." aria-label="Pencarian alumni">
            <button id="btn-clear-search" type="button" class="map-clear-btn" title="Hapus" aria-label="Hapus" hidden>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            <button id="btn-search" type="button" class="map-search-btn" title="Cari" aria-label="Cari">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </button>

            <button id="toggle-filter" type="button" class="map-filter-btn" title="Filter" aria-label="Filter">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                </svg>
            </button>

            <button id="collapse-filter-panel" type="button" class="map-collapse-btn" title="Tutup/Buka Panel" aria-expanded="true" aria-label="Tutup/Buka Panel">
                <svg class="icon-collapse" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                <svg class="icon-expand" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="9 18 15 12 9 6"></polyline>
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
                                    {{-- <option value="bekerja" selected data-icon="{{ asset('img/icon alumni kerja.png') }}">Sedang Bekerja</option> --}}
                                    <option value="bekerja" selected data-icon="https://jmogfydhlafcuoknkcrg.supabase.co/storage/v1/object/public/alumni/icon%20alumni%20kerja.png">Sedang Bekerja</option>
                                    {{-- <option value="belum_bekerja" selected data-icon="{{ asset('img/icon alumni nganggur.png') }}">Belum Bekerja</option> --}}
                                    <option value="belum_bekerja" selected data-icon="https://jmogfydhlafcuoknkcrg.supabase.co/storage/v1/object/public/alumni/icon%20alumni%20nganggur.png">Belum Bekerja</option>
                                    {{-- <option value="studi_lanjut" data-icon="{{ asset('img/Icon studi lanjut.png') }}">Studi Lanjut</option> --}}
                                    <option value="studi_lanjut" data-icon="https://jmogfydhlafcuoknkcrg.supabase.co/storage/v1/object/public/alumni/Icon%20studi%20lanjut.png">Studi Lanjut</option>
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
