<?php
require 'vendor/autoload.php';

use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

// Test the convertExcelToCCCardCsv logic
$file = 'd:/Bu Intan/data/Rekapitulasi Pembayaran CC Juli -September 2025.xlsx';

echo "=== Testing CC Card Excel to CSV Conversion ===\n\n";

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
    } else {
        $year = date('Y');
    }
    
    if (preg_match('/(\d{4})\s*$/', $sheetName, $matches)) {
        $ccNumber = $matches[1];
    } else {
        $ccNumber = '5657';
    }
    
    return "$month $year - CC $ccNumber";
}

function parseSummaryAmount($val) {
    if (empty($val) || $val === '-' || trim((string)$val) === '-') {
        return 0;
    }
    $cleaned = preg_replace('/[^\d]/', '', trim((string)$val));
    return (float)$cleaned;
}

function extractSummarySection($rows) {
    $result = [
        'total_payment' => 0,
        'nominal_refund' => 0,
        'biaya_transfer' => 0,
        'iuran_tahunan' => 0,
        'biaya_adm_bunga' => 0,
        'refund_transactions' => [],
    ];
    
    $valueCol = 8;
    $inRefundSection = false;
    
    for ($i = 0; $i < count($rows); $i++) {
        $row = $rows[$i];
        $rowStr = implode('|', array_map('strval', $row));
        
        if (stripos($rowStr, 'TOTAL PAYMENT') !== false) {
            $result['total_payment'] = parseSummaryAmount($row[$valueCol] ?? '');
            $inRefundSection = true;
            continue;
        }
        
        if (stripos($rowStr, 'NOMINAL REFUND') !== false) {
            $result['nominal_refund'] = parseSummaryAmount($row[$valueCol] ?? '');
            $inRefundSection = false;
            continue;
        }
        
        if ($inRefundSection) {
            $rowNumStr = trim((string)($row[1] ?? ''));
            $bookingId = trim((string)($row[2] ?? ''));
            $name = trim((string)($row[3] ?? ''));
            $amount = parseSummaryAmount($row[$valueCol] ?? '');
            
            if (is_numeric($rowNumStr) && !empty($bookingId) && preg_match('/\d/', $bookingId) && $amount > 0) {
                $result['refund_transactions'][] = [
                    'booking_id' => $bookingId,
                    'name' => $name,
                    'amount' => $amount
                ];
            }
        }
        
        if (stripos($rowStr, 'BIAYA PAYMENT') !== false || stripos($rowStr, 'VIA TRANSFER') !== false) {
            $result['biaya_transfer'] = parseSummaryAmount($row[$valueCol] ?? '');
            continue;
        }
        
        if (stripos($rowStr, 'IURAN TAHUNAN') !== false) {
            $result['iuran_tahunan'] = parseSummaryAmount($row[$valueCol] ?? '');
            continue;
        }
        
        if (stripos($rowStr, 'BIAYA ADM') !== false) {
            $result['biaya_adm_bunga'] = parseSummaryAmount($row[$valueCol] ?? '');
            continue;
        }
    }
    
    return $result;
}

function findColumnIndex($headerRow, $possibleNames) {
    foreach ($headerRow as $index => $value) {
        $valueLower = strtolower(trim((string)$value));
        foreach ($possibleNames as $name) {
            if ($valueLower === strtolower($name)) {
                return $index;
            }
        }
    }
    return false;
}

// Load and process
$spreadsheet = IOFactory::load($file);
$sheetNames = $spreadsheet->getSheetNames();

$totalPayments = 0;
$totalRefunds = 0;
$totalRefundTransactions = 0;
$totalAdditionalFees = 0;

$allSheetFees = [];

foreach ($sheetNames as $sheetIndex => $originalSheetName) {
    $sheet = $spreadsheet->getSheet($sheetIndex);
    $rows = $sheet->toArray();
    
    $sheetNameForDb = parseSheetName($originalSheetName);
    
    echo "Sheet: $originalSheetName -> $sheetNameForDb\n";
    echo str_repeat("-", 60) . "\n";
    
    // Find header
    $headerRowIndex = -1;
    for ($i = 0; $i < min(10, count($rows)); $i++) {
        $rowString = implode('|', array_map('strval', $rows[$i]));
        if (stripos($rowString, 'Booking ID') !== false) {
            $headerRowIndex = $i;
            break;
        }
    }
    
    // Count payment transactions (before TOTAL PAYMENT)
    $paymentCount = 0;
    for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
        $rowString = implode('|', array_map('strval', $rows[$i]));
        if (stripos($rowString, 'TOTAL PAYMENT') !== false) {
            break;
        }
        $bookingId = trim((string)($rows[$i][2] ?? ''));
        if (!empty($bookingId) && preg_match('/\d/', $bookingId)) {
            $paymentCount++;
        }
    }
    
    // Extract summary
    $summary = extractSummarySection($rows);
    
    echo "  Payment transactions: $paymentCount\n";
    echo "  Refund transactions: " . count($summary['refund_transactions']) . "\n";
    
    if (!empty($summary['refund_transactions'])) {
        foreach ($summary['refund_transactions'] as $idx => $rt) {
            echo "    " . ($idx+1) . ". {$rt['booking_id']} - {$rt['name']}: Rp " . number_format($rt['amount'], 0, ',', '.') . "\n";
        }
    }
    
    echo "\n  Additional Fees:\n";
    echo "    - Biaya Adm & Bunga: Rp " . number_format($summary['biaya_adm_bunga'], 0, ',', '.') . "\n";
    echo "    - Biaya Transfer: Rp " . number_format($summary['biaya_transfer'], 0, ',', '.') . "\n";
    echo "    - Iuran Tahunan: Rp " . number_format($summary['iuran_tahunan'], 0, ',', '.') . "\n";
    
    $allSheetFees[$sheetNameForDb] = $summary;
    
    $totalPayments += $paymentCount;
    $totalRefunds += $summary['nominal_refund'];
    $totalRefundTransactions += count($summary['refund_transactions']);
    $totalAdditionalFees += $summary['biaya_adm_bunga'] + $summary['biaya_transfer'] + $summary['iuran_tahunan'];
    
    echo "\n";
}

echo "=== SUMMARY ===\n";
echo "Total Payment Transactions: $totalPayments\n";
echo "Total Refund Transactions: $totalRefundTransactions\n";
echo "Total Refund Amount: Rp " . number_format($totalRefunds, 0, ',', '.') . "\n";
echo "Total Additional Fees: Rp " . number_format($totalAdditionalFees, 0, ',', '.') . "\n";
echo "\nThis means CSV will have: " . ($totalPayments + $totalRefundTransactions) . " rows\n";
