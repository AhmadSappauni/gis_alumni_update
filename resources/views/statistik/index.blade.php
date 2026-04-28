<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Statistik Alumni - GIS Alumni</title>

    <link rel="stylesheet" href="{{ asset('css/utama/statistik.css') }}">
</head>
<body>
    <header class="stat-topbar">
        <a class="stat-back" href="{{ route('map.index') }}" aria-label="Kembali ke Peta">
            <span class="stat-back-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 18l-6-6 6-6"></path>
                </svg>
            </span>
            <span>Kembali ke Peta</span>
        </a>

        <div class="stat-brand">
            <div class="stat-brand-title">Statistik Alumni</div>
            <div class="stat-brand-sub">Dashboard analisis tracer study alumni Pendidikan Komputer</div>
        </div>
    </header>

    <main class="stat-wrap">
        <section class="stat-panel">
            @php
                $hasInitialFilters = collect($initialFilters ?? [])
                    ->filter(fn ($v) => $v !== null && trim((string) $v) !== '')
                    ->isNotEmpty();
            @endphp

            <div class="stat-filter-head">
                <button id="stat-filter-toggle" class="stat-btn stat-btn-filter" type="button" aria-controls="stat-filter-panel"
                    aria-expanded="{{ $hasInitialFilters ? 'true' : 'false' }}">
                    Filter Statistik
                </button>
            </div>

            <div id="stat-filter-panel" class="stat-filter-panel" {{ $hasInitialFilters ? '' : 'hidden' }}>
                <div class="stat-filter-grid">
                <div class="stat-field">
                    <label>Angkatan</label>
                    <select id="stat-filter-angkatan">
                        <option value="">Semua</option>
                        @foreach($angkatanOptions as $opt)
                            <option value="{{ $opt }}" {{ (string)($initialFilters['angkatan'] ?? '') === (string)$opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="stat-field">
                    <label>Tahun Lulus</label>
                    <select id="stat-filter-tahun-lulus">
                        <option value="">Semua</option>
                        @foreach($tahunLulusOptions as $opt)
                            <option value="{{ $opt }}" {{ (string)($initialFilters['tahun_lulus'] ?? '') === (string)$opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="stat-field">
                    <label>Jenis Kelamin</label>
                    <select id="stat-filter-jenis-kelamin">
                        <option value="">Semua</option>
                        @foreach($jenisKelaminOptions as $opt)
                            <option value="{{ $opt }}" {{ (string)($initialFilters['jenis_kelamin'] ?? '') === (string)$opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="stat-field">
                    <label>Status Alumni</label>
                    <select id="stat-filter-status-alumni">
                        <option value="">Semua</option>
                        <option value="bekerja" {{ (string)($initialFilters['status_alumni'] ?? '') === 'bekerja' ? 'selected' : '' }}>Bekerja</option>
                        <option value="belum_bekerja" {{ (string)($initialFilters['status_alumni'] ?? '') === 'belum_bekerja' ? 'selected' : '' }}>Belum Bekerja</option>
                        <option value="studi_lanjut" {{ (string)($initialFilters['status_alumni'] ?? '') === 'studi_lanjut' ? 'selected' : '' }}>Studi Lanjut</option>
                    </select>
                </div>
                <div class="stat-field">
                    <label>Bidang Pekerjaan</label>
                    <select id="stat-filter-bidang">
                        <option value="">Semua</option>
                        @foreach($bidangOptions as $opt)
                            <option value="{{ $opt }}" {{ (string)($initialFilters['bidang_pekerjaan'] ?? '') === (string)$opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="stat-field">
                    <label>Wilayah Kerja</label>
                    <select id="stat-filter-wilayah">
                        <option value="">Semua</option>
                        @foreach($wilayahOptions as $opt)
                            <option value="{{ $opt }}" {{ (string)($initialFilters['wilayah'] ?? '') === (string)$opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="stat-filter-actions">
                <button id="stat-apply" class="stat-btn stat-btn-primary" type="button">Terapkan</button>
                <button id="stat-reset" class="stat-btn" type="button">Reset</button>
                <div id="stat-loading" class="stat-loading" aria-live="polite" style="display:none;">Memuat data…</div>
            </div>
        </div>
        </section>

        <section id="stat-insight" class="stat-insight" hidden>
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

        <section class="stat-kpis">
            <div class="stat-kpi-grid">
                <div class="stat-kpi-card kpi-accent-blue">
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
                        <div class="stat-kpi-label">Total Alumni</div>
                    </div>
                    <div class="stat-kpi-value" id="kpi-total">0</div>
                    <div class="stat-kpi-sub">Jumlah alumni terdata</div>
                </div>

                <div class="stat-kpi-card kpi-accent-green">
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
                        <div class="stat-kpi-label">Alumni Bekerja</div>
                    </div>
                    <div class="stat-kpi-value" id="kpi-bekerja">0</div>
                    <div class="stat-kpi-sub" id="kpi-bekerja-sub"></div>
                </div>

                <div class="stat-kpi-card kpi-accent-red">
                    <div class="kpi-top">
                        <div class="kpi-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M15 9l-6 6"></path>
                                <path d="M9 9l6 6"></path>
                            </svg>
                        </div>
                        <div class="stat-kpi-label">Belum Bekerja</div>
                    </div>
                    <div class="stat-kpi-value" id="kpi-belum">0</div>
                    <div class="stat-kpi-sub">Perlu dukungan karier</div>
                </div>

                <div class="stat-kpi-card kpi-accent-purple">
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
                        <div class="stat-kpi-label">Studi Lanjut</div>
                    </div>
                    <div class="stat-kpi-value" id="kpi-studi">0</div>
                    <div class="stat-kpi-sub">Melanjutkan pendidikan</div>
                </div>

                <div class="stat-kpi-card kpi-accent-yellow">
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
                        <div class="stat-kpi-label">Multi-Job</div>
                    </div>
                    <div class="stat-kpi-value" id="kpi-multi">0</div>
                    <div class="stat-kpi-sub">Memiliki >1 pekerjaan</div>
                </div>

                <div class="stat-kpi-card kpi-accent-sky">
                    <div class="kpi-top">
                        <div class="kpi-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M12 6v6l3 2"></path>
                            </svg>
                        </div>
                        <div class="stat-kpi-label">Rata-rata Masa Tunggu</div>
                    </div>
                    <div class="stat-kpi-value"><span id="kpi-masatunggu">-</span> <span class="stat-kpi-unit">bulan</span></div>
                    <div class="stat-kpi-sub">Estimasi waktu mendapat kerja</div>
                </div>
            </div>
        </section>

        <section class="stat-charts">
            <div class="stat-section">
                <div class="stat-section-head">
                    <div class="stat-section-title">Ringkasan Ketenagakerjaan</div>
                    <div class="stat-section-sub">Gambaran status kerja dan masa tunggu</div>
                </div>
                <div class="stat-chart-grid">
                    <div class="stat-card">
                        <div class="stat-card-head">
                            <div class="stat-card-title">Status Alumni</div>
                            <div class="stat-card-sub">Distribusi status (unik)</div>
                        </div>
                        <div class="stat-card-body">
                            <canvas id="chart-status"></canvas>
                            <div class="stat-empty" data-empty-for="chart-status" hidden>Belum ada data untuk ditampilkan.</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-head">
                            <div class="stat-card-title">Masa Tunggu Kerja</div>
                            <div class="stat-card-sub">Kelompok masa tunggu (aktif)</div>
                        </div>
                        <div class="stat-card-body">
                            <canvas id="chart-masa-tunggu"></canvas>
                            <div class="stat-empty" data-empty-for="chart-masa-tunggu" hidden>Belum ada data untuk ditampilkan.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stat-section">
                <div class="stat-section-head">
                    <div class="stat-section-title">Relevansi Karier</div>
                    <div class="stat-section-sub">Kesesuaian bidang ilmu dan bidang pekerjaan dominan</div>
                </div>
                <div class="stat-chart-grid">
                    <div class="stat-card">
                        <div class="stat-card-head">
                            <div class="stat-card-title">Kesesuaian Bidang Ilmu</div>
                            <div class="stat-card-sub">Linearitas pekerjaan utama (aktif)</div>
                        </div>
                        <div class="stat-card-body">
                            <canvas id="chart-linearitas"></canvas>
                            <div class="stat-empty" data-empty-for="chart-linearitas" hidden>Belum ada data untuk ditampilkan.</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-head">
                            <div class="stat-card-title">Top 5 Bidang Pekerjaan</div>
                            <div class="stat-card-sub">Bidang terbanyak (aktif)</div>
                        </div>
                        <div class="stat-card-body">
                            <canvas id="chart-top-bidang"></canvas>
                            <div class="stat-empty" data-empty-for="chart-top-bidang" hidden>Belum ada data untuk ditampilkan.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stat-section">
                <div class="stat-section-head">
                    <div class="stat-section-title">Persebaran Wilayah</div>
                    <div class="stat-section-sub">Wilayah kerja dan kampus studi lanjut terbanyak</div>
                </div>
                <div class="stat-chart-grid">
                    <div class="stat-card">
                        <div class="stat-card-head">
                            <div class="stat-card-title">Top 5 Wilayah Kerja</div>
                            <div class="stat-card-sub">Kota/provinsi terbanyak (aktif)</div>
                        </div>
                        <div class="stat-card-body">
                            <canvas id="chart-top-wilayah"></canvas>
                            <div class="stat-empty" data-empty-for="chart-top-wilayah" hidden>Belum ada data untuk ditampilkan.</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-head">
                            <div class="stat-card-title">Top Kampus Studi Lanjut</div>
                            <div class="stat-card-sub">5 kampus terbanyak (unik)</div>
                        </div>
                        <div class="stat-card-body">
                            <canvas id="chart-top-kampus"></canvas>
                            <div class="stat-empty" data-empty-for="chart-top-kampus" hidden>Belum ada data untuk ditampilkan.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stat-section">
                <div class="stat-section-head">
                    <div class="stat-section-title">Tren Alumni</div>
                    <div class="stat-section-sub">Perubahan jumlah alumni dan keterserapan kerja per angkatan</div>
                </div>
                <div class="stat-chart-grid">
                    <div class="stat-card stat-card-wide">
                        <div class="stat-card-head">
                            <div class="stat-card-title">Tren Alumni per Angkatan</div>
                            <div class="stat-card-sub">Jumlah alumni per angkatan</div>
                        </div>
                        <div class="stat-card-body">
                            <canvas id="chart-tren-angkatan"></canvas>
                            <div class="stat-empty" data-empty-for="chart-tren-angkatan" hidden>Belum ada data untuk ditampilkan.</div>
                        </div>
                    </div>

                    <div class="stat-card stat-card-wide">
                        <div class="stat-card-head">
                            <div class="stat-card-title">Tren Keterserapan Kerja per Angkatan</div>
                            <div class="stat-card-sub">Bekerja vs belum bekerja</div>
                        </div>
                        <div class="stat-card-body">
                            <canvas id="chart-tren-serap"></canvas>
                            <div class="stat-empty" data-empty-for="chart-tren-serap" hidden>Belum ada data untuk ditampilkan.</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        window.__STATISTIK_ENDPOINT__ = @json(route('statistik.data'));
    </script>
    <script src="{{ asset('js/utama/statistik.js') }}"></script>
</body>
</html>
