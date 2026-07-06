@extends('admin.layout')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/statistik.css') }}">
@endpush

@section('content')
    <script>
        document.body.classList.add('stat-is-loading');
    </script>

    <header class="top-header glass-panel statistik-header">
        <div class="header-left">
            <h1>Statistik Alumni</h1>
            <p class="statistik-subtitle">Dashboard analisis tracer study alumni Pendidikan Komputer</p>
        </div>

        <div class="header-right statistik-actions">
            @php
                $hasInitialFilters = collect($initialFilters ?? [])
                    ->filter(fn ($v) => $v !== null && trim((string) $v) !== '')
                    ->isNotEmpty();
            @endphp

            <button id="stat-filter-toggle" class="btn-filter-toggle btn-filter-icon" type="button"
                aria-controls="stat-filter-panel"
                aria-expanded="{{ $hasInitialFilters ? 'true' : 'false' }}"
                title="Tampilkan filter statistik"
                aria-label="Tampilkan filter statistik">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                </svg>
            </button>
            <button id="stat-export-pdf" class="btn-export" type="button" title="Export PDF laporan statistik">
                Export Laporan PDF
            </button>
            <button id="stat-export-excel" class="btn-export" type="button" title="Export Excel laporan statistik">
                Export Laporan Excel
            </button>
        </div>
    </header>

    <section id="stat-filter-shell" class="glass-panel statistik-filter" {{ $hasInitialFilters ? '' : 'hidden' }}>
        <div id="stat-filter-panel" class="filter-panel" {{ $hasInitialFilters ? '' : 'hidden' }}>
            <div class="filter-grid">
            <div class="filter-item">
                <label>Angkatan</label>
                <select id="stat-filter-angkatan">
                    <option value="">Semua</option>
                    @foreach($angkatanOptions as $opt)
                        <option value="{{ $opt }}" {{ (string)($initialFilters['angkatan'] ?? '') === (string)$opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-item">
                <label>Tahun Lulus</label>
                <select id="stat-filter-tahun-lulus">
                    <option value="">Semua</option>
                    @foreach($tahunLulusOptions as $opt)
                        <option value="{{ $opt }}" {{ (string)($initialFilters['tahun_lulus'] ?? '') === (string)$opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-item">
                <label>Jenis Kelamin</label>
                <select id="stat-filter-jenis-kelamin">
                    <option value="">Semua</option>
                    @foreach($jenisKelaminOptions as $opt)
                        <option value="{{ $opt }}" {{ (string)($initialFilters['jenis_kelamin'] ?? '') === (string)$opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-item">
                <label>Status Alumni</label>
                <select id="stat-filter-status-alumni">
                    <option value="">Semua</option>
                    <option value="bekerja" {{ (string)($initialFilters['status_alumni'] ?? '') === 'bekerja' ? 'selected' : '' }}>Bekerja</option>
                    <option value="belum_bekerja" {{ (string)($initialFilters['status_alumni'] ?? '') === 'belum_bekerja' ? 'selected' : '' }}>Belum Bekerja</option>
                    <option value="studi_lanjut" {{ (string)($initialFilters['status_alumni'] ?? '') === 'studi_lanjut' ? 'selected' : '' }}>Studi Lanjut</option>
                </select>
            </div>
            <div class="filter-item">
                <label>Bidang Pekerjaan</label>
                <select id="stat-filter-bidang">
                    <option value="">Semua</option>
                    @foreach($bidangOptions as $opt)
                        <option value="{{ $opt }}" {{ (string)($initialFilters['bidang_pekerjaan'] ?? '') === (string)$opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-item">
                <label>Kabupaten/Kota</label>
                <select id="stat-filter-wilayah"
                    data-initial-wilayah-id="{{ $initialFilters['wilayah_id'] ?? '' }}">
                    <option value="">Semua Wilayah Kalsel</option>
                </select>
            </div>
            <div class="filter-item">
                <label>Mode Data</label>
                <select id="stat-filter-data-mode">
                    <option value="valid" selected>Data valid</option>
                    <option value="all">Semua data</option>
                </select>
            </div>
        </div>

        <div class="filter-actions">
            <button id="stat-apply" class="btn-apply" type="button">Terapkan Filter</button>
            <button id="stat-reset" class="btn-reset" type="button">Reset</button>
            <div id="stat-loading" class="stat-loading" aria-live="polite" style="display:none;">Memuat data...</div>
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

            <div class="kpi-card glass-panel kpi-accent-sky toefl-summary-card">
                <div class="kpi-top">
                    <div class="kpi-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19h16"></path>
                            <path d="M8 19V5"></path>
                            <path d="M16 19V9"></path>
                            <path d="M12 19V13"></path>
                        </svg>
                    </div>
                    <div class="kpi-label">Rata-rata TOEFL</div>
                </div>
                <div class="kpi-value" id="kpi-toefl">-</div>
                <div class="kpi-sub" id="kpi-toefl-sub">Data TOEFL belum tersedia</div>
                <div class="kpi-meta" id="kpi-toefl-meta" style="display:none;">Data tersedia: 0 alumni</div>
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

        <div class="chart-section stat-profile-section">
            <div class="chart-section-head">
                <div class="chart-section-title">Profil Alumni</div>
                <div class="chart-section-sub">Komposisi jenis kelamin dan distribusi TOEFL</div>
            </div>
            <div class="chart-grid chart-grid--3">
                <div class="chart-card glass-panel gender-chart-card">
                    <div class="chart-head">
                        <div class="chart-title">Jenis Kelamin</div>
                        <div class="chart-subtitle">Perbandingan alumni laki-laki dan perempuan</div>
                    </div>
                    <div class="chart-body">
                        <canvas id="chart-gender"></canvas>
                        <div class="chart-empty" data-empty-for="chart-gender" hidden>Data jenis kelamin belum tersedia.</div>
                    </div>
                </div>

                <div class="chart-card glass-panel toefl-dist-chart-card">
                    <div class="chart-head">
                        <div class="chart-title">Distribusi Nilai TOEFL</div>
                        <div class="chart-subtitle">Kelompok nilai TOEFL alumni</div>
                    </div>
                    <div class="chart-body">
                        <div class="chart-fixed chart-toefl-box">
                            <canvas id="chart-toefl-dist"></canvas>
                            <div class="chart-empty" data-empty-for="chart-toefl-dist" hidden>Data TOEFL belum tersedia.</div>
                        </div>
                        <div class="chart-footnote" id="toefl-dist-footnote" style="display:none;"></div>
                    </div>
                </div>

                <div class="chart-card glass-panel salary-dist-chart-card">
                    <div class="chart-head">
                        <div class="chart-title">Distribusi Rentang Gaji Alumni</div>
                        <div class="chart-subtitle">Pengelompokan alumni berdasarkan gaji nominal</div>
                    </div>
                    <div class="chart-body">
                        <div class="chart-fixed">
                            <canvas id="chart-salary-dist"></canvas>
                            <div class="chart-empty" data-empty-for="chart-salary-dist" hidden>Data gaji belum tersedia.</div>
                        </div>
                        <div class="chart-footnote" id="salary-dist-footnote" style="display:none;"></div>
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
                <div class="chart-section-title">Keterserapan Kerja</div>
                <div class="chart-section-sub">Instansi paling banyak menyerap alumni</div>
            </div>
            <div class="chart-grid">
                <div class="chart-card glass-panel chart-card-wide top-company-chart-card">
                    <div class="chart-head">
                        <div class="chart-title">Top 10 Perusahaan/Instansi Tujuan</div>
                        <div class="chart-subtitle">Berdasarkan pekerjaan utama (aktif)</div>
                    </div>
                    <div class="chart-body">
                        <canvas id="chart-top-company"></canvas>
                        <div class="chart-empty" data-empty-for="chart-top-company" hidden>Data perusahaan/instansi tujuan belum tersedia.</div>
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
                <div class="chart-card glass-panel chart-card-wilayah">
                    <div class="chart-head">
                        <div class="chart-title-row chart-title-row-center">
                            <div class="chart-title chart-title-center">Persebaran Kerja Alumni</div>
                            <span class="chart-info-icon" tabindex="0" role="img" aria-label="Info persebaran kerja alumni"
                                title="Alumni dianggap terhubung dengan suatu wilayah jika tempat kerja saat ini atau alamat domisilinya berada dalam batas administratif wilayah tersebut. Chart menampilkan distribusi lokasi kerja saat ini dari alumni-alumni tersebut, sehingga dapat mencakup wilayah selain yang difilter.">i</span>
                        </div>
                        <div id="chart-top-wilayah-subtitle" class="chart-subtitle chart-subtitle-center">Distribusi wilayah kerja seluruh alumni yang bekerja</div>
                    </div>
                    <div class="chart-body">
                        <div class="chart-wilayah-toolbar">
                            <label class="chart-inline-check">
                                <input id="stat-wilayah-sort" type="checkbox">
                                <span>Urutkan dari terbanyak</span>
                            </label>
                        </div>
                        <div class="chart-wilayah-chart-wrap">
                            <canvas id="chart-top-wilayah"></canvas>
                            <div class="chart-empty" data-empty-for="chart-top-wilayah" hidden>Data persebaran kerja alumni belum tersedia.</div>
                        </div>
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

        {{-- Heatmap Persebaran Alumni dinonaktifkan (admin) --}}
        {{--
        <div class="chart-section">
            <div class="chart-section-head">
                <div class="chart-section-title">Heatmap Persebaran Alumni</div>
                <div class="chart-section-sub">Kalimantan Selatan (Domisili vs Lokasi Kerja)</div>
            </div>
            <div class="chart-grid chart-grid--1">
                <div class="chart-card glass-panel chart-card-wide heatmap-stat-card">
                    <div class="chart-head chart-heatmap-head">
                        <div>
                            <div class="chart-title">Heatmap Persebaran Alumni (Kalimantan Selatan)</div>
                            <div class="chart-subtitle" id="heatmap-subtitle">Menampilkan kepadatan domisili alumni di Kalsel</div>
                        </div>
                        <div class="chart-heatmap-tabs" role="tablist" aria-label="Mode heatmap">
                            <button id="heatmap-tab-domisili" class="btn-primary chart-heatmap-tab" type="button" aria-selected="true">Domisili</button>
                            <button id="heatmap-tab-kerja" class="btn-outline-primary chart-heatmap-tab" type="button" aria-selected="false">Lokasi Kerja</button>
                        </div>
                    </div>
                    <div class="chart-body chart-heatmap-body">
                        <div id="heatmap-map" class="chart-heatmap-map"></div>
                        <div class="chart-empty chart-heatmap-empty" data-empty-for="heatmap-map" hidden>Data koordinat belum tersedia.</div>
                    </div>
                    <div class="chart-heatmap-foot">
                        <div class="chart-heatmap-meta" id="heatmap-meta">0 titik valid • 0 data tanpa koordinat</div>
                    </div>
                </div>
            </div>
        </div>
        --}}

        <div class="chart-section">
            <div class="chart-section-head">
                <div class="chart-section-title">Tren Alumni</div>
                <div class="chart-section-sub">Perubahan jumlah alumni dan keterserapan kerja per angkatan</div>
            </div>
            <div class="chart-grid chart-grid--1">
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
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        window.__STATISTIK_ENDPOINT__ = @json(route('admin.statistik.data'));
        window.__STATISTIK_EXPORT_PDF__ = @json(route('admin.statistik.export.pdf'));
        window.__STATISTIK_EXPORT_EXCEL__ = @json(route('admin.statistik.export.excel'));
    </script>
    <script src="{{ asset('js/admin/statistik.js') }}"></script>
@endpush
