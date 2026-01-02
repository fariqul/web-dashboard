<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'd:/Bu Intan/data/Rekapitulasi Pembayaran CC Juli -September 2025.xlsx';

$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getSheet(0); // Juli 25 - 5657
$rows = $sheet->toArray();

echo "=== Debug: Looking for TOTAL PAYMENT row ===\n\n";

// Find TOTAL PAYMENT row
for ($i = 0; $i < count($rows); $i++) {
    $row = $rows[$i];
    $rowStr = implode('|', array_map('strval', $row));
    
    if (stripos($rowStr, 'TOTAL PAYMENT') !== false || 
        stripos($rowStr, 'NOMINAL REFUND') !== false ||
        stripos($rowStr, 'BIAYA ADM') !== false ||
        stripos($rowStr, 'TOTAL (A-B') !== false ||
        stripos($rowStr, 'IURAN TAHUNAN') !== false) {
        
        echo "Row $i:\n";
        foreach ($row as $colIdx => $val) {
            if (!empty($val)) {
                echo "  Col $colIdx: " . var_export($val, true) . "\n";
            }
        }
        echo "\n";
    }
}
