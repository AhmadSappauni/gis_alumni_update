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
            <div class="stat-brand-sub">Dashboard tracer study Alumni Pendidikan Komputer ULM</div>
        </div>
    </header>

    <main class="stat-wrap">
        <section class="stat-panel">
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
        </section>

        <section class="stat-kpis">
            <div class="stat-kpi-grid">
                <div class="stat-kpi">
                    <div class="stat-kpi-label">Total Alumni</div>
                    <div class="stat-kpi-value" id="kpi-total">0</div>
                </div>
                <div class="stat-kpi">
                    <div class="stat-kpi-label">Bekerja</div>
                    <div class="stat-kpi-value" id="kpi-bekerja">0</div>
                </div>
                <div class="stat-kpi">
                    <div class="stat-kpi-label">Belum Bekerja</div>
                    <div class="stat-kpi-value" id="kpi-belum">0</div>
                </div>
                <div class="stat-kpi">
                    <div class="stat-kpi-label">Studi Lanjut</div>
                    <div class="stat-kpi-value" id="kpi-studi">0</div>
                </div>
                <div class="stat-kpi">
                    <div class="stat-kpi-label">Multi-Job</div>
                    <div class="stat-kpi-value" id="kpi-multi">0</div>
                </div>
                <div class="stat-kpi">
                    <div class="stat-kpi-label">Rata-rata Masa Tunggu</div>
                    <div class="stat-kpi-value" id="kpi-masatunggu">-</div>
                    <div class="stat-kpi-note">bulan</div>
                </div>
            </div>
        </section>

        <section class="stat-charts">
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
                        <div class="stat-card-title">Masa Tunggu Kerja</div>
                        <div class="stat-card-sub">Kelompok masa tunggu (aktif)</div>
                    </div>
                    <div class="stat-card-body">
                        <canvas id="chart-masa-tunggu"></canvas>
                        <div class="stat-empty" data-empty-for="chart-masa-tunggu" hidden>Belum ada data untuk ditampilkan.</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-head">
                        <div class="stat-card-title">Studi Lanjut per Jenjang</div>
                        <div class="stat-card-sub">Jenjang studi (unik)</div>
                    </div>
                    <div class="stat-card-body">
                        <canvas id="chart-studi-jenjang"></canvas>
                        <div class="stat-empty" data-empty-for="chart-studi-jenjang" hidden>Belum ada data untuk ditampilkan.</div>
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
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        window.__STATISTIK_ENDPOINT__ = @json(route('statistik.data'));
    </script>
    <script src="{{ asset('js/utama/statistik.js') }}"></script>
</body>
</html>

