<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ServiceFee;

// Get unfixed hotels
$unfixed = ServiceFee::where('service_type', 'hotel')
    ->where(function($q) {
        $q->whereNull('room_type')
          ->orWhere('room_type', '')
          ->orWhere('room_type', 'N/A');
    })
    ->get();

echo "=== Remaining Unfixed Hotels ===\n";
echo "Total: " . $unfixed->count() . "\n\n";

// Manual fixes based on patterns
$manualFixes = [];

foreach ($unfixed as $hotel) {
    $originalName = $hotel->hotel_name;
    $room = null;
    $newHotel = null;
    
    // "Pare Beach Hotel Deluxe Single 2 Ibrahim" -> Deluxe Single
    if (strpos($originalName, 'Pare Beach Hotel Deluxe Single') !== false) {
        $room = 'Deluxe Single';
        $newHotel = 'Pare Beach Hotel';
    }
    // "Luminor Hotel Padjadjaran Bogor by WH Hollywood"
    elseif (strpos($originalName, 'Luminor Hotel Padjadjaran Bogor by WH Hollywood') !== false) {
        $room = 'Hollywood';
        $newHotel = 'Luminor Hotel Padjadjaran Bogor by WH';
    }
    // "ibis Styles Semarang Simpang Lima Superior Double Bed"
    elseif (strpos($originalName, 'ibis Styles Semarang Simpang Lima Superior Double Bed') !== false) {
        $room = 'Superior Double Bed';
        $newHotel = 'ibis Styles Semarang Simpang Lima';
    }
    // Fix room 205/101 to Room 205/Room 101
    elseif (preg_match('/\s(\d{3})$/', $originalName, $m)) {
        $room = 'Room ' . $m[1];
        $newHotel = preg_replace('/\s\d{3}$/', '', $originalName);
    }
    // Fix "Family Ro" to "Family Room"
    elseif (strpos($originalName, 'Family Ro') !== false) {
        $room = 'Family Room';
        $newHotel = str_replace('Family Ro', '', $originalName);
    }
    
    if ($room) {
        $hotel->room_type = $room;
        if ($newHotel) {
            $hotel->hotel_name = trim($newHotel);
        }
        $hotel->save();
        echo "Fixed ID {$hotel->id}: Room = '$room'\n";
    } else {
        echo "STILL UNFIXED ID {$hotel->id}: $originalName\n";
    }
}

// Final count
$remaining = ServiceFee::where('service_type', 'hotel')
    ->where(function($q) {
        $q->whereNull('room_type')
          ->orWhere('room_type', '')
          ->orWhere('room_type', 'N/A');
    })
    ->count();

echo "\n=== Final Count: $remaining empty room_types ===\n";
