<?php

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($argc < 2) {
    fwrite(STDERR, "Usage: php tools/inspect_xlsx.php <path-to-xlsx>\n");
    exit(2);
}

$path = $argv[1];
if (!is_file($path)) {
    fwrite(STDERR, "File not found: {$path}\n");
    exit(2);
}

$spreadsheet = IOFactory::load($path);
$sheet = $spreadsheet->getSheet(0);
$rows = $sheet->toArray(null, true, true, true);

$header = $rows[1] ?? [];
echo "HEADER\n";
echo json_encode($header, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";

echo "SAMPLE\n";
for ($i = 2; $i <= 6; $i++) {
    if (!isset($rows[$i])) {
        continue;
    }
    echo json_encode($rows[$i], JSON_UNESCAPED_UNICODE) . "\n";
}

