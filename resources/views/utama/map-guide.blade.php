<div id="map-guide-overlay" class="map-guide-overlay" hidden>
    <div
        id="map-guide-dialog"
        class="map-guide-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="map-guide-title"
        aria-describedby="map-guide-desc"
    >
        <div class="map-guide-head">
            <div>
                <span class="map-guide-kicker">Mulai Cepat</span>
                <h2 id="map-guide-title">Apa yang bisa dilakukan di peta ini?</h2>
            </div>

            <button
                id="map-guide-close"
                class="map-guide-close"
                type="button"
                aria-label="Tutup panduan"
                data-map-tooltip="Tutup panduan"
            >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <p id="map-guide-desc" class="map-guide-intro">
            Cari alumni, saring data, lalu klik pin atau wilayah untuk melihat detail.
        </p>

        <div class="map-guide-quick-grid" aria-label="Fitur utama peta">
            <div class="map-guide-feature">
                <span class="map-guide-feature-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"></circle>
                        <line x1="20" y1="20" x2="16.2" y2="16.2"></line>
                    </svg>
                </span>
                <strong>Cari</strong>
                <p>Nama, NIM, atau tempat kerja.</p>
            </div>

            <div class="map-guide-feature">
                <span class="map-guide-feature-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="22 3 2 3 10 12.5 10 19 14 21 14 12.5 22 3"></polygon>
                    </svg>
                </span>
                <strong>Filter</strong>
                <p>Status, angkatan, bidang, wilayah.</p>
            </div>

            <div class="map-guide-feature">
                <span class="map-guide-feature-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0z"></path>
                        <circle cx="12" cy="10" r="2.5"></circle>
                    </svg>
                </span>
                <strong>Klik Peta</strong>
                <p>Detail pin dan ringkasan wilayah.</p>
            </div>
        </div>

        <div id="map-guide-full" class="map-guide-full" hidden>
            <div class="map-guide-full-title">Panduan lengkap</div>
            <ul class="map-guide-full-list">
                <li>Ketik minimal 2 huruf untuk mencari alumni.</li>
                <li>Gunakan tombol filter untuk mempersempit data.</li>
                <li>Klik pin alumni untuk melihat ringkasan profil dan lokasi.</li>
                <li>Klik wilayah berwarna untuk melihat ringkasan kabupaten/kota.</li>
                <li>Gunakan tombol layer untuk marker, choropleth, heatmap, legenda, cluster, dan komponen peta.</li>
            </ul>
        </div>

        <div class="map-guide-actions">
            <button id="map-guide-hide" class="map-guide-secondary" type="button">
                Jangan tampilkan lagi
            </button>
            <button id="map-guide-more" class="map-guide-secondary" type="button" aria-expanded="false" aria-controls="map-guide-full">
                Panduan lengkap
            </button>
            <button id="map-guide-start" class="map-guide-primary" type="button">
                Mulai Jelajahi
            </button>
        </div>
    </div>
</div>

<div id="map-search-hint" class="map-search-hint" role="status" aria-live="polite" hidden>
    Mulai dari pencarian di sini
</div>
