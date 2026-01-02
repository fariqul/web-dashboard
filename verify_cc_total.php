<?php
// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CCTransaction;
use App\Models\SheetAdditionalFee;

echo "=== Verifikasi CC Card Data ===\n\n";

// Total dari database
$grossPayment = CCTransaction::where('transaction_type', 'payment')->sum('payment_amount');
$totalRefund = CCTransaction::where('transaction_type', 'refund')->sum('payment_amount');

$totalAdminInterest = 0;
$fees = SheetAdditionalFee::all();
foreach ($fees as $fee) {
    $totalAdminInterest += $fee->biaya_adm_bunga + $fee->biaya_transfer + $fee->iuran_tahunan;
}

$netPayment = $grossPayment - $totalRefund;
$grandTotal = $netPayment + $totalAdminInterest;

echo "=== DARI DATABASE ===\n";
echo "Gross Payment:       Rp " . number_format($grossPayment, 0, ',', '.') . "\n";
echo "Total Refund:        Rp " . number_format($totalRefund, 0, ',', '.') . "\n";
echo "Net Payment:         Rp " . number_format($netPayment, 0, ',', '.') . "\n";
echo "Total Additional Fees: Rp " . number_format($totalAdminInterest, 0, ',', '.') . "\n";
echo str_repeat("-", 50) . "\n";
echo "Grand Total (Payment - Refund + Fees): Rp " . number_format($grandTotal, 0, ',', '.') . "\n";

echo "\n=== DARI EXCEL (Referensi) ===\n";
echo "Grand Total di Excel: Rp 1.548.732.172\n";

echo "\n=== STATUS ===\n";
if ($grandTotal == 1548732172) {
    echo "✅ MATCH! Total di database sama dengan total di Excel!\n";
} else {
    $diff = $grandTotal - 1548732172;
    echo "❌ NOT MATCH! Selisih: Rp " . number_format($diff, 0, ',', '.') . "\n";
}

echo "\n=== DETAIL PER SHEET ===\n";
$sheets = CCTransaction::select('sheet')
    ->selectRaw("SUM(CASE WHEN transaction_type = 'payment' THEN payment_amount ELSE 0 END) as payment")
    ->selectRaw("SUM(CASE WHEN transaction_type = 'refund' THEN payment_amount ELSE 0 END) as refund")
    ->groupBy('sheet')
    ->orderBy('sheet')
    ->get();

foreach ($sheets as $s) {
    $fee = SheetAdditionalFee::where('sheet_name', $s->sheet)->first();
    $additionalFees = $fee ? ($fee->biaya_adm_bunga + $fee->biaya_transfer + $fee->iuran_tahunan) : 0;
    $net = $s->payment - $s->refund + $additionalFees;
    
    echo "\n{$s->sheet}:\n";
    echo "  Payment:        Rp " . number_format($s->payment, 0, ',', '.') . "\n";
    echo "  Refund:         Rp " . number_format($s->refund, 0, ',', '.') . "\n";
    echo "  Additional Fee: Rp " . number_format($additionalFees, 0, ',', '.') . "\n";
    echo "  Net Total:      Rp " . number_format($net, 0, ',', '.') . "\n";
}
