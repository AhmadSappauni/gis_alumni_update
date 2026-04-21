<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebGIS Persebaran Alumni</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet-betterscale@1.0.0/L.Control.BetterScale.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet-minimap/3.6.1/Control.MiniMap.min.css" />
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    
</head>
<body>
    @include('utama.filter-panel')
    <div id="map"></div>

    <div class="kompas-ui is-hidden" aria-hidden="true">
        <img src="{{ asset('img/kompas.png') }}" alt="Kompas">
    </div>

    <div class="status-legend" role="status" aria-live="polite">
        <div class="status-legend-title">Keterangan :</div>
        <div class="status-legend-item">
            <img src="{{ asset('img/icon alumni kerja.png') }}" alt="Alumni Bekerja">
            <span>Alumni Bekerja</span>
            <b id="legend-bekerja-count">0</b>
        </div>
        <div class="status-legend-item">
            <img src="{{ asset('img/icon alumni nganggur.png') }}" alt="Alumni Belum Bekerja">
            <span>Alumni Belum Bekerja</span>
            <b id="legend-belum-count">0</b>
        </div>
    </div>

    @include('utama.sidebar')
    @include('utama.daftar-alumni')
    @include('utama.id-card')
    @include('utama.cluster')

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet-betterscale@1.0.0/L.Control.BetterScale.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-minimap/3.6.1/Control.MiniMap.min.js"></script>
    <script>
        var alumniData = @json($dataPekerjaan);
    </script>
    <script src="{{ asset('js/utama/map.js') }}"></script>
    <script src="{{ asset('js/utama/filter.js') }}"></script>
    <script src="{{ asset('js/utama/sidebar.js') }}"></script>
    <script src="{{ asset('js/utama/daftar-alumni.js') }}"></script>
    <script src="{{ asset('js/utama/id-card.js') }}"></script>
    <script src="{{ asset('js/utama/cluster.js') }}"></script>
    
</body>
</html>
