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
    echo str_repeat("=", 60) . "\n";
    
    $totalPayment = 0;
    $nominalRefund = 0;
    $biayaTransfer = 0;
    $iuranTahunan = 0;
    $biayaAdmBunga = 0;
    $grandTotal = 0;
    $refundTransactions = [];
    
    // Find summary section by looking at each row
    $inRefundSection = false;
    
    for ($i = 0; $i < count($rows); $i++) {
        $row = $rows[$i];
        
        // Create a string representation of the row to search
        $rowStr = implode('|', array_map('strval', $row));
        
        // Find the value column (usually column 7 or 8, index 7 or 8)
        $paymentColIndex = 8; // Column I (index 8 in 0-based)
        for ($c = 7; $c <= 10; $c++) {
            if (isset($row[$c]) && is_numeric($row[$c]) && $row[$c] > 1000) {
                $paymentColIndex = $c;
                break;
            }
        }
        
        // Check for TOTAL PAYMENT
        if (stripos($rowStr, 'TOTAL PAYMENT') !== false) {
            $totalPayment = isset($row[$paymentColIndex]) ? (float)$row[$paymentColIndex] : 0;
            $inRefundSection = true;
            continue;
        }
        
        // Check for NOMINAL REFUND
        if (stripos($rowStr, 'NOMINAL REFUND') !== false) {
            $nominalRefund = isset($row[$paymentColIndex]) ? (float)$row[$paymentColIndex] : 0;
            $inRefundSection = false;
            continue;
        }
        
        // Capture refund transactions (between TOTAL PAYMENT and NOMINAL REFUND)
        if ($inRefundSection) {
            $firstCol = trim((string)($row[1] ?? ''));
            $bookingId = trim((string)($row[2] ?? ''));
            $name = trim((string)($row[3] ?? ''));
            $amount = isset($row[$paymentColIndex]) ? (float)$row[$paymentColIndex] : 0;
            
            if (is_numeric($firstCol) && !empty($bookingId) && preg_match('/\d/', $bookingId)) {
                $refundTransactions[] = [
                    'booking_id' => $bookingId,
                    'name' => $name,
                    'amount' => $amount
                ];
            }
        }
        
        // Check for BIAYA PAYMENT VIA TRANSFER
        if (stripos($rowStr, 'BIAYA PAYMENT') !== false || stripos($rowStr, 'VIA TRANSFER') !== false) {
            $val = isset($row[$paymentColIndex]) ? $row[$paymentColIndex] : 0;
            $biayaTransfer = is_numeric($val) ? (float)$val : 0;
            continue;
        }
        
        // Check for IURAN TAHUNAN
        if (stripos($rowStr, 'IURAN TAHUNAN') !== false) {
            $val = isset($row[$paymentColIndex]) ? $row[$paymentColIndex] : 0;
            $iuranTahunan = is_numeric($val) ? (float)$val : 0;
            continue;
        }
        
        // Check for BIAYA ADM & BUNGA
        if (stripos($rowStr, 'BIAYA ADM') !== false || stripos($rowStr, 'ADM & BUNGA') !== false) {
            $val = isset($row[$paymentColIndex]) ? $row[$paymentColIndex] : 0;
            $biayaAdmBunga = is_numeric($val) ? (float)$val : 0;
            continue;
        }
        
        // Check for GRAND TOTAL
        if (stripos($rowStr, 'TOTAL (A-B') !== false || stripos($rowStr, 'TOTAL(A-B') !== false) {
            $grandTotal = isset($row[$paymentColIndex]) ? (float)$row[$paymentColIndex] : 0;
            continue;
        }
    }
    
    echo "Total Payment (A):        Rp " . number_format($totalPayment, 0, ',', '.') . "\n";
    
    if (!empty($refundTransactions)) {
        echo "\nRefund Transactions (before B):\n";
        $totalRefundFromList = 0;
        foreach ($refundTransactions as $idx => $rt) {
            echo "  " . ($idx+1) . ". Booking: {$rt['booking_id']}\n";
            echo "     Name: {$rt['name']}\n";
            echo "     Amount: Rp " . number_format($rt['amount'], 0, ',', '.') . "\n";
            $totalRefundFromList += $rt['amount'];
        }
        echo "  --------------------------------\n";
        echo "  Total from list: Rp " . number_format($totalRefundFromList, 0, ',', '.') . "\n";
    }
    
    echo "\nNominal Refund (B):       Rp " . number_format($nominalRefund, 0, ',', '.') . "\n";
    echo "Biaya Transfer (C):       Rp " . number_format($biayaTransfer, 0, ',', '.') . "\n";
    echo "Iuran Tahunan (D):        Rp " . number_format($iuranTahunan, 0, ',', '.') . "\n";
    echo "Biaya Adm & Bunga (E):    Rp " . number_format($biayaAdmBunga, 0, ',', '.') . "\n";
    echo "Grand Total (Excel):      Rp " . number_format($grandTotal, 0, ',', '.') . "\n";
    
    // Calculate expected total: A - B + C + D + E
    $calculated = $totalPayment - $nominalRefund + $biayaTransfer + $iuranTahunan + $biayaAdmBunga;
    echo "Calculated (A-B+C+D+E):   Rp " . number_format($calculated, 0, ',', '.') . "\n";
    
    echo "\n";
}
