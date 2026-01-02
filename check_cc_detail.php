<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'd:/Bu Intan/data/Rekapitulasi Pembayaran CC Juli -September 2025.xlsx';

echo "=== Detailed CC Card Excel Analysis ===\n\n";

$spreadsheet = IOFactory::load($file);
$sheetNames = $spreadsheet->getSheetNames();

// Analyze first sheet in detail
$sheetIndex = 0;
$sheetName = $sheetNames[$sheetIndex];
$sheet = $spreadsheet->getSheet($sheetIndex);
$rows = $sheet->toArray();

echo "Analyzing Sheet: $sheetName\n";
echo "Total rows: " . count($rows) . "\n\n";

echo "=== ALL ROWS (showing first 15 columns) ===\n";
for ($i = 0; $i < count($rows); $i++) {
    $row = $rows[$i];
    
    // Show first 15 columns
    $displayRow = array_slice($row, 0, 15);
    $displayStr = implode(' | ', array_map(function($v) { 
        $s = trim((string)$v);
        return substr($s, 0, 25); 
    }, $displayRow));
    
    echo "Row $i: $displayStr\n";
    
    // Stop after finding some key data and summary
    if ($i > 340) break;
}

echo "\n=== Looking for Summary/Total rows ===\n";
for ($i = count($rows) - 20; $i < count($rows); $i++) {
    if ($i < 0) continue;
    $row = $rows[$i];
    $displayRow = array_slice($row, 0, 15);
    $displayStr = implode(' | ', array_map(function($v) { 
        $s = trim((string)$v);
        return substr($s, 0, 25); 
    }, $displayRow));
    
    echo "Row $i: $displayStr\n";
}
