<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'd:/Bu Intan/data/Rekapitulasi Pembayaran CC Juli -September 2025.xlsx';

echo "=== CC Card Excel Summary Analysis ===\n\n";

$spreadsheet = IOFactory::load($file);
$sheetNames = $spreadsheet->getSheetNames();

foreach ($sheetNames as $index => $sheetName) {
    $sheet = $spreadsheet->getSheet($index);
    $rows = $sheet->toArray();
    
    echo "Sheet: $sheetName\n";
    echo str_repeat("-", 50) . "\n";
    
    $totalPayment = 0;
    $nominalRefund = 0;
    $biayaTransfer = 0;
    $iuranTahunan = 0;
    $biayaAdmBunga = 0;
    $grandTotal = 0;
    $refundTransactions = [];
    
    // Find summary section (look for "TOTAL PAYMENT" row)
    $inRefundSection = false;
    
    for ($i = 0; $i < count($rows); $i++) {
        $row = $rows[$i];
        $firstCol = trim((string)($row[1] ?? ''));
        $secondCol = trim((string)($row[2] ?? ''));
        $valueCol = isset($row[8]) ? $row[8] : (isset($row[7]) ? $row[7] : null);
        
        // Check for TOTAL PAYMENT
        if (stripos($firstCol, 'TOTAL PAYMENT') !== false || stripos($secondCol, 'TOTAL PAYMENT') !== false) {
            $totalPayment = $valueCol;
            $inRefundSection = true;
            continue;
        }
        
        // Check for NOMINAL REFUND
        if (stripos($firstCol, 'NOMINAL REFUND') !== false || stripos($secondCol, 'NOMINAL REFUND') !== false) {
            $nominalRefund = $valueCol;
            $inRefundSection = false;
            continue;
        }
        
        // Capture refund transactions (between TOTAL PAYMENT and NOMINAL REFUND)
        if ($inRefundSection && is_numeric($firstCol) && !empty($row[2])) {
            $refundTransactions[] = [
                'booking_id' => $row[2],
                'name' => $row[3] ?? '',
                'amount' => $valueCol
            ];
        }
        
        // Check for BIAYA PAYMENT VIA TRANSFER
        if (stripos($firstCol, 'BIAYA PAYMENT') !== false || stripos($secondCol, 'BIAYA PAYMENT') !== false ||
            stripos($firstCol, 'BIAYA TRANSFER') !== false || stripos($secondCol, 'BIAYA TRANSFER') !== false) {
            $biayaTransfer = is_numeric($valueCol) ? $valueCol : 0;
            continue;
        }
        
        // Check for IURAN TAHUNAN
        if (stripos($firstCol, 'IURAN TAHUNAN') !== false || stripos($secondCol, 'IURAN TAHUNAN') !== false) {
            $iuranTahunan = is_numeric($valueCol) ? $valueCol : 0;
            continue;
        }
        
        // Check for BIAYA ADM & BUNGA
        if (stripos($firstCol, 'BIAYA ADM') !== false || stripos($secondCol, 'BIAYA ADM') !== false) {
            $biayaAdmBunga = is_numeric($valueCol) ? $valueCol : 0;
            continue;
        }
        
        // Check for GRAND TOTAL
        if (stripos($firstCol, 'TOTAL (A-B') !== false || stripos($firstCol, 'TOTAL(A-B') !== false ||
            stripos($secondCol, 'TOTAL (A-B') !== false) {
            $grandTotal = $valueCol;
            continue;
        }
    }
    
    echo "Total Payment (A):        " . number_format((float)$totalPayment) . "\n";
    echo "Nominal Refund (B):       " . number_format((float)$nominalRefund) . "\n";
    echo "Biaya Transfer (C):       " . number_format((float)$biayaTransfer) . "\n";
    echo "Iuran Tahunan (D):        " . number_format((float)$iuranTahunan) . "\n";
    echo "Biaya Adm & Bunga (E):    " . number_format((float)$biayaAdmBunga) . "\n";
    echo "Grand Total (Excel):      " . number_format((float)$grandTotal) . "\n";
    
    // Calculate expected total
    $calculated = (float)$totalPayment - (float)$nominalRefund + (float)$biayaTransfer + (float)$iuranTahunan + (float)$biayaAdmBunga;
    echo "Calculated (A-B+C+D+E):   " . number_format($calculated) . "\n";
    
    if (!empty($refundTransactions)) {
        echo "\nRefund Transactions:\n";
        foreach ($refundTransactions as $rt) {
            echo "  - Booking: {$rt['booking_id']}, Name: {$rt['name']}, Amount: " . number_format((float)$rt['amount']) . "\n";
        }
    }
    
    echo "\n";
}
