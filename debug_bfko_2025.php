<?php
/**
 * Debug script to check raw Excel values for BFKO - Sheet 2025
 */
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$inputFile = 'd:/Bu Intan/data/bfko/Monitoring Pembayaran BFKO 2024_2025_Rincian Tgl Bayar.xlsx';

echo "=== BFKO Excel Sheet 2025 Check ===\n\n";

$spreadsheet = IOFactory::load($inputFile);

// Check sheet 2 (2025)
$sheet = $spreadsheet->getSheet(1);
$rows = $sheet->toArray(null, true, true, false);

echo "Sheet: " . $spreadsheet->getSheetNames()[1] . "\n";
echo "Total rows: " . count($rows) . "\n\n";

// Search for SETIYAWAN
echo "=== Looking for SETIYAWAN ===\n\n";
for ($i = 0; $i < count($rows); $i++) {
    $row = $rows[$i];
    foreach ($row as $colIdx => $cell) {
        if (is_string($cell) && stripos($cell, 'SETIYAWAN') !== false) {
            echo "Found SETIYAWAN at row $i:\n";
            for ($c = 0; $c < count($row); $c++) {
                $val = $row[$c] ?? '';
                if (!empty($val)) {
                    echo "  Col $c: $val\n";
                }
            }
            echo "\n";
        }
    }
}

// Search for YULI ASHANIAIS
echo "=== Looking for YULI ===\n\n";
for ($i = 0; $i < count($rows); $i++) {
    $row = $rows[$i];
    foreach ($row as $colIdx => $cell) {
        if (is_string($cell) && stripos($cell, 'YULI') !== false) {
            echo "Found YULI at row $i:\n";
            for ($c = 0; $c < count($row); $c++) {
                $val = $row[$c] ?? '';
                if (!empty($val)) {
                    echo "  Col $c: $val\n";
                }
            }
            echo "\n";
        }
    }
}

// Find the Angsuran section
echo "=== Angsuran section structure ===\n\n";
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
    for ($h = $angsuranStartRow; $h < min($angsuranStartRow + 4, count($rows)); $h++) {
        echo "Row $h: ";
        for ($c = 0; $c < min(25, count($rows[$h])); $c++) {
            $val = $rows[$h][$c] ?? '';
            if (!empty($val)) {
                echo "[$c:" . substr((string)$val, 0, 12) . "] ";
            }
        }
        echo "\n";
    }
}

// Also show first few data rows with all columns
echo "\n=== First 3 data rows with all values ===\n\n";
$dataStart = $angsuranStartRow + 3;
for ($d = $dataStart; $d < min($dataStart + 5, count($rows)); $d++) {
    $row = $rows[$d];
    $nip = $row[1] ?? '';
    if (empty($nip) || !preg_match('/^\d/', $nip)) continue;
    
    $nama = $row[2] ?? '';
    echo "Row $d: $nip - $nama\n";
    
    // Show all columns with values
    for ($c = 6; $c < count($row); $c++) {
        $val = $row[$c] ?? '';
        if (!empty($val) && $val != '0' && $val != '-') {
            echo "  Col $c: [$val]\n";
        }
    }
    echo "\n";
}
