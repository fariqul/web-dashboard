<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'd:/Bu Intan/data/bfko/Monitoring Pembayaran BFKO 2024_2025_Rincian Tgl Bayar.xlsx';

echo "=== Detail Struktur Excel BFKO ===\n\n";

$spreadsheet = IOFactory::load($file);

// Analyze first sheet
$sheet = $spreadsheet->getSheet(0);
$sheetName = $spreadsheet->getSheetNames()[0];
$rows = $sheet->toArray();

echo "Analyzing sheet: $sheetName\n\n";

// Show header row (row 3) with all columns
echo "HEADER ROW (Row 3) - All Columns:\n";
$headerRow = $rows[3];
foreach ($headerRow as $colIdx => $cell) {
    if (!empty($cell)) {
        echo "  Col $colIdx: $cell\n";
    }
}

echo "\n\nDATA ROW SAMPLE (Row 4) - All Columns:\n";
$dataRow = $rows[4];
foreach ($dataRow as $colIdx => $cell) {
    if (!empty($cell) || $colIdx < 30) {
        $cellValue = is_null($cell) ? 'NULL' : $cell;
        echo "  Col $colIdx: $cellValue\n";
    }
}

// Check for month columns - usually after main data
echo "\n\nLooking for month columns in header row...\n";
$monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
               'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

// Check rows 2-3 for month names (might be in merged header)
for ($rowIdx = 0; $rowIdx <= 3; $rowIdx++) {
    echo "\nRow $rowIdx months:\n";
    foreach ($rows[$rowIdx] as $colIdx => $cell) {
        $cellStr = (string)$cell;
        foreach ($monthNames as $month) {
            if (stripos($cellStr, $month) !== false) {
                echo "  Col $colIdx: $cell\n";
            }
        }
    }
}

// Show complete row 3 (header) as array
echo "\n\nComplete Row 3 (raw):\n";
print_r(array_filter($rows[3], function($v) { return !empty($v); }));

// Show row structure from col 9 onwards
echo "\n\nColumns 9-50 in rows 2-4:\n";
for ($rowIdx = 2; $rowIdx <= 4; $rowIdx++) {
    echo "Row $rowIdx: ";
    $cells = [];
    for ($col = 9; $col <= 50 && $col < count($rows[$rowIdx]); $col++) {
        $val = $rows[$rowIdx][$col] ?? '';
        if (!empty($val)) {
            $cells[] = "[$col]=" . substr((string)$val, 0, 15);
        }
    }
    echo implode(" | ", $cells) . "\n";
}
