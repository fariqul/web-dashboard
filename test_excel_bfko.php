<?php
// Test konversi Excel BFKO ke CSV

require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = __DIR__ . '/data/bfko/sample_bfko_test.xlsx';

if (!file_exists($file)) {
    // Try original bfko.csv converted to xlsx style
    echo "File sample_bfko_test.xlsx tidak ditemukan\n";
    echo "Coba dengan bfko.csv...\n";
    $file = __DIR__ . '/data/bfko/bfko.csv';
}

if (!file_exists($file)) {
    echo "Tidak ada file test!\n";
    exit(1);
}

echo "Loading file: $file\n\n";

$extension = pathinfo($file, PATHINFO_EXTENSION);

if ($extension === 'csv') {
    // Read CSV directly
    echo "=== CSV Format ===\n";
    $handle = fopen($file, 'r');
    for ($i = 0; $i < 10; $i++) {
        $row = fgetcsv($handle);
        if ($row === false) break;
        echo "Row $i: " . implode(' | ', array_slice($row, 0, 10)) . "\n";
    }
    fclose($handle);
} else {
    // Read Excel
    echo "=== Excel Format ===\n";
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();
    
    echo "Total rows: " . count($rows) . "\n\n";
    
    for ($i = 0; $i < min(10, count($rows)); $i++) {
        $row = $rows[$i];
        echo "Row $i: " . implode(' | ', array_slice($row, 0, 10)) . "\n";
    }
}
