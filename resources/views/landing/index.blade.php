<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="WebGIS Persebaran Alumni Pendidikan Komputer FKIP ULM — visualisasi spasial dan tracer study alumni.">
    <title>WebGIS Alumni — Persebaran Alumni Pendidikan Komputer FKIP ULM</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Leaflet — same CDN version as halaman peta --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        /* ── Design tokens ──────────────────────────────────────────── */
        :root {
            --navy:        #004a87;
            --navy-light:  rgba(0, 74, 135, 0.06);
            --navy-hover:  rgba(0, 74, 135, 0.12);
            --yellow:      #fdb813;
            --yellow-h:    #e0a400;
            --bg:          #f1f5f9;
            --bg-hero:     linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);
            --text:        #0f172a;
            --text-mid:    #334155;
            --text-muted:  #64748b;
            --text-soft:   #94a3b8;
            --border:      #e2e8f0;
            --white:       #ffffff;
        }

        /* ── Reset & base ───────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; font-size: 16px; }
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; background: #fff; color: var(--text); line-height: 1.5; }
        a { text-decoration: none; }
        ul { list-style: none; }
        img { display: block; max-width: 100%; }

        /* ── Container ──────────────────────────────────────────────── */
        .lp-container { max-width: 1200px; margin: 0 auto; padding: 0 1.5rem; }

        /* ── Navbar ─────────────────────────────────────────────────── */
        .lp-nav {
            position: sticky; top: 0; z-index: 9999;
            background: var(--white);
            border-bottom: 1px solid var(--border);
            box-shadow: 0 1px 6px rgba(0,0,0,0.06);
        }
        .lp-nav-inner {
            display: flex; align-items: center; justify-content: space-between;
            height: 64px;
        }
        .lp-nav-brand {
            display: flex; align-items: center; gap: 0.75rem; color: var(--navy);
        }
        .lp-nav-brand-logo {
            width: 38px; height: 38px;
            border: 2px dashed var(--navy);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 9px; font-weight: 700; color: var(--navy);
            text-align: center; line-height: 1.25;
            flex-shrink: 0;
        }
        .lp-nav-brand-text { font-size: 1.1rem; font-weight: 700; letter-spacing: -0.02em; }
        .lp-nav-links { display: flex; align-items: center; gap: 2rem; }
        .lp-nav-link {
            font-size: 0.9rem; font-weight: 500; color: var(--text-mid);
            transition: color 0.2s;
        }
        .lp-nav-link:hover { color: var(--navy); }
        .lp-nav-cta {
            background: var(--yellow); color: var(--navy);
            padding: 0.45rem 1.25rem; border-radius: 8px;
            font-size: 0.875rem; font-weight: 700;
            transition: background 0.2s;
        }
        .lp-nav-cta:hover { background: var(--yellow-h); }
        .lp-nav-mobile { display: none; align-items: center; gap: 0.75rem; }
        .lp-hamburger {
            background: none; border: none; cursor: pointer; padding: 4px;
            display: flex; align-items: center;
        }
        .lp-nav-dropdown {
            display: none; flex-direction: column;
            padding: 0.75rem 1.5rem 1rem;
            border-top: 1px solid var(--border);
            background: var(--white); gap: 0.25rem;
        }
        .lp-nav-dropdown a {
            color: var(--text-mid); padding: 0.5rem 0; font-weight: 500; font-size: 0.925rem;
        }
        .lp-nav-dropdown.open { display: flex; }

        /* ── Hero ───────────────────────────────────────────────────── */
        .lp-hero {
            background: var(--bg-hero);
            padding: 5rem 1.5rem 4.5rem;
        }
        .lp-hero-grid {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 3.5rem; align-items: center;
        }
        .lp-hero-logo {
            width: 88px; height: 88px;
            border: 2px dashed var(--navy); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            background: rgba(0, 74, 135, 0.05);
            font-size: 9.5px; font-weight: 700; color: var(--navy);
            text-align: center; line-height: 1.4; margin-bottom: 1.5rem;
        }
        .lp-hero-h1 {
            font-size: clamp(2rem, 4vw, 2.85rem);
            font-weight: 800; color: var(--navy); line-height: 1.2;
            margin-bottom: 1.25rem; letter-spacing: -0.03em;
        }
        .lp-hero-sub {
            font-size: 1rem; color: #475569; line-height: 1.8;
            margin-bottom: 2rem; max-width: 520px;
        }
        .lp-hero-btns { display: flex; gap: 1rem; flex-wrap: wrap; }
        .btn-primary {
            background: var(--yellow); color: var(--navy);
            padding: 0.75rem 2rem; border-radius: 10px;
            font-weight: 700; font-size: 1rem;
            box-shadow: 0 4px 14px rgba(253,184,19,0.35);
            transition: background 0.2s; display: inline-block;
        }
        .btn-primary:hover { background: var(--yellow-h); }
        .btn-outline {
            background: transparent; color: var(--navy);
            padding: 0.75rem 2rem; border-radius: 10px;
            font-weight: 700; font-size: 1rem;
            border: 2px solid var(--navy);
            transition: background 0.2s; display: inline-block;
        }
        .btn-outline:hover { background: var(--navy-light); }

        /* Mini map */
        .lp-minimap-wrap {
            position: relative; cursor: pointer;
            border-radius: 14px; overflow: hidden;
            box-shadow: 0 12px 32px rgba(0,74,135,0.18), 0 2px 8px rgba(0,0,0,0.08);
        }
        #mini-map { height: 460px; width: 100%; }
        .lp-minimap-overlay {
            position: absolute; inset: 0;
            background: rgba(0, 74, 135, 0.72);
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.3s ease;
            z-index: 1000;
        }
        .lp-minimap-wrap:hover .lp-minimap-overlay { opacity: 1; }
        .lp-minimap-overlay-inner { text-align: center; color: #fff; }
        .lp-minimap-overlay-inner .icon { font-size: 2rem; margin-bottom: 0.5rem; }
        .lp-minimap-overlay-inner .label {
            font-size: 0.975rem; font-weight: 600; letter-spacing: 0.02em;
        }
        .lp-minimap-hint {
            text-align: center; font-size: 0.8rem; color: var(--text-soft);
            margin-top: 0.75rem;
        }

        /* ── KPI Strip ──────────────────────────────────────────────── */
        .lp-kpi { background: var(--bg); padding: 4rem 1.5rem; }
        .lp-kpi-grid {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 0;
        }
        .lp-kpi-item {
            text-align: center; padding: 1.5rem 1.5rem;
            border-left: 1px solid #cbd5e1;
        }
        .lp-kpi-item:first-child { border-left: none; }
        .lp-kpi-num {
            font-size: clamp(2rem, 4vw, 2.85rem);
            font-weight: 800; color: var(--navy);
            line-height: 1; margin-bottom: 0.5rem;
        }
        .lp-kpi-label {
            font-size: 0.7rem; text-transform: uppercase;
            letter-spacing: 0.1em; color: var(--text-muted); font-weight: 600;
        }

        /* ── Fitur ──────────────────────────────────────────────────── */
        .lp-fitur { padding: 5rem 1.5rem; background: var(--white); }
        .lp-section-heading { text-align: center; margin-bottom: 3rem; }
        .lp-section-heading h2 {
            font-size: clamp(1.6rem, 3vw, 2rem); font-weight: 800;
            color: var(--navy); margin-bottom: 0.6rem; letter-spacing: -0.02em;
        }
        .lp-section-heading p { color: var(--text-muted); font-size: 1rem; }
        .lp-fitur-grid {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem;
        }
        .lp-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px; padding: 1.75rem 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: box-shadow 0.25s, transform 0.25s;
        }
        .lp-card:hover {
            box-shadow: 0 8px 24px rgba(0,74,135,0.13);
            transform: translateY(-2px);
        }
        .lp-card-icon {
            width: 50px; height: 50px;
            background: var(--navy); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; margin-bottom: 1rem;
        }
        .lp-card h3 {
            font-size: 1rem; font-weight: 700; color: var(--navy);
            margin-bottom: 0.6rem;
        }
        .lp-card p {
            font-size: 0.865rem; color: #475569; line-height: 1.7;
        }

        /* ── Tentang ────────────────────────────────────────────────── */
        .lp-tentang { background: var(--navy); padding: 5rem 1.5rem; }
        .lp-tentang-grid {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 4rem; align-items: center;
        }
        .lp-tentang-h2 {
            font-size: clamp(1.6rem, 3vw, 2rem); font-weight: 800;
            color: var(--yellow); margin-bottom: 1.5rem; letter-spacing: -0.02em;
        }
        .lp-tentang-body {
            color: #e2e8f0; line-height: 1.85;
            margin-bottom: 2rem; font-size: 0.95rem;
        }
        .lp-tentang-list { display: flex; flex-direction: column; gap: 0.8rem; }
        .lp-tentang-list li {
            display: flex; align-items: flex-start; gap: 0.8rem;
            color: #e2e8f0; font-size: 0.925rem;
        }
        .lp-check { color: var(--yellow); font-weight: 700; flex-shrink: 0; margin-top: 1px; }
        .lp-tentang-illus {
            border: 2px dashed rgba(253,184,19,0.45);
            border-radius: 16px; min-height: 280px;
            display: flex; align-items: center; justify-content: center;
            color: rgba(253,184,19,0.55); font-size: 0.875rem; font-weight: 500;
        }

        /* ── CTA ────────────────────────────────────────────────────── */
        .lp-cta {
            background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);
            padding: 5rem 1.5rem; text-align: center;
        }
        .lp-cta h2 {
            font-size: clamp(1.6rem, 3vw, 2rem); font-weight: 800;
            color: var(--navy); margin-bottom: 1rem; letter-spacing: -0.02em;
        }
        .lp-cta p { color: var(--text-muted); margin-bottom: 2.5rem; font-size: 1rem; line-height: 1.7; }
        .lp-cta-btns {
            display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;
        }
        .btn-primary-lg {
            background: var(--yellow); color: var(--navy);
            padding: 0.9rem 2.5rem; border-radius: 12px;
            font-weight: 700; font-size: 1rem;
            box-shadow: 0 4px 14px rgba(253,184,19,0.35);
            transition: background 0.2s; display: inline-block;
        }
        .btn-primary-lg:hover { background: var(--yellow-h); }
        .btn-outline-lg {
            background: transparent; color: var(--navy);
            padding: 0.9rem 2.5rem; border-radius: 12px;
            font-weight: 700; font-size: 1rem;
            border: 2px solid var(--navy);
            transition: background 0.2s; display: inline-block;
        }
        .btn-outline-lg:hover { background: var(--navy-light); }

        /* ── Footer ─────────────────────────────────────────────────── */
        .lp-footer { background: var(--navy); padding: 3.5rem 1.5rem 1.5rem; }
        .lp-footer-grid {
            display: grid; grid-template-columns: 1fr 1fr 1fr;
            gap: 3rem; margin-bottom: 2.5rem;
        }
        .lp-footer-brand { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; }
        .lp-footer-logo {
            width: 40px; height: 40px;
            border: 2px dashed rgba(253,184,19,0.55); border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 8.5px; font-weight: 700; color: var(--yellow);
            text-align: center; line-height: 1.3; flex-shrink: 0;
        }
        .lp-footer-brand-name { font-weight: 700; font-size: 1.05rem; color: var(--white); }
        .lp-footer-tagline { font-size: 0.82rem; color: #94a3b8; line-height: 1.6; }
        .lp-footer-col h4 {
            font-size: 0.8rem; font-weight: 600; color: var(--yellow);
            text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 1rem;
        }
        .lp-footer-col li {
            font-size: 0.82rem; color: #cbd5e1; margin-bottom: 0.4rem; line-height: 1.5;
        }
        .lp-footer-logos { display: flex; gap: 0.75rem; flex-wrap: wrap; margin-top: 1.25rem; }
        .lp-footer-logo-pill {
            width: 46px; height: 46px;
            border: 1px dashed rgba(253,184,19,0.45); border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
            font-size: 8px; color: rgba(253,184,19,0.65); text-align: center; line-height: 1.4;
        }
        .lp-footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 1.25rem; text-align: center;
        }
        .lp-footer-bottom p { font-size: 0.79rem; color: #64748b; }

        /* ── Responsive ─────────────────────────────────────────────── */
        @media (max-width: 1024px) {
            .lp-fitur-grid { grid-template-columns: repeat(2, 1fr); }
            .lp-kpi-grid  { grid-template-columns: repeat(2, 1fr); }
            .lp-kpi-item  { border-left: 1px solid #cbd5e1; }
            .lp-kpi-item:first-child,
            .lp-kpi-item:nth-child(3) { border-left: none; }
        }
        @media (max-width: 768px) {
            .lp-nav-links  { display: none !important; }
            .lp-nav-mobile { display: flex !important; }
            .lp-hero { padding: 3rem 1.25rem 3rem; }
            .lp-hero-grid     { grid-template-columns: 1fr; gap: 2.5rem; }
            .lp-tentang-grid  { grid-template-columns: 1fr; gap: 2rem; }
            .lp-footer-grid   { grid-template-columns: 1fr; gap: 2rem; }
            .lp-tentang-illus { display: none; }
            #mini-map { height: 300px; }
        }
        @media (max-width: 600px) {
            .lp-fitur-grid { grid-template-columns: 1fr; }
            .lp-kpi-grid   { grid-template-columns: repeat(2, 1fr); }
            .lp-kpi-item   { border-left: none; border-top: 1px solid #cbd5e1; }
            .lp-kpi-item:first-child,
            .lp-kpi-item:nth-child(2) { border-top: none; }
            .lp-hero-btns  { flex-direction: column; align-items: flex-start; }
            .lp-cta-btns   { flex-direction: column; align-items: center; }
        }
    </style>
</head>
<body>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 0 · NAVBAR
     ═══════════════════════════════════════════════════════════════════ --}}
<nav class="lp-nav">
    <div class="lp-container lp-nav-inner">

        {{-- Brand --}}
        <a href="{{ route('landing') }}" class="lp-nav-brand">
            <img src="{{ asset('img/ULM-PNG-Baru.png') }}" alt="Logo ULM" style="height:38px;width:auto;object-fit:contain;flex-shrink:0;">
            <span class="lp-nav-brand-text">WebGIS Alumni Pilkom</span>
        </a>

        {{-- Desktop links --}}
        <div class="lp-nav-links">
            <a href="#tentang" class="lp-nav-link">Tentang</a>
            <a href="#fitur"   class="lp-nav-link">Fitur</a>
            <a href="{{ route('statistik.index') }}" class="lp-nav-link">Statistik</a>
            <a href="{{ route('peta') }}" class="lp-nav-cta">Buka Peta</a>
        </div>

        {{-- Mobile bar --}}
        <div class="lp-nav-mobile">
            <a href="{{ route('peta') }}" class="lp-nav-cta" style="font-size:0.825rem; padding:0.4rem 1rem;">Buka Peta</a>
            <button class="lp-hamburger" onclick="lpToggleMenu()" aria-label="Buka menu">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#334155" stroke-width="2" stroke-linecap="round">
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <line x1="3" y1="12" x2="21" y2="12"/>
                    <line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Mobile dropdown --}}
    <div id="lp-mobile-menu" class="lp-nav-dropdown">
        <a href="#tentang" onclick="lpCloseMenu()">Tentang</a>
        <a href="#fitur"   onclick="lpCloseMenu()">Fitur</a>
        <a href="{{ route('statistik.index') }}">Statistik</a>
    </div>
</nav>


{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 1 · HERO
     ═══════════════════════════════════════════════════════════════════ --}}
<section class="lp-hero">
    <div class="lp-container">
        <div class="lp-hero-grid">

            {{-- Left: text --}}
            <div>
                <img src="{{ asset('img/ULM-PNG-Baru.png') }}" alt="Logo ULM" style="height:80px;width:auto;object-fit:contain;margin-bottom:1.5rem;">

                <h1 class="lp-hero-h1">
                    Persebaran Alumni Pendidikan Komputer FKIP ULM
                </h1>

                <p class="lp-hero-sub">
                    Visualisasi spasial dan analisis tracer study alumni Jurusan
                    Pendidikan Komputer FKIP Universitas Lambung Mangkurat.
                </p>

                <div class="lp-hero-btns">
                    <a href="{{ route('peta') }}" class="btn-primary">Jelajahi Peta</a>
                    <a href="{{ route('statistik.index') }}" class="btn-outline">Lihat Statistik</a>
                </div>
            </div>

            {{-- Right: mini map --}}
            <div>
                <div class="lp-minimap-wrap" id="lp-minimap-wrap"
                     onclick="window.location.href='{{ route('peta') }}'">
                    <div id="mini-map"></div>
                    <div class="lp-minimap-overlay">
                        <div class="lp-minimap-overlay-inner">
                            <div class="icon">🗺️</div>
                            <div class="label">Klik untuk membuka peta interaktif →</div>
                        </div>
                    </div>
                </div>
                <p class="lp-minimap-hint">Preview peta — klik untuk eksplorasi penuh</p>
            </div>

        </div>
    </div>
</section>


{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 2 · KPI STRIP
     ═══════════════════════════════════════════════════════════════════ --}}
<section class="lp-kpi">
    <div class="lp-container">
        <div class="lp-kpi-grid">
            <div class="lp-kpi-item">
                <div class="lp-kpi-num">{{ $totalAlumni }}</div>
                <div class="lp-kpi-label">Alumni Terdata</div>
            </div>
            <div class="lp-kpi-item">
                <div class="lp-kpi-num">{{ $wilayahTerpetakan }}</div>
                <div class="lp-kpi-label">Kabupaten/Kota Terpetakan</div>
            </div>
            <div class="lp-kpi-item">
                <div class="lp-kpi-num">{{ $profilTracer }}</div>
                <div class="lp-kpi-label">Profil Tracer Study</div>
            </div>
            <div class="lp-kpi-item">
                <div class="lp-kpi-num">{{ $cakupanAngkatan }}</div>
                <div class="lp-kpi-label">Cakupan Angkatan</div>
            </div>
        </div>
    </div>
</section>


{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 3 · FITUR UTAMA
     ═══════════════════════════════════════════════════════════════════ --}}
<section id="fitur" class="lp-fitur">
    <div class="lp-container">
        <div class="lp-section-heading">
            <h2>Fitur Sistem</h2>
            <p>Apa yang bisa Anda lakukan di sini</p>
        </div>

        <div class="lp-fitur-grid">
            <div class="lp-card">
                <div class="lp-card-icon">🗺️</div>
                <h3>Peta Interaktif</h3>
                <p>Visualisasi spasial lokasi kerja dan domisili alumni dengan dukungan PostGIS, clustering otomatis, dan heatmap density.</p>
            </div>
            <div class="lp-card">
                <div class="lp-card-icon">📊</div>
                <h3>Dashboard Statistik</h3>
                <p>Analisis tracer study real-time: distribusi sektor, linearitas bidang, masa tunggu, dan tren per angkatan.</p>
            </div>
            <div class="lp-card">
                <div class="lp-card-icon">🔍</div>
                <h3>Filter Dinamis</h3>
                <p>Saring data berdasarkan wilayah, status pekerjaan, angkatan, bidang, dan kesesuaian dengan keilmuan TIK.</p>
            </div>
            <div class="lp-card">
                <div class="lp-card-icon">📥</div>
                <h3>Ekspor Laporan</h3>
                <p>Unduh data terfilter dalam format Excel multi-sheet dan PDF dengan insight otomatis.</p>
            </div>
        </div>
    </div>
</section>


{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 4 · TENTANG SISTEM
     ═══════════════════════════════════════════════════════════════════ --}}
<section id="tentang" class="lp-tentang">
    <div class="lp-container">
        <div class="lp-tentang-grid">

            {{-- Text --}}
            <div>
                <h2 class="lp-tentang-h2">Tentang Sistem</h2>
                <p class="lp-tentang-body">
                    Sistem ini merupakan implementasi Web-GIS dari penelitian skripsi untuk
                    Jurusan Pendidikan Komputer FKIP Universitas Lambung Mangkurat.
                    Dikembangkan untuk mendukung tracer study, dan
                    menjadi Spatial Decision Support Tool bagi Jurusan dalam
                    pengambilan kebijakan berbasis data spasial.
                </p>
                <ul class="lp-tentang-list">
                    <li><span class="lp-check">✓</span> Tracer study berbasis koordinat spasial</li>
                    <li><span class="lp-check">✓</span> Decision support untuk kebijakan prodi</li>
                    <li><span class="lp-check">✓</span> Sistem open-source dengan PostgreSQL + PostGIS</li>
                </ul>
            </div>

            {{-- Illustration --}}
            <img
                src="{{ asset('img/ilustrasi.png') }}"
                alt="Ilustrasi Sistem Web-GIS Alumni"
                style="border-radius:16px;box-shadow:0 8px 24px rgba(0,0,0,0.2);width:100%;height:auto;object-fit:cover;max-height:400px;"
            >

        </div>
    </div>
</section>


{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 5 · CTA
     ═══════════════════════════════════════════════════════════════════ --}}
<section class="lp-cta">
    <div class="lp-container" style="max-width: 640px;">
        <h2>Mulai Eksplorasi</h2>
        <p>Akses peta interaktif untuk mulai menganalisis distribusi alumni</p>
        <div class="lp-cta-btns">
            <a href="{{ route('peta') }}"        class="btn-primary-lg">Buka Peta</a>
            <a href="{{ route('statistik.index') }}"  class="btn-outline-lg">Lihat Dashboard</a>
        </div>
    </div>
</section>


{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 6 · FOOTER
     ═══════════════════════════════════════════════════════════════════ --}}
<footer class="lp-footer">
    <div class="lp-container">
        <div class="lp-footer-grid">

            {{-- Col 1: Brand --}}
            <div>
                <div class="lp-footer-brand">
                    <img src="{{ asset('img/ULM-PNG-Baru.png') }}" alt="Logo ULM" style="height:40px;width:auto;object-fit:contain;flex-shrink:0;">
                    <span class="lp-footer-brand-name">WebGIS Alumni Pilkom</span>
                </div>
                <p class="lp-footer-tagline">Visualisasi &amp; Tracer Study Alumni</p>
            </div>

            {{-- Col 2: Penelitian --}}
            <div class="lp-footer-col">
                <h4>Penelitian</h4>
                <ul>
                    <li>Skripsi oleh: Ahmad Sappauni</li>
                    <li>NIM: 2210131210010</li>
                    <li>Pembimbing 1: Drs. Harja Santana Purba, M.Kom., Ph.D.</li>
                    <li>Pembimbing 2: Rizky Pamuji, S.Kom., M.Kom.</li>
                    <li>Tahun: 2026</li>
                </ul>
            </div>

            {{-- Col 3: Institusi --}}
            <div class="lp-footer-col">
                <h4>Institusi</h4>
                <ul>
                    <li>Jurusan Pendidikan Komputer</li>
                    <li>Fakultas Keguruan dan Ilmu Pendidikan</li>
                    <li>Universitas Lambung Mangkurat</li>
                    <li>Banjarmasin, Kalimantan Selatan</li>
                </ul>
                <div class="lp-footer-logos">
                    <img src="{{ asset('img/ULM-PNG-Baru.png') }}" alt="Logo ULM" style="height:46px;width:auto;object-fit:contain;">
                </div>
            </div>

        </div>

        <div class="lp-footer-bottom">
            <p>© 2026 WebGIS Alumni Pilkom </p>
        </div>
    </div>
</footer>


{{-- ═══════════════════════════════════════════════════════════════════
     SCRIPTS
     ═══════════════════════════════════════════════════════════════════ --}}
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
/* ── Mini map ──────────────────────────────────────────────────────── */
(function () {
    var map = L.map('mini-map', {
        dragging:        false,
        scrollWheelZoom: false,
        doubleClickZoom: false,
        boxZoom:         false,
        keyboard:        false,
        zoomControl:     false,
        touchZoom:       false,
        attributionControl: false
    }).setView([-3.0, 115.0], 7);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18
    }).addTo(map);

    /* Warna per kabupaten/kota — sama dengan peta utama */
    var MINI_COLORS = {
        'banjarmasin':        '#004a87',
        'banjarbaru':         '#0ea5e9',
        'banjar':             '#10b981',
        'barito kuala':       '#f59e0b',
        'tanah laut':         '#ef4444',
        'tanah bumbu':        '#8b5cf6',
        'kotabaru':           '#ec4899',
        'tapin':              '#f97316',
        'hulu sungai selatan':'#14b8a6',
        'hulu sungai tengah': '#6366f1',
        'hulu sungai utara':  '#a855f7',
        'balangan':           '#fbbf24',
        'tabalong':           '#4ade80'
    };

    function miniNormalize(name) {
        if (!name) return '';
        var t = name.toString().toLowerCase().trim()
            .replace(/[^a-z0-9]+/gi, ' ')
            .replace(/\s+/g, ' ').trim();
        if (t === 'kota baru')   return 'kotabaru';
        if (t === 'banjar baru') return 'banjarbaru';
        return t.replace(/^(kabupaten|kab|kota)\s+/i, '').trim();
    }

    function getMiniColor(feature) {
        var p = (feature && feature.properties) || {};
        var raw = p.WADMKK || p.NAMOBJ || p.NAME_2 || p.name || p.nama || p.kabupaten || p.kota || '';
        return MINI_COLORS[miniNormalize(raw)] || '#94a3b8';
    }

    /* Load polygon Kalsel — letakkan di bawah marker */
    fetch('/data/data_kalsel.geojson')
        .then(function (r) { return r.json(); })
        .then(function (data) {
            L.geoJSON(data, {
                style: function (feature) {
                    return {
                        fillColor:   getMiniColor(feature),
                        weight:      1,
                        opacity:     1,
                        color:       '#475569',
                        dashArray:   '3',
                        fillOpacity: 0.5
                    };
                },
                interactive: false
            }).addTo(map).bringToBack();
        })
        .catch(function () {});

    /* 15 koordinat dummy di Kalimantan Selatan */
    var dummyCoords = [
        [-3.3225, 114.5902], [-2.4500, 115.3000], [-3.7800, 114.4500],
        [-2.2000, 115.5000], [-3.9500, 114.8500], [-2.8000, 115.6000],
        [-3.1500, 114.7500], [-2.6500, 115.1000], [-3.5000, 115.2000],
        [-2.0500, 115.7500], [-4.1000, 114.6500], [-3.6000, 115.4500],
        [-2.9000, 114.4000], [-3.2500, 115.8000], [-2.7500, 114.9000]
    ];

    dummyCoords.forEach(function (c) {
        L.circleMarker(c, {
            radius:      8,
            fillColor:   '#004a87',
            color:       '#fdb813',
            weight:      2,
            fillOpacity: 0.85
        }).addTo(map);
    });
}());

/* ── Mobile nav ────────────────────────────────────────────────────── */
function lpToggleMenu() {
    var m = document.getElementById('lp-mobile-menu');
    m.classList.toggle('open');
}
function lpCloseMenu() {
    document.getElementById('lp-mobile-menu').classList.remove('open');
}
</script>

</body>
</html>
