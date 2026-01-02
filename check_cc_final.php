<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'd:/Bu Intan/data/Rekapitulasi Pembayaran CC Juli -September 2025.xlsx';

echo "=== CC Card Excel Complete Summary ===\n\n";

function parseAmount($val) {
    if (empty($val) || $val === '-' || trim($val) === '-') return 0;
    // Remove spaces, commas, and other non-numeric chars except dot
    $cleaned = preg_replace('/[^\d]/', '', trim((string)$val));
    return (float)$cleaned;
}

$spreadsheet = IOFactory::load($file);
$sheetNames = $spreadsheet->getSheetNames();

$allSummary = [];

foreach ($sheetNames as $index => $sheetName) {
    $sheet = $spreadsheet->getSheet($index);
    $rows = $sheet->toArray();
    
    echo "Sheet: $sheetName\n";
    echo str_repeat("=", 60) . "\n";
    
    $data = [
        'sheet_name' => $sheetName,
        'total_payment' => 0,
        'refund_transactions' => [],
        'nominal_refund' => 0,
        'biaya_transfer' => 0,
        'iuran_tahunan' => 0,
        'biaya_adm_bunga' => 0,
        'grand_total' => 0
    ];
    
    // Payment value column is column 8 (index 8)
    $valueCol = 8;
    $inRefundSection = false;
    
    for ($i = 0; $i < count($rows); $i++) {
        $row = $rows[$i];
        $rowStr = implode('|', array_map('strval', $row));
        
        // Check for TOTAL PAYMENT
        if (stripos($rowStr, 'TOTAL PAYMENT') !== false) {
            $data['total_payment'] = parseAmount($row[$valueCol] ?? '');
            $inRefundSection = true;
            continue;
        }
        
        // Check for NOMINAL REFUND
        if (stripos($rowStr, 'NOMINAL REFUND') !== false) {
            $data['nominal_refund'] = parseAmount($row[$valueCol] ?? '');
            $inRefundSection = false;
            continue;
        }
        
        // Capture refund transactions (between TOTAL PAYMENT and NOMINAL REFUND)
        if ($inRefundSection) {
            $firstCol = trim((string)($row[1] ?? ''));
            $bookingId = trim((string)($row[2] ?? ''));
            $name = trim((string)($row[3] ?? ''));
            $amount = parseAmount($row[$valueCol] ?? '');
            
            if (is_numeric($firstCol) && !empty($bookingId) && preg_match('/\d/', $bookingId) && $amount > 0) {
                $data['refund_transactions'][] = [
                    'booking_id' => $bookingId,
                    'name' => $name,
                    'amount' => $amount
                ];
            }
        }
        
        // Check for BIAYA PAYMENT VIA TRANSFER
        if (stripos($rowStr, 'BIAYA PAYMENT') !== false || stripos($rowStr, 'VIA TRANSFER') !== false) {
            $data['biaya_transfer'] = parseAmount($row[$valueCol] ?? '');
            continue;
        }
        
        // Check for IURAN TAHUNAN
        if (stripos($rowStr, 'IURAN TAHUNAN') !== false) {
            $data['iuran_tahunan'] = parseAmount($row[$valueCol] ?? '');
            continue;
        }
        
        // Check for BIAYA ADM & BUNGA
        if (stripos($rowStr, 'BIAYA ADM') !== false) {
            $data['biaya_adm_bunga'] = parseAmount($row[$valueCol] ?? '');
            continue;
        }
        
        // Check for GRAND TOTAL
        if (stripos($rowStr, 'TOTAL (A-B') !== false || stripos($rowStr, 'TOTAL(A-B') !== false) {
            $data['grand_total'] = parseAmount($row[$valueCol] ?? '');
            continue;
        }
    }
    
    $allSummary[] = $data;
    
    echo "A. Total Payment:         Rp " . number_format($data['total_payment'], 0, ',', '.') . "\n";
    
    if (!empty($data['refund_transactions'])) {
        echo "\n   Refund Transactions:\n";
        $totalRefundFromList = 0;
        foreach ($data['refund_transactions'] as $idx => $rt) {
            echo "   " . ($idx+1) . ". {$rt['booking_id']} - {$rt['name']}: Rp " . number_format($rt['amount'], 0, ',', '.') . "\n";
            $totalRefundFromList += $rt['amount'];
        }
    }
    
    echo "\nB. Nominal Refund:        Rp " . number_format($data['nominal_refund'], 0, ',', '.') . "\n";
    echo "C. Biaya Transfer:        Rp " . number_format($data['biaya_transfer'], 0, ',', '.') . "\n";
    echo "D. Iuran Tahunan:         Rp " . number_format($data['iuran_tahunan'], 0, ',', '.') . "\n";
    echo "E. Biaya Adm & Bunga:     Rp " . number_format($data['biaya_adm_bunga'], 0, ',', '.') . "\n";
    echo "---------------------------------------------\n";
    echo "   Grand Total (Excel):   Rp " . number_format($data['grand_total'], 0, ',', '.') . "\n";
    
    // Calculate: A - B + C + D + E
    $calculated = $data['total_payment'] - $data['nominal_refund'] + $data['biaya_transfer'] + $data['iuran_tahunan'] + $data['biaya_adm_bunga'];
    echo "   Calculated (A-B+C+D+E):Rp " . number_format($calculated, 0, ',', '.') . "\n";
    
    echo "\n";
}

// Grand summary
echo "=== GRAND SUMMARY ALL SHEETS ===\n";
$grandTotalPayment = 0;
$grandTotalRefund = 0;
$grandTotalBiayaAdm = 0;
$grandTotal = 0;

foreach ($allSummary as $s) {
    $grandTotalPayment += $s['total_payment'];
    $grandTotalRefund += $s['nominal_refund'];
    $grandTotalBiayaAdm += $s['biaya_adm_bunga'] + $s['biaya_transfer'] + $s['iuran_tahunan'];
    $grandTotal += $s['grand_total'];
}

echo "Total Payment All:    Rp " . number_format($grandTotalPayment, 0, ',', '.') . "\n";
echo "Total Refund All:     Rp " . number_format($grandTotalRefund, 0, ',', '.') . "\n";
echo "Total Biaya Adm All:  Rp " . number_format($grandTotalBiayaAdm, 0, ',', '.') . "\n";
echo "Grand Total All:      Rp " . number_format($grandTotal, 0, ',', '.') . "\n";
