<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Perbandingan GIS dan PostGIS</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="{{ asset('css/postgis-demo/postgis-demo.css') }}">
</head>
<body>
    <main class="demo-shell">
        <header class="demo-header">
            <div>
                <p class="demo-eyebrow">Demo internal WebGIS Alumni Pendidikan Komputer</p>
                <h1>Perbedaan GIS tanpa PostGIS dan GIS dengan PostGIS</h1>
                <p class="demo-lead">
                    Halaman ini memakai sampel data alumni yang memiliki koordinat domisili. Data dibatasi maksimal 20 titik agar ringan untuk server dan aman untuk deploy.
                </p>
            </div>
            <aside class="demo-status-box" aria-live="polite">
                <span class="status-dot" id="postgis-status-dot"></span>
                <span id="postgis-status-text">Memuat data demo...</span>
            </aside>
        </header>

        <section class="map-section" aria-label="Peta sampel alumni">
            <div id="postgis-demo-map"></div>
        </section>

        <section class="demo-tabs" aria-label="Pilihan tampilan demo">
            <button type="button" class="tab-button is-active" data-demo-tab="tanpa">
                Lihat Cara Tanpa PostGIS
            </button>
            <button type="button" class="tab-button" data-demo-tab="dengan">
                Lihat Cara Dengan PostGIS
            </button>
            <button type="button" class="tab-button" data-demo-tab="bandingkan">
                Bandingkan Hasil
            </button>
        </section>

        <section class="tab-content">
            <article class="tab-panel is-active" data-demo-panel="tanpa">
                <h2>GIS tanpa PostGIS</h2>
                <p>
                    Tanpa PostGIS, sistem hanya menampilkan titik dari latitude dan longitude. Database membaca koordinat sebagai angka biasa, lalu wilayah biasanya disimpulkan dari teks alamat, kota, atau provinsi.
                </p>
                <pre><code>SELECT nama_lengkap, latitude, longitude, kota, provinsi
FROM alumnis
JOIN alamat_alumni ON alamat_alumni.alumni_id = alumnis.id
WHERE latitude IS NOT NULL AND longitude IS NOT NULL;</code></pre>
            </article>

            <article class="tab-panel" data-demo-panel="dengan">
                <h2>GIS dengan PostGIS</h2>
                <p>
                    Dengan PostGIS, database dapat melakukan analisis spasial seperti mengecek titik berada di dalam batas wilayah. Titik dibentuk dari longitude dan latitude, lalu dibandingkan dengan polygon wilayah.
                </p>
                <pre><code>ST_Within(
    ST_SetSRID(ST_MakePoint(longitude, latitude), 4326),
    wilayah_geom
)</code></pre>
            </article>

            <article class="tab-panel" data-demo-panel="bandingkan">
                <h2>Bandingkan hasil</h2>
                <p>
                    Kolom "Wilayah dari Teks" berasal dari data alamat biasa. Kolom "Wilayah dari PostGIS" berasal dari deteksi posisi titik di dalam polygon wilayah_kalsel.geom.
                </p>
                <div class="summary-grid" aria-label="Ringkasan hasil perbandingan">
                    <div>
                        <span id="summary-total">0</span>
                        <small>Sampel</small>
                    </div>
                    <div>
                        <span id="summary-sama">0</span>
                        <small>Sama</small>
                    </div>
                    <div>
                        <span id="summary-berbeda">0</span>
                        <small>Berbeda</small>
                    </div>
                    <div>
                        <span id="summary-tidak">0</span>
                        <small>Tidak terdeteksi</small>
                    </div>
                </div>
            </article>
        </section>

        <section class="compare-panels" aria-label="Panel perbandingan GIS">
            <article class="compare-card compare-card-left">
                <p class="panel-label">Panel kiri</p>
                <h2>GIS tanpa PostGIS</h2>
                <ul>
                    <li>Latitude dan longitude disimpan sebagai angka.</li>
                    <li>Sistem dapat menampilkan marker di peta.</li>
                    <li>Wilayah biasanya diambil dari teks kota atau provinsi.</li>
                    <li>Database belum benar-benar memahami relasi titik dan batas wilayah.</li>
                </ul>
            </article>

            <article class="compare-card compare-card-right">
                <p class="panel-label">Panel kanan</p>
                <h2>GIS dengan PostGIS</h2>
                <ul>
                    <li>Titik dapat diolah sebagai geometry atau geography.</li>
                    <li>ST_Contains dan ST_Within mengecek titik di dalam polygon.</li>
                    <li>ST_Distance dapat menghitung jarak langsung di database.</li>
                    <li>Analisis spasial dapat dilakukan tanpa memindahkan logika ke JavaScript.</li>
                </ul>
            </article>
        </section>

        <section class="table-section" aria-label="Tabel hasil perbandingan">
            <div class="section-heading">
                <div>
                    <p class="demo-eyebrow">Hasil sampel data</p>
                    <h2>Tabel perbandingan deteksi wilayah</h2>
                </div>
                <p id="table-helper">Mengambil maksimal 20 data alumni dari endpoint demo...</p>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nama Alumni</th>
                            <th>Koordinat</th>
                            <th>Wilayah dari Teks</th>
                            <th>Wilayah dari PostGIS</th>
                            <th>Hasil</th>
                        </tr>
                    </thead>
                    <tbody id="postgis-demo-table">
                        <tr>
                            <td colspan="5" class="empty-cell">Memuat data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        window.postgisDemoConfig = {
            dataUrl: @json($dataUrl),
        };
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="{{ asset('js/postgis-demo/postgis-demo.js') }}"></script>
</body>
</html>
