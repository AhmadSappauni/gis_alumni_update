<div id="sidebar-overlay" class="sidebar-overlay"></div>

<div id="main-sidebar" class="main-sidebar" role="dialog" aria-modal="true" aria-label="Menu Utama">
    <div class="sidebar-top">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg>
            </div>
            <div class="sidebar-brand-text">
                <h3 id="sidebar-title">GIS ALUMNI</h3>
                <p>Pendidikan Komputer ULM</p>
            </div>
        </div>

        <button id="close-sidebar" class="close-sidebar-btn" type="button" aria-label="Tutup menu">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>

    <div id="sidebar-menu-utama" class="sidebar-menu" role="navigation" aria-label="Menu">
        <a href="{{ route('statistik.index') }}" class="menu-item" id="btn-statistik">
            <span class="menu-icon" aria-hidden="true">
                <span class="menu-icon-circle">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="20" x2="12" y2="10"></line>
                        <line x1="18" y1="20" x2="18" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="16"></line>
                    </svg>
                </span>
            </span>
            <span class="menu-content">
                <span class="menu-text">Statistik Alumni</span>
                <span class="menu-subtitle">Lihat ringkasan dan grafik alumni</span>
            </span>
        </a>

        <a href="#" class="menu-item" id="btn-daftar-mhs">
            <span class="menu-icon" aria-hidden="true">
                <span class="menu-icon-circle">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20"></path>
                        <path d="M20 2H6.5A2.5 2.5 0 0 0 4 4.5v15"></path>
                        <path d="M8 6h8"></path>
                        <path d="M8 10h8"></path>
                        <path d="M8 14h6"></path>
                    </svg>
                </span>
            </span>
            <span class="menu-content">
                <span class="menu-text">Buku Direktori Alumni</span>
                <span class="menu-subtitle">Lihat daftar lengkap alumni</span>
            </span>
        </a>
    </div>

    <div class="sidebar-footer" aria-label="Footer">
        <div class="sidebar-footer-title">WebGIS Alumni Pilkom ULM</div>
        <div class="sidebar-footer-meta">Copyrigth © 2026 Ahmad Sappauni.</div>
    </div>
</div>
