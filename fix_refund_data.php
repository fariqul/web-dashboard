<?php
/**
 * Script untuk memperbaiki data refund yang tidak memiliki info trip
 * dengan mengambil dari payment yang sesuai atau dari CSV
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CCTransaction;

echo "=== Fix Refund Trip Data ===\n\n";

// Cari semua refund yang tidak punya trip destination
$refundsWithoutTrip = CCTransaction::where('transaction_type', 'refund')
    ->where(function($q) {
        $q->whereNull('trip_destination_full')
          ->orWhere('trip_destination_full', '');
    })
    ->get();

echo "Found " . $refundsWithoutTrip->count() . " refunds without trip data\n\n";

$fixed = 0;
$notFixed = 0;

foreach ($refundsWithoutTrip as $refund) {
    // Extract original booking ID (remove -REFUND suffix)
    $originalBookingId = preg_replace('/-REFUND(-\d+)?$/', '', $refund->booking_id);
    
    // Try to find corresponding payment
    $payment = CCTransaction::where('booking_id', $originalBookingId)
        ->where('transaction_type', 'payment')
        ->first();
    
    if ($payment && !empty($payment->trip_destination_full)) {
        // Copy trip data from payment
        $refund->update([
            'personel_number' => $payment->personel_number,
            'trip_number' => $payment->trip_number,
            'origin' => $payment->origin,
            'destination' => $payment->destination,
            'trip_destination_full' => $payment->trip_destination_full,
            'departure_date' => $payment->departure_date,
            'return_date' => $payment->return_date,
            'duration_days' => $payment->duration_days,
        ]);
        
        echo "✅ Fixed: {$refund->booking_id} -> {$payment->trip_destination_full}\n";
        $fixed++;
    } else {
        echo "❌ Not fixed: {$refund->booking_id} (no matching payment found)\n";
        $notFixed++;
    }
}

echo "\n=== Summary ===\n";
echo "Fixed: $fixed\n";
echo "Not fixed: $notFixed\n";
echo "Done!\n";
