<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CCTransaction;

echo "Latest 5 CC Transactions:\n";
echo "========================\n\n";

$latest = CCTransaction::latest()->take(5)->get();

foreach ($latest as $t) {
    echo "Booking ID: {$t->booking_id}\n";
    echo "Name: {$t->employee_name}\n";
    echo "Payment: Rp " . number_format($t->payment_amount, 0, ',', '.') . "\n";
    echo "Sheet: {$t->sheet}\n";
    echo "---\n";
}

echo "\nTotal CC Transactions: " . CCTransaction::count() . "\n";
