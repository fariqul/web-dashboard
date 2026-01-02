<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get all hotels with empty employee_name
$hotels = App\Models\ServiceFee::where('service_type', 'hotel')
    ->where(function($q) {
        $q->whereNull('employee_name')
          ->orWhere('employee_name', '');
    })
    ->get();

echo "=== Hotels with Empty Employee Name ===\n";
echo "Total: " . count($hotels) . "\n\n";

foreach ($hotels as $h) {
    echo "ID: {$h->id}\n";
    echo "  Hotel: {$h->hotel_name}\n";
    echo "  Room Type: " . ($h->room_type ?: '(empty)') . "\n";
    echo "  Employee: (empty)\n";
    echo "\n";
}
