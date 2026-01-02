<?php

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\CCTransactionController;
use App\Models\CCTransaction;
use App\Models\SheetAdditionalFee;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

echo "=== Testing CC Card Import ===\n\n";

// First clear existing data
echo "Clearing existing data...\n";
CCTransaction::truncate();
SheetAdditionalFee::truncate();

$file = 'd:/Bu Intan/data/Rekapitulasi Pembayaran CC Juli -September 2025.xlsx';

// Simulate file upload
$uploadedFile = new UploadedFile(
    $file,
    'Rekapitulasi Pembayaran CC Juli -September 2025.xlsx',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    null,
    true
);

// Create request
$request = Request::create('/cc-card/import', 'POST', [
    'update_existing' => false
]);
$request->files->set('csv_file', $uploadedFile);

echo "Calling import...\n";

$controller = new CCTransactionController();
$response = $controller->import($request);

echo "Import completed!\n\n";

// Show results
echo "=== RESULTS ===\n";

// Count by sheet
$sheets = CCTransaction::select('sheet')
    ->selectRaw('COUNT(*) as total')
    ->selectRaw("SUM(CASE WHEN transaction_type = 'payment' THEN 1 ELSE 0 END) as payments")
    ->selectRaw("SUM(CASE WHEN transaction_type = 'refund' THEN 1 ELSE 0 END) as refunds")
    ->selectRaw("SUM(CASE WHEN transaction_type = 'payment' THEN payment_amount ELSE 0 END) as payment_total")
    ->selectRaw("SUM(CASE WHEN transaction_type = 'refund' THEN payment_amount ELSE 0 END) as refund_total")
    ->groupBy('sheet')
    ->orderBy('sheet')
    ->get();

foreach ($sheets as $s) {
    echo "\nSheet: {$s->sheet}\n";
    echo "  Payments: {$s->payments} (Rp " . number_format($s->payment_total, 0, ',', '.') . ")\n";
    echo "  Refunds: {$s->refunds} (Rp " . number_format($s->refund_total, 0, ',', '.') . ")\n";
    echo "  Net: Rp " . number_format($s->payment_total - $s->refund_total, 0, ',', '.') . "\n";
}

// Show additional fees
echo "\n=== ADDITIONAL FEES ===\n";
$fees = SheetAdditionalFee::all();
foreach ($fees as $f) {
    echo "\nSheet: {$f->sheet_name}\n";
    echo "  Biaya Adm & Bunga: Rp " . number_format($f->biaya_adm_bunga, 0, ',', '.') . "\n";
    echo "  Biaya Transfer: Rp " . number_format($f->biaya_transfer, 0, ',', '.') . "\n";
    echo "  Iuran Tahunan: Rp " . number_format($f->iuran_tahunan, 0, ',', '.') . "\n";
}

// Grand total
$grandPayment = CCTransaction::where('transaction_type', 'payment')->sum('payment_amount');
$grandRefund = CCTransaction::where('transaction_type', 'refund')->sum('payment_amount');
$grandFees = SheetAdditionalFee::sum('biaya_adm_bunga') + SheetAdditionalFee::sum('biaya_transfer') + SheetAdditionalFee::sum('iuran_tahunan');

echo "\n=== GRAND TOTAL ===\n";
echo "Total Payment: Rp " . number_format($grandPayment, 0, ',', '.') . "\n";
echo "Total Refund: Rp " . number_format($grandRefund, 0, ',', '.') . "\n";
echo "Total Additional Fees: Rp " . number_format($grandFees, 0, ',', '.') . "\n";
echo "Net Total (Payment - Refund + Fees): Rp " . number_format($grandPayment - $grandRefund + $grandFees, 0, ',', '.') . "\n";

// Excel grand total for comparison
echo "\n=== EXCEL GRAND TOTAL (for comparison) ===\n";
echo "Grand Total dari Excel: Rp 1.548.732.172\n";
