<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ServiceFee;

// Manual fixes for remaining 6 records
$fixes = [
    686 => ['hotel' => 'Pare Beach Hotel', 'room' => 'Deluxe Single', 'employee' => 'IBRAHIM'],
    799 => ['hotel' => 'Luminor Hotel Padjadjaran Bogor by WH', 'room' => 'Hollywood'],
    792 => ['hotel' => 'BIGLAND Hotel & Convention Sentul', 'room' => 'Family Room'],  // Fix truncated
    1104 => ['hotel' => 'ibis Styles Semarang Simpang Lima', 'room' => 'Superior Double Bed'],
    1105 => ['hotel' => 'ibis Styles Semarang Simpang Lima', 'room' => 'Superior Double Bed'],
    1106 => ['hotel' => 'ibis Styles Semarang Simpang Lima', 'room' => 'Superior Double Bed'],
    1107 => ['hotel' => 'ibis Styles Semarang Simpang Lima', 'room' => 'Superior Double Bed'],
    729 => ['room' => 'Room 205'],  // Fix room number
    730 => ['room' => 'Room 205'],
    731 => ['room' => 'Room 101'],
    732 => ['room' => 'Room 101'],
    733 => ['room' => 'Room 101'],
];

echo "=== Manual Fixes ===\n";

foreach ($fixes as $id => $fix) {
    $hotel = ServiceFee::find($id);
    if ($hotel) {
        if (isset($fix['hotel'])) {
            $hotel->hotel_name = $fix['hotel'];
        }
        if (isset($fix['room'])) {
            $hotel->room_type = $fix['room'];
        }
        if (isset($fix['employee'])) {
            $hotel->employee_name = $fix['employee'];
        }
        $hotel->save();
        echo "Fixed ID $id: room = '{$fix['room']}'" . (isset($fix['hotel']) ? ", hotel = '{$fix['hotel']}'" : "") . "\n";
    } else {
        echo "ID $id not found\n";
    }
}

// Verify
$remaining = ServiceFee::where('service_type', 'hotel')
    ->where(function($q) {
        $q->whereNull('room_type')
          ->orWhere('room_type', '')
          ->orWhere('room_type', 'N/A');
    })
    ->count();

echo "\n=== Remaining empty room_type: $remaining ===\n";
