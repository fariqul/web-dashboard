<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\ServiceFeeController;

// Test sample hotel names
$testCases = [
    'ibis Styles Bandung Grand Central Superior 1 Double Bed',
    'ibis Styles Bandung Grand Central Superior With 1 Double Bed',
    'Amaris Hotel Pakuan Bogor Smart Hollywood',
    'Louis Kienne Pemuda Deluxe Double Or Twin',
    'MARYAM PALACE HOTEL SOPPENG 205',
    'HARRIS Hotel Sentraland Semarang Harris',
    'ibis Styles Semarang Simpang Lima Superior Double Bed',
];

// Use reflection to access private method
$controller = new ServiceFeeController();
$method = new ReflectionMethod($controller, 'extractRoomTypeFromHotelName');
$method->setAccessible(true);

echo "=== Testing extractRoomTypeFromHotelName ===\n\n";

foreach ($testCases as $hotelName) {
    $result = $method->invoke($controller, $hotelName);
    echo "Input: $hotelName\n";
    echo "Hotel: {$result['hotel_name']}\n";
    echo "Room: " . ($result['room_type'] ?? 'NULL') . "\n";
    echo "Employee: " . ($result['employee_name'] ?? 'NULL') . "\n";
    echo "---\n";
}
