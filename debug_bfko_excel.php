<?php
/**
 * Debug script to check raw Excel values for BFKO
 */
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$inputFile = 'd:/Bu Intan/data/bfko/Monitoring Pembayaran BFKO 2024_2025_Rincian Tgl Bayar.xlsx';

echo "=== BFKO Excel Raw Data Check ===\n\n";

$spreadsheet = IOFactory::load($inputFile);
$sheetNames = $spreadsheet->getSheetNames();

echo "Sheets found: " . count($sheetNames) . "\n";
print_r($sheetNames);

// Check first sheet
$sheet = $spreadsheet->getSheet(0);
$rows = $sheet->toArray(null, true, true, false);

echo "\n=== Looking for SETIYAWAN or high values ===\n\n";

// Search for SETIYAWAN to understand where the 30B value comes from
for ($i = 0; $i < count($rows); $i++) {
    $row = $rows[$i];
    foreach ($row as $colIdx => $cell) {
        if (is_string($cell) && stripos($cell, 'SETIYAWAN') !== false) {
            echo "Found SETIYAWAN at row $i:\n";
            // Show the row
            for ($c = 0; $c < min(15, count($row)); $c++) {
                $val = $row[$c] ?? 'NULL';
                if (is_numeric($val) && $val > 1000000) {
                    $val = number_format((float)$val, 2);
                }
                echo "  Col $c: $val\n";
            }
            // Show more columns
            echo "  Additional columns:\n";
            for ($c = 15; $c < count($row); $c++) {
                $val = $row[$c] ?? 'NULL';
                if (!empty($val)) {
                    if (is_numeric($val) && $val > 1000) {
                        $val = number_format((float)$val, 2);
                    }
                    echo "  Col $c: $val\n";
                }
            }
            echo "\n";
        }
    }
}

// Also check a few sample rows from the data section to understand values
echo "\n=== Sample rows from data section ===\n\n";

// Find the Angsuran section
$angsuranStartRow = -1;
for ($i = 0; $i < count($rows); $i++) {
    $firstCol = trim((string)($rows[$i][0] ?? ''));
    if (stripos($firstCol, 'Angsuran Bulanan') !== false) {
        $angsuranStartRow = $i;
        break;
    }
}

if ($angsuranStartRow >= 0) {
    echo "Angsuran section starts at row $angsuranStartRow\n";
    
    // Show header rows
    echo "\nHeader rows:\n";
    for ($h = $angsuranStartRow; $h < $angsuranStartRow + 4; $h++) {
        echo "Row $h: ";
        for ($c = 0; $c < min(20, count($rows[$h])); $c++) {
            $val = $rows[$h][$c] ?? '';
            echo "[$c:" . substr((string)$val, 0, 15) . "] ";
        }
        echo "\n";
    }
    
    // Show first 3 data rows
    echo "\nFirst data rows:\n";
    $dataStart = $angsuranStartRow + 3; // Skip headers
    for ($d = $dataStart; $d < min($dataStart + 3, count($rows)); $d++) {
        $row = $rows[$d];
        $nip = $row[1] ?? '';
        if (empty($nip) || !preg_match('/^\d/', $nip)) continue;
        
        echo "Row $d (NIP: $nip):\n";
        for ($c = 0; $c < min(20, count($row)); $c++) {
            $val = $row[$c] ?? '';
            if (!empty($val)) {
                if (is_numeric($val) && abs($val) > 1000) {
                    echo "  Col $c: " . number_format((float)$val, 2) . " (raw: $val)\n";
                } else {
                    echo "  Col $c: $val\n";
                }
            }
        }
        echo "\n";
    }
}
