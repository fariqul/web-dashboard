<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get all hotels with empty room_type
$hotels = App\Models\ServiceFee::where('service_type', 'hotel')
    ->where(function($q) {
        $q->whereNull('room_type')
          ->orWhere('room_type', '')
          ->orWhere('room_type', 'N/A');
    })
    ->get();

echo "=== Hotels with Empty Room Type ===\n";
echo "Total: " . count($hotels) . "\n\n";

foreach ($hotels as $h) {
    echo "ID: {$h->id}\n";
    echo "  Hotel: {$h->hotel_name}\n";
    echo "  Room Type: " . ($h->room_type ?: '(empty)') . "\n";
    echo "  Employee: " . ($h->employee_name ?: '(empty)') . "\n";
    echo "\n";
}
