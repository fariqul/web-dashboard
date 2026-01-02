<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'd:/Bu Intan/data/Rekapitulasi Pembayaran CC Juli -September 2025.xlsx';
$spreadsheet = IOFactory::load($file);

echo "=== CC Card Excel Sheet Names ===\n\n";
foreach ($spreadsheet->getSheetNames() as $index => $name) {
    echo "Sheet $index: $name\n";
    
    // Get first few rows to see structure
    $sheet = $spreadsheet->getSheet($index);
    $rows = $sheet->toArray();
    
    echo "  Total rows: " . count($rows) . "\n";
    
    // Show first 5 rows
    for ($i = 0; $i < min(5, count($rows)); $i++) {
        $row = array_slice($rows[$i], 0, 10);
        echo "  Row $i: " . implode(' | ', array_map(function($v) { return substr((string)$v, 0, 20); }, $row)) . "\n";
    }
    echo "\n";
}
