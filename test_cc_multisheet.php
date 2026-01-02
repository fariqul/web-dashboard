<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'd:/Bu Intan/data/Rekapitulasi Pembayaran CC Juli -September 2025.xlsx';

echo "=== Test CC Card Multi-Sheet Conversion ===\n\n";

$spreadsheet = IOFactory::load($file);
$sheetNames = $spreadsheet->getSheetNames();

echo "Sheets found: " . count($sheetNames) . "\n";
foreach ($sheetNames as $name) {
    echo "  - $name\n";
}
echo "\n";

// Test parseSheetName function
function parseSheetName($sheetName) {
    $monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                   'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    
    $month = '';
    $year = '';
    $ccNumber = '';
    
    foreach ($monthNames as $m) {
        if (stripos($sheetName, $m) !== false) {
            $month = $m;
            break;
        }
    }
    
    if (preg_match('/\b(\d{2})\b/', $sheetName, $matches)) {
        $year = '20' . $matches[1];
    } elseif (preg_match('/\b(20\d{2})\b/', $sheetName, $matches)) {
        $year = $matches[1];
    } else {
        $year = date('Y');
    }
    
    if (preg_match('/(\d{4})\s*$/', $sheetName, $matches)) {
        $ccNumber = $matches[1];
    } elseif (preg_match('/[-–]\s*(\d{4})/', $sheetName, $matches)) {
        $ccNumber = $matches[1];
    } else {
        $ccNumber = '5657';
    }
    
    if ($month && $year) {
        return "$month $year - CC $ccNumber";
    }
    return $sheetName;
}

echo "Sheet Name Parsing:\n";
foreach ($sheetNames as $name) {
    $parsed = parseSheetName($name);
    echo "  '$name' => '$parsed'\n";
}
echo "\n";

// Count data per sheet
$totalData = 0;
foreach ($sheetNames as $index => $name) {
    $sheet = $spreadsheet->getSheet($index);
    $rows = $sheet->toArray();
    
    // Find header row
    $headerRowIndex = -1;
    for ($i = 0; $i < min(10, count($rows)); $i++) {
        $rowString = implode('|', array_map('strval', $rows[$i]));
        if (stripos($rowString, 'Booking ID') !== false) {
            $headerRowIndex = $i;
            break;
        }
    }
    
    if ($headerRowIndex < 0) {
        echo "Sheet '$name': Header not found\n";
        continue;
    }
    
    // Count data rows (rows with valid booking ID)
    $dataCount = 0;
    $bookingIdCol = null;
    foreach ($rows[$headerRowIndex] as $idx => $val) {
        if (stripos((string)$val, 'Booking ID') !== false) {
            $bookingIdCol = $idx;
            break;
        }
    }
    
    for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
        $bookingId = $rows[$i][$bookingIdCol] ?? '';
        if (!empty($bookingId) && preg_match('/\d/', $bookingId)) {
            $dataCount++;
        }
    }
    
    echo "Sheet '$name': $dataCount transactions\n";
    $totalData += $dataCount;
}

echo "\nTotal transactions across all sheets: $totalData\n";
