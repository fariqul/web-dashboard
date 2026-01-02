<?php
// Test konversi Excel BFKO dengan algoritma yang diperbaiki

require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

$file = __DIR__ . '/data/bfko/sample_bfko_test.xlsx';

if (!file_exists($file)) {
    echo "File tidak ditemukan: $file\n";
    exit(1);
}

echo "=== Test Konversi Excel BFKO ===\n\n";

$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray();

echo "Total rows: " . count($rows) . "\n\n";

// Find header row with NIP column
$headerRowIndex = -1;
$headerRow = null;

for ($i = 0; $i < min(10, count($rows)); $i++) {
    $row = $rows[$i];
    foreach ($row as $cell) {
        $cellClean = strtoupper(trim((string)$cell));
        if ($cellClean === 'NIP') {
            $headerRow = $row;
            $headerRowIndex = $i;
            echo "Header found at row: $i\n";
            break 2;
        }
    }
}

if (!$headerRow) {
    echo "ERROR: Header row with NIP not found!\n";
    exit(1);
}

// Find column indices
$nipCol = null;
$namaCol = null;
$jabatanCol = null;
$unitCol = null;

foreach ($headerRow as $colIndex => $colName) {
    $cellClean = strtoupper(trim((string)$colName));
    if ($cellClean === 'NIP') {
        $nipCol = $colIndex;
    } elseif (stripos($colName, 'Nama') !== false && $namaCol === null) {
        $namaCol = $colIndex;
    } elseif (stripos($colName, 'Jabatan') !== false && $jabatanCol === null) {
        $jabatanCol = $colIndex;
    } elseif (stripos($colName, 'Unit') !== false) {
        $unitCol = $colIndex;
    }
}

echo "Columns found: NIP=$nipCol, Nama=$namaCol, Jabatan=$jabatanCol, Unit=$unitCol\n";

// Find month columns in header row
$monthColumns = [];
$monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
              'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

foreach ($headerRow as $colIndex => $colName) {
    $cellClean = trim((string)$colName);
    foreach ($monthNames as $month) {
        if (strcasecmp($cellClean, $month) === 0 && !isset($monthColumns[$month])) {
            $monthColumns[$month] = $colIndex;
            break;
        }
    }
}

echo "Month columns found: " . json_encode($monthColumns) . "\n\n";

// Detect year
$year = date('Y');
foreach ($rows as $idx => $row) {
    if ($idx > 5) break;
    foreach ($row as $cell) {
        if (preg_match('/(\d{4})/', (string)$cell, $matches)) {
            $year = $matches[1];
            echo "Year detected: $year\n";
            break 2;
        }
    }
}

// Build CSV
$csvLines = [];
$csvLines[] = 'nip,nama,jabatan,unit,bulan,tahun,nilai_angsuran,tanggal_bayar,status_angsuran';

$dataStartRow = $headerRowIndex + 1;
$processedCount = 0;

for ($i = $dataStartRow; $i < count($rows); $i++) {
    $row = $rows[$i];
    
    $nipValue = isset($row[$nipCol]) ? trim((string)$row[$nipCol]) : '';
    if (empty($nipValue) || !preg_match('/[0-9]/', $nipValue)) {
        continue;
    }
    
    $nip = str_replace('"', '""', $nipValue);
    $nama = str_replace('"', '""', trim((string)($row[$namaCol] ?? '')));
    $jabatan = str_replace('"', '""', trim((string)($row[$jabatanCol] ?? '')));
    $unit = str_replace('"', '""', trim((string)($row[$unitCol] ?? '')));
    
    if (empty($nama)) continue;
    
    foreach ($monthColumns as $month => $colIndex) {
        if (!isset($row[$colIndex])) continue;
        
        $nilai = $row[$colIndex];
        if (is_numeric($nilai)) {
            $nilai = (int)$nilai;
        } else {
            $nilai = (int)preg_replace('/[^\d]/', '', (string)$nilai);
        }
        
        if ($nilai > 0) {
            $tanggalBayar = '';
            if (isset($row[$colIndex + 1])) {
                $dateVal = $row[$colIndex + 1];
                if ($dateVal instanceof DateTime) {
                    $tanggalBayar = $dateVal->format('Y-m-d');
                } elseif (is_string($dateVal) && strtotime($dateVal)) {
                    $tanggalBayar = date('Y-m-d', strtotime($dateVal));
                }
            }
            
            $csvLines[] = sprintf(
                '"%s","%s","%s","%s","%s","%s","%s","%s","%s"',
                $nip, $nama, $jabatan, $unit, $month, $year, $nilai, $tanggalBayar, 'Lunas'
            );
            $processedCount++;
        }
    }
}

echo "\nCSV Lines generated: " . count($csvLines) . "\n";
echo "Payments processed: $processedCount\n\n";

echo "=== Sample CSV Output ===\n";
for ($i = 0; $i < min(5, count($csvLines)); $i++) {
    echo $csvLines[$i] . "\n";
}
