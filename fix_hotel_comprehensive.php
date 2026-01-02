<?php
/**
 * Comprehensive fix for all hotel data after import
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Comprehensive Hotel Data Fix ===\n\n";

function extractHotelRoomEmployee($hotelName) {
    $originalHotel = $hotelName;
    $roomType = null;
    $employeeName = null;
    
    // Step 1: Extract employee name patterns
    
    // Pattern: lowercase name at end "mohamad sulthan", "Yusdi Yusdi"
    if (preg_match('/\s+([a-z]+\s+[a-z]+)$/i', $hotelName, $m)) {
        $name = trim($m[1]);
        // Check if both words start with same letter (like Yusdi Yusdi) or lowercase
        if (preg_match('/^[a-z]/', $name) || preg_match('/^(\w+)\s+\1$/i', $name)) {
            $employeeName = $name;
            $hotelName = trim(substr($hotelName, 0, -strlen($m[0])));
        }
    }
    
    // Pattern: Single letter + name like "1 A" at end followed by nothing  
    if (preg_match('/\s+(\d+)\s+([A-Z])$/u', $hotelName, $m)) {
        // Remove the trailing letter, keep the number
        $hotelName = trim(substr($hotelName, 0, -strlen($m[0]))) . ' ' . $m[1];
    }
    
    // Pattern: "2 I" at end (number + single letter)
    if (preg_match('/\s+(\d+)\s+I$/u', $hotelName, $m)) {
        $hotelName = trim(substr($hotelName, 0, -strlen($m[0]))) . ' ' . $m[1];
    }
    
    // Step 2: Extract room type
    
    // Pattern: "Superior 1 Double Bed", "Deluxe 1 King Bed", "Standard 1 Queen Bed"
    if (preg_match('/\b(Superior|Deluxe|Standard|Executive|Privilege|Premium)\s+(\d+\s+(?:Double|King|Queen|Twin|Single)\s+Bed(?:s)?)\s*$/i', $hotelName, $m)) {
        $roomType = trim($m[0]);
        $hotelName = trim(substr($hotelName, 0, -strlen($m[0])));
    }
    // Pattern: "Superior With 1 King - Size Bed", "Superior With 2 Single Beds"
    elseif (preg_match('/\b(Superior|Deluxe|Standard)\s+With\s+(\d+\s+(?:Double|King|Queen|Twin|Single)(?:\s*-\s*Size)?\s+Beds?)\s*$/i', $hotelName, $m)) {
        $roomType = trim($m[0]);
        $hotelName = trim(substr($hotelName, 0, -strlen($m[0])));
    }
    // Pattern: "Deluxe 1 King Bed With Sofa Bed"
    elseif (preg_match('/\b(Deluxe|Superior|Standard)\s+\d+\s+\w+\s+Bed\s+With\s+Sofa\s+Bed\s*$/i', $hotelName, $m)) {
        $roomType = trim($m[0]);
        $hotelName = trim(substr($hotelName, 0, -strlen($m[0])));
    }
    // Pattern: "1 King Bed", "2 Twin Beds" at end
    elseif (preg_match('/\b(\d+\s+(?:King|Queen|Twin|Double|Single)\s+Beds?)\s*$/i', $hotelName, $m)) {
        $roomType = trim($m[0]);
        $hotelName = trim(substr($hotelName, 0, -strlen($m[0])));
    }
    // Pattern: "Privilege 1 King Bed"
    elseif (preg_match('/\b(Privilege|Executive|Club)\s+\d+\s+\w+\s+Bed\s*$/i', $hotelName, $m)) {
        $roomType = trim($m[0]);
        $hotelName = trim(substr($hotelName, 0, -strlen($m[0])));
    }
    // Pattern: "Standard 1 King - Size Bed"
    elseif (preg_match('/\b(Standard|Superior|Deluxe)\s+\d+\s+\w+\s*-\s*Size\s+Bed\s*$/i', $hotelName, $m)) {
        $roomType = trim($m[0]);
        $hotelName = trim(substr($hotelName, 0, -strlen($m[0])));
    }
    // Pattern: "Condotel 2 Bedroom"
    elseif (preg_match('/\b(Condotel)\s+\d+\s+Bedroom\s*$/i', $hotelName, $m)) {
        $roomType = trim($m[0]);
        $hotelName = trim(substr($hotelName, 0, -strlen($m[0])));
    }
    // Pattern: "Superior With Double Bed" (no number)
    elseif (preg_match('/\b(Superior|Deluxe|Standard)\s+With\s+(Double|King|Queen|Twin|Single)\s+Bed\s*$/i', $hotelName, $m)) {
        $roomType = trim($m[0]);
        $hotelName = trim(substr($hotelName, 0, -strlen($m[0])));
    }
    // Pattern: "Deluxe Double 2", "Superior Twin 1", "Executive Double 1"
    elseif (preg_match('/\b(Deluxe|Superior|Standard|Executive|Privilege)\s+(Double|Twin|Single|King|Queen)\s+(\d+)\s*$/i', $hotelName, $m)) {
        $roomType = trim($m[0]);
        $hotelName = trim(substr($hotelName, 0, -strlen($m[0])));
    }
    // Pattern: "Deluxe Bed" (special case)
    elseif (preg_match('/\b(Deluxe|Superior)\s+Bed\s*$/i', $hotelName, $m)) {
        $roomType = trim($m[0]);
        $hotelName = trim(substr($hotelName, 0, -strlen($m[0])));
    }
    // Pattern: "Double - 2 People"
    elseif (preg_match('/\b(Double|Twin)\s*-\s*\d+\s*People\s*$/i', $hotelName, $m)) {
        $roomType = trim($m[0]);
        $hotelName = trim(substr($hotelName, 0, -strlen($m[0])));
    }
    // Pattern: "Deluxe Kingbed", "Deluxe Queen Bf"
    elseif (preg_match('/\b(Deluxe|Superior|Standard)\s+(Kingbed|Queen\s+Bf|Doublebed)\s*$/i', $hotelName, $m)) {
        $roomType = trim($m[0]);
        $hotelName = trim(substr($hotelName, 0, -strlen($m[0])));
    }
    // Pattern: "Kamar Sedang 1"
    elseif (preg_match('/\bKamar\s+Sedang\s*\d*\s*$/i', $hotelName, $m)) {
        $roomType = trim($m[0]);
        $hotelName = trim(substr($hotelName, 0, -strlen($m[0])));
    }
    // Pattern: "Family Ro" (truncated Family Room)
    elseif (preg_match('/\bFamily\s+Ro\s*$/i', $hotelName, $m)) {
        $roomType = 'Family Room';
        $hotelName = trim(substr($hotelName, 0, -strlen($m[0])));
    }
    // Pattern: "Villa 2", "Apartment"
    elseif (preg_match('/\b(Villa\s+\d+|Apartment)\s*$/i', $hotelName, $m)) {
        $roomType = trim($m[0]);
        $hotelName = trim(substr($hotelName, 0, -strlen($m[0])));
    }
    // Pattern: "2 Bed" at end
    elseif (preg_match('/\b(\d+\s+Bed)\s*$/i', $hotelName, $m)) {
        $roomType = trim($m[0]);
        $hotelName = trim(substr($hotelName, 0, -strlen($m[0])));
    }
    // Pattern: "Superior With 1 New" (incomplete)
    elseif (preg_match('/\b(Superior|Deluxe)\s+With\s+\d+\s+New\s*$/i', $hotelName, $m)) {
        $roomType = trim($m[0]);
        $hotelName = trim(substr($hotelName, 0, -strlen($m[0])));
    }
    // Pattern: "Executive 3" at end
    elseif (preg_match('/\b(Executive|Superior|Deluxe|Standard|Premium|Privilege|Club)\s+\d+\s*$/i', $hotelName, $m)) {
        $roomType = trim($m[0]);
        $hotelName = trim(substr($hotelName, 0, -strlen($m[0])));
    }
    // Pattern: "1 Bedroom Executive Twin 1"
    elseif (preg_match('/\b(\d+\s+Bedroom\s+Executive\s+Twin\s+\d+)\s*$/i', $hotelName, $m)) {
        $roomType = trim($m[0]);
        $hotelName = trim(substr($hotelName, 0, -strlen($m[0])));
    }
    // Pattern: Room number "205", "101"
    elseif (preg_match('/\s+(\d{3})\s*$/', $hotelName, $m)) {
        $roomType = 'Room ' . trim($m[1]);
        $hotelName = trim(substr($hotelName, 0, -strlen($m[0])));
    }
    
    // Clean up trailing chars
    $hotelName = rtrim($hotelName, ' -,');
    $hotelName = preg_replace('/\s+/', ' ', $hotelName);
    
    return [
        'hotel_name' => trim($hotelName) ?: $originalHotel,
        'room_type' => $roomType,
        'employee_name' => $employeeName
    ];
}

// Get all hotels with empty room_type
$hotels = DB::table('service_fees')
    ->where('service_type', 'hotel')
    ->where(function($q) {
        $q->whereNull('room_type')
          ->orWhere('room_type', '')
          ->orWhere('room_type', 'N/A');
    })
    ->get();

echo "Found " . count($hotels) . " hotels with empty room type\n\n";

$fixed = 0;
foreach ($hotels as $hotel) {
    $extracted = extractHotelRoomEmployee($hotel->hotel_name);
    
    $updates = [];
    if (!empty($extracted['room_type'])) {
        $updates['hotel_name'] = $extracted['hotel_name'];
        $updates['room_type'] = $extracted['room_type'];
    }
    if (!empty($extracted['employee_name']) && empty($hotel->employee_name)) {
        $updates['employee_name'] = $extracted['employee_name'];
    }
    
    if (!empty($updates)) {
        DB::table('service_fees')->where('id', $hotel->id)->update($updates);
        echo "Fixed ID {$hotel->id}:\n";
        echo "  Before: {$hotel->hotel_name}\n";
        echo "  Hotel:  " . ($updates['hotel_name'] ?? $hotel->hotel_name) . "\n";
        if (isset($updates['room_type'])) {
            echo "  Room:   {$updates['room_type']}\n";
        }
        if (isset($updates['employee_name'])) {
            echo "  Emp:    {$updates['employee_name']}\n";
        }
        echo "\n";
        $fixed++;
    }
}

echo "\n=== Summary ===\n";
echo "Fixed: {$fixed}\n";

// Check remaining
$remaining = DB::table('service_fees')
    ->where('service_type', 'hotel')
    ->where(function($q) {
        $q->whereNull('room_type')
          ->orWhere('room_type', '')
          ->orWhere('room_type', 'N/A');
    })
    ->get();

echo "Remaining: " . count($remaining) . "\n";

if (count($remaining) > 0) {
    echo "\nRemaining hotels:\n";
    foreach ($remaining as $h) {
        echo "  ID {$h->id}: {$h->hotel_name}\n";
    }
}
