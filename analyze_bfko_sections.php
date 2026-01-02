<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'd:/Bu Intan/data/bfko/Monitoring Pembayaran BFKO 2024_2025_Rincian Tgl Bayar.xlsx';

echo "=== Analisis Lengkap Semua Section di Excel ===\n\n";

$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getSheet(0);
$rows = $sheet->toArray();

// Find all section headers (rows with text in first column but not numeric)
echo "Looking for sections/headers in the sheet...\n\n";
$sections = [];
for ($i = 0; $i < count($rows); $i++) {
    $firstCol = trim((string)($rows[$i][0] ?? ''));
    $secondCol = trim((string)($rows[$i][1] ?? ''));
    
    // Check if this is a section header (text only, not data row)
    if (!empty($firstCol) && !is_numeric($firstCol) && strlen($firstCol) > 5) {
        // Not a "No" header
        if ($firstCol !== 'No') {
            echo "Row $i: $firstCol\n";
            $sections[] = ['row' => $i, 'title' => $firstCol];
        }
    }
}

// Find if there's monthly payment section
echo "\n\nSearching for 'Angsuran' or 'Bulanan' sections...\n";
for ($i = 0; $i < count($rows); $i++) {
    $rowStr = implode(' ', array_map('strval', $rows[$i]));
    if (stripos($rowStr, 'angsuran') !== false || stripos($rowStr, 'bulanan') !== false || stripos($rowStr, 'cicilan') !== false) {
        echo "Row $i: $rowStr\n";
    }
}

// Check rows 15-25 to see if there's additional structure
echo "\n\nRows 15-30 content:\n";
for ($i = 15; $i <= 30 && $i < count($rows); $i++) {
    $rowContent = [];
    foreach ($rows[$i] as $colIdx => $cell) {
        if (!empty($cell)) {
            $rowContent[] = "[$colIdx]=" . substr((string)$cell, 0, 25);
        }
    }
    if (!empty($rowContent)) {
        echo "Row $i: " . implode(" | ", $rowContent) . "\n";
    }
}

// Check around row 100, 200, 300 to see different sections
echo "\n\nRows around 100:\n";
for ($i = 95; $i <= 105 && $i < count($rows); $i++) {
    $rowContent = [];
    foreach ($rows[$i] as $colIdx => $cell) {
        if (!empty($cell)) {
            $rowContent[] = "[$colIdx]=" . substr((string)$cell, 0, 25);
        }
    }
    if (!empty($rowContent)) {
        echo "Row $i: " . implode(" | ", $rowContent) . "\n";
    }
}

echo "\n\nRows around 200:\n";
for ($i = 195; $i <= 205 && $i < count($rows); $i++) {
    $rowContent = [];
    foreach ($rows[$i] as $colIdx => $cell) {
        if (!empty($cell)) {
            $rowContent[] = "[$colIdx]=" . substr((string)$cell, 0, 25);
        }
    }
    if (!empty($rowContent)) {
        echo "Row $i: " . implode(" | ", $rowContent) . "\n";
    }
}
