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
    <link rel="stylesheet" href="<?php echo e(asset('css/style.css')); ?>">
    
</head>
<body>
    <?php echo $__env->make('utama.filter-panel', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div id="map"></div>
    <?php echo $__env->make('utama.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('utama.daftar-alumni', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('utama.id-card', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('utama.cluster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
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
</html><?php /**PATH D:\Aplikasi_Skripsi\gis-alumni\resources\views/index.blade.php ENDPATH**/ ?>