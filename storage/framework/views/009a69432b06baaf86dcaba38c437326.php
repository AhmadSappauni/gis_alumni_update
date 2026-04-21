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
    <link rel="stylesheet" href="<?php echo e(asset('css/style.css')); ?>">
    
</head>
<body>
    <?php echo $__env->make('utama.filter-panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div id="map"></div>

    <div class="kompas-ui is-hidden" aria-hidden="true">
        <img src="<?php echo e(asset('img/kompas.png')); ?>" alt="Kompas">
    </div>

    <div class="status-legend" role="status" aria-live="polite">
        <div class="status-legend-title">Keterangan :</div>
        <div class="status-legend-summary" aria-label="Jumlah alumni">
            <img
                class="status-legend-logo"
                src="<?php echo e(asset('img/ULM-PNG-Baru.png')); ?>"
                alt="Logo ULM"
            >
            <div class="status-legend-summary-text">
                <div class="status-legend-summary-label">Jumlah Alumni</div>
                <div class="status-legend-summary-count" id="legend-total-count">0 orang</div>
            </div>
        </div>
        <div class="status-legend-item">
            <img src="<?php echo e(asset('img/icon alumni kerja.png')); ?>" alt="Alumni Bekerja">
            <span>Alumni Bekerja</span>
            <b id="legend-bekerja-count">0</b>
        </div>
        <div class="status-legend-item">
            <img src="<?php echo e(asset('img/icon alumni nganggur.png')); ?>" alt="Alumni Belum Bekerja">
            <span>Alumni Belum Bekerja</span>
            <b id="legend-belum-count">0</b>
        </div>
    </div>

    <?php echo $__env->make('utama.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('utama.daftar-alumni', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('utama.id-card', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('utama.cluster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet-betterscale@1.0.0/L.Control.BetterScale.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-minimap/3.6.1/Control.MiniMap.min.js"></script>
    <script>
        var alumniData = <?php echo json_encode($dataPekerjaan, 15, 512) ?>;
    </script>
    <script src="<?php echo e(asset('js/utama/map.js')); ?>"></script>
    <script src="<?php echo e(asset('js/utama/filter.js')); ?>"></script>
    <script src="<?php echo e(asset('js/utama/sidebar.js')); ?>"></script>
    <script src="<?php echo e(asset('js/utama/daftar-alumni.js')); ?>"></script>
    <script src="<?php echo e(asset('js/utama/id-card.js')); ?>"></script>
    <script src="<?php echo e(asset('js/utama/cluster.js')); ?>"></script>
    
</body>
</html>
<?php /**PATH D:\Aplikasi_Skripsi\gis_alumni_3\resources\views/index.blade.php ENDPATH**/ ?>