<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$year = '2025';

echo "=== Fixed Year Check for 2025 ===\n";
$query = \App\Models\CCTransaction::query();
$query->where(function($q) use ($year) {
    $q->where(function($q2) use ($year) {
        // Has departure_date - check both formats
        $q2->whereNotNull('departure_date')
           ->where('departure_date', '!=', '')
           ->where(function($q3) use ($year) {
               $q3->whereRaw("SUBSTR(departure_date, -4) = ?", [$year]) // M/D/YYYY format
                  ->orWhereRaw("SUBSTR(departure_date, 1, 4) = ?", [$year]); // YYYY-MM-DD format
           });
    })->orWhere(function($q2) use ($year) {
        // No departure_date (like refunds) - check year from sheet name
        $q2->where(function($q3) {
            $q3->whereNull('departure_date')
               ->orWhere('departure_date', '');
        })->whereRaw("sheet LIKE ?", ['%' . $year . '%']);
    });
});

$transactions = $query->get();
$paymentTransactions = $transactions->where('transaction_type', 'payment');
$refundTransactions = $transactions->where('transaction_type', 'refund');

echo "Total transactions: " . $transactions->count() . "\n";
echo "Payment transactions: " . $paymentTransactions->count() . "\n";
echo "Refund transactions: " . $refundTransactions->count() . "\n";
echo "\n";
echo "Gross Payment: " . number_format($paymentTransactions->sum('payment_amount')) . "\n";
echo "Total Refund: " . number_format($refundTransactions->sum('payment_amount')) . "\n";
