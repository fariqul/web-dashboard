<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ServiceFee;

// Lusiana Hotel - Sorowako Double - 2 People
ServiceFee::where('id', 6753)->update([
    'room_type' => 'Sorowako Double',
    'hotel_name' => 'Lusiana Hotel & Restauran Soroako'
]);
echo "Fixed 6753: Sorowako Double\n";

// Grand Dafam Braga - Condotel 2 Bedroom
ServiceFee::where('id', 6781)->update([
    'room_type' => 'Condotel 2 Bedroom',
    'hotel_name' => 'Grand Dafam Braga Bandung'
]);
echo "Fixed 6781: Condotel 2 Bedroom\n";

// Best Deal 2BR Apartment
ServiceFee::where('id', 7019)->update([
    'room_type' => '2BR Apartment',
    'hotel_name' => 'Best Deal And Homey 2Br At Bale Hinggil Apartment'
]);
echo "Fixed 7019: 2BR Apartment\n";

// all seasons Jakarta - Superior With 1 New (truncated)
ServiceFee::where('id', 7031)->update([
    'room_type' => 'Superior',
    'hotel_name' => 'all seasons Jakarta Thamrin'
]);
echo "Fixed 7031: Superior\n";

// Final check
$remaining = ServiceFee::where('service_type', 'hotel')
    ->where(function($q) {
        $q->whereNull('room_type')
          ->orWhere('room_type', '')
          ->orWhere('room_type', 'N/A');
    })
    ->count();

echo "\nFinal count: $remaining empty room_types\n";
