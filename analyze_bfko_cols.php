<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'd:/Bu Intan/data/bfko/Monitoring Pembayaran BFKO 2024_2025_Rincian Tgl Bayar.xlsx';

echo "=== Mencari Kolom Pembayaran Bulanan ===\n\n";

$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getSheet(0);

// Get highest column
$highestCol = $sheet->getHighestColumn();
$highestRow = $sheet->getHighestRow();
$highestColIdx = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

echo "Sheet dimension: $highestRow rows x $highestColIdx columns (A to $highestCol)\n\n";

// Read all rows
$rows = $sheet->toArray(null, true, true, false);

// Look at rows 0-5 with ALL columns
echo "First 6 rows with ALL columns:\n";
for ($rowIdx = 0; $rowIdx <= 5; $rowIdx++) {
    echo "\n--- ROW $rowIdx ---\n";
    $row = $rows[$rowIdx];
    for ($col = 0; $col < count($row); $col++) {
        $val = $row[$col];
        if (!empty($val)) {
            echo "  Col $col: $val\n";
        }
    }
}

// Check if months are in a different row structure
echo "\n\n=== Looking for Payment Columns ===\n";
// Search all rows 0-10 for any month name
$monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
               'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
               'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

for ($rowIdx = 0; $rowIdx <= 10; $rowIdx++) {
    $row = $rows[$rowIdx];
    $found = [];
    for ($col = 0; $col < count($row); $col++) {
        $val = trim((string)($row[$col] ?? ''));
        if (!empty($val)) {
            foreach ($monthNames as $m) {
                if (stripos($val, $m) !== false && strlen($val) < 30) {
                    $found[] = "Col$col=$val";
                }
            }
        }
    }
    if (!empty($found)) {
        echo "Row $rowIdx: " . implode(" | ", $found) . "\n";
    }
}

// Let's check the actual Excel structure by looking at raw cell values
echo "\n\n=== Sample Data Rows (4-10) - All Non-Empty Columns ===\n";
for ($rowIdx = 4; $rowIdx <= 10; $rowIdx++) {
    echo "\nRow $rowIdx:\n";
    $row = $rows[$rowIdx];
    for ($col = 0; $col < min(count($row), 50); $col++) {
        $val = $row[$col];
        if (!empty($val)) {
            echo "  [$col] $val\n";
        }
    }
}
