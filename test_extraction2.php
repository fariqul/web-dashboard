<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\ServiceFeeController;

$controller = new ServiceFeeController();
$method = new ReflectionMethod($controller, 'extractRoomTypeFromHotelName');
$method->setAccessible(true);

$tests = [
    'ibis Styles Semarang Simpang Lima Superior Double Bed',
    'Pare Beach Hotel Deluxe Single 2 Ibrahim',
    'Luminor Hotel Padjadjaran Bogor by WH Hollywood',
];

echo "=== Testing Updated Extraction ===\n\n";

foreach ($tests as $h) {
    $r = $method->invoke($controller, $h);
    echo "Input: $h\n";
    echo "Hotel: {$r['hotel_name']}\n";
    echo "Room: " . ($r['room_type'] ?? 'NULL') . "\n";
    echo "---\n";
}
