<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'd:/Bu Intan/data/bfko/Monitoring Pembayaran BFKO 2024_2025_Rincian Tgl Bayar.xlsx';

echo "=== Analisis File Excel BFKO Asli ===\n\n";

$spreadsheet = IOFactory::load($file);
$sheetNames = $spreadsheet->getSheetNames();

echo "Total Sheets: " . count($sheetNames) . "\n";
echo "Sheet Names:\n";
foreach ($sheetNames as $idx => $name) {
    echo "  $idx. $name\n";
}

echo "\n";

// Analyze each sheet
foreach ($sheetNames as $sheetIndex => $sheetName) {
    $sheet = $spreadsheet->getSheet($sheetIndex);
    $rows = $sheet->toArray();
    
    echo str_repeat("=", 70) . "\n";
    echo "SHEET: $sheetName\n";
    echo str_repeat("=", 70) . "\n";
    
    // Show first 20 rows to understand structure
    echo "First 20 rows:\n";
    for ($i = 0; $i < min(20, count($rows)); $i++) {
        $row = $rows[$i];
        // Show non-empty cells
        $cells = [];
        foreach ($row as $colIdx => $cell) {
            if (!empty($cell)) {
                $cells[] = "[$colIdx]=" . substr((string)$cell, 0, 30);
            }
        }
        if (!empty($cells)) {
            echo "Row $i: " . implode(" | ", $cells) . "\n";
        }
    }
    
    echo "\n";
    
    // Try to find header row
    echo "Looking for header row...\n";
    for ($i = 0; $i < min(15, count($rows)); $i++) {
        $rowStr = strtolower(implode('|', array_map('strval', $rows[$i])));
        if (strpos($rowStr, 'nip') !== false || strpos($rowStr, 'nama') !== false) {
            echo "Potential header at row $i: " . implode(' | ', array_filter($rows[$i])) . "\n";
        }
    }
    
    echo "\nTotal rows in sheet: " . count($rows) . "\n\n";
}
