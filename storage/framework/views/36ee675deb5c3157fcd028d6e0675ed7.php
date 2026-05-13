<?php
    $title = (string)($title ?? '');
    $labels = is_array($labels ?? null) ? $labels : [];
    $values = is_array($values ?? null) ? $values : [];
    $color = (string)($color ?? '#0b5e9e');
    $showRank = (bool)($showRank ?? false);

    $len = min(count($labels), count($values));
    $labels = array_slice($labels, 0, $len);
    $values = array_slice($values, 0, $len);

    $maxVal = 0;
    foreach ($values as $v) {
        $n = is_numeric($v) ? (float) $v : 0.0;
        if ($n > $maxVal) $maxVal = $n;
    }
    $maxVal = $maxVal > 0 ? $maxVal : 1;

    $fmt = $fmt ?? fn($n) => number_format((float)($n ?? 0), 0, ',', '.');
?>

<?php if($title): ?>
    <div class="chart-title"><?php echo e($title); ?></div>
<?php endif; ?>

<div class="pdf-chart">
    <?php $__currentLoopData = $labels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $lab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $val = $values[$i] ?? 0;
            $num = is_numeric($val) ? (float) $val : 0.0;
            $width = max(0, min(100, ($num / $maxVal) * 100));
        ?>
        <div class="chart-row">
            <div class="chart-label">
                <?php if($showRank): ?>
                    <span class="chart-rank"><?php echo e($i + 1); ?>.</span>
                <?php endif; ?>
                <?php echo e($lab); ?>

            </div>
            <div class="chart-track">
                <div class="chart-bar" style="width: <?php echo e($width); ?>%; background: <?php echo e($color); ?>;"></div>
            </div>
            <div class="chart-value"><?php echo e($fmt($val)); ?></div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<?php /**PATH D:\Aplikasi_Skripsi\gis_alumni_5\resources\views/admin/statistik/pdf/partials/hbar.blade.php ENDPATH**/ ?>