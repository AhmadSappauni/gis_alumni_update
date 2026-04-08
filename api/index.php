<?php

// Pastikan config dicache ke folder /tmp agar tidak error permission
putenv('VIEW_COMPILED_PATH=/tmp');

require __DIR__ . '/../public/index.php';