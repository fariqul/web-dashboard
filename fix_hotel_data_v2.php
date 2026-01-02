<?php
/**
 * Fix hotel data: extract room type and employee name from hotel_name
 * Run: php fix_hotel_data_v2.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Fix Hotel Data v2 ===\n\n";

// Function to extract room type and employee name from hotel name
function extractRoomTypeFromHotelName($hotelName, $existingRoomType = null) {
    // If room type already exists and not empty, return as-is
    if (!empty($existingRoomType) && $existingRoomType !== 'N/A') {
        return ['hotel_name' => $hotelName, 'room_type' => $existingRoomType, 'employee_name' => null];
    }

    $originalHotel = $hotelName;
    $extractedEmployee = null;
    $extractedRoom = null;

    // First, try to extract employee name (usually ALL CAPS at the end, 2-4 words)
    if (preg_match('/\s+([A-Z]{2,}(?:\s+[A-Z]{1,}){1,4})$/u', $hotelName, $empMatches)) {
        $potentialName = trim($empMatches[1]);
        // Verify it's not a room type keyword
        $roomKeywords = ['DELUXE', 'SUPERIOR', 'STANDARD', 'EXECUTIVE', 'SUITE', 'KING', 'QUEEN', 'TWIN', 'DOUBLE', 'SINGLE', 'BED', 'ROOM', 'HOTEL'];
        $isRoomKeyword = false;
        foreach ($roomKeywords as $keyword) {
            if (stripos($potentialName, $keyword) !== false) {
                $isRoomKeyword = true;
                break;
            }
        }
        if (!$isRoomKeyword && strlen($potentialName) > 5) {
            $extractedEmployee = $potentialName;
            $hotelName = trim(str_replace($potentialName, '', $hotelName));
        }
    }

    // Room type keywords to look for
    $roomTypeKeywords = [
        'Deluxe', 'Superior', 'Standard', 'Executive', 'Premier', 'Suite', 
        'Junior Suite', 'Grand', 'Classic', 'Business', 'Premium', 'Comfort',
        'Family', 'Smart', 'Hollywood', 'Super', 'Warmth', 'Harris', 'Zest',
        'Max Happiness', 'New'
    ];

    $keywordPattern = implode('|', array_map('preg_quote', $roomTypeKeywords));
    
    // Pattern 1: Room type with dash and bed info
    $patternDash = '/\b(' . $keywordPattern . ')\s*-\s*(\d*\s*(?:Single|Double|Twin|King|Queen|Hollywood)?(?:\s*-?\s*Size)?\s*(?:Bed)?\s*\d*)\s*$/i';
    
    if (preg_match($patternDash, $hotelName, $matches)) {
        $extractedRoom = trim($matches[0]);
        $hotelName = trim(substr($hotelName, 0, -strlen($matches[0])));
    }
    
    // Pattern 2: Room type at end without dash
    if (empty($extractedRoom)) {
        $pattern = '/\b(' . $keywordPattern . ')(\s+(?:With\s+)?\d*\s*(?:Single|Double|Twin|King|Queen|Hollywood)?(?:\s*-?\s*Size)?\s*(?:Bed)?\s*\d*)\s*$/i';
        
        if (preg_match($pattern, $hotelName, $matches)) {
            $extractedRoom = trim($matches[0]);
            $hotelName = trim(substr($hotelName, 0, -strlen($matches[0])));
        }
    }

    // Pattern 3: Just room keyword at end
    if (empty($extractedRoom)) {
        $patternSimple = '/\b(' . $keywordPattern . ')\s*$/i';
        if (preg_match($patternSimple, $hotelName, $matches)) {
            $extractedRoom = trim($matches[0]);
            $hotelName = trim(substr($hotelName, 0, -strlen($matches[0])));
        }
    }
    
    // Pattern 4: Room type in middle with numbers after hotel
    if (empty($extractedRoom)) {
        $pattern2 = '/\b(' . $keywordPattern . ')(\s+(?:With\s+)?\d+\s*(?:Single|Double|Twin|King|Queen|Hollywood)?(?:\s*-?\s*Size)?\s*(?:Bed)?\s*\d*)/i';
        
        if (preg_match($pattern2, $hotelName, $matches)) {
            $extractedRoom = trim($matches[0]);
            $hotelName = trim(str_replace($extractedRoom, '', $hotelName));
            $hotelName = preg_replace('/\s+/', ' ', $hotelName);
        }
    }

    // Clean up trailing numbers from hotel name
    $hotelName = preg_replace('/\s+\d+\s*$/', '', $hotelName);
    $hotelName = trim($hotelName);
    
    // Remove trailing dash or special chars
    $hotelName = rtrim($hotelName, ' -');

    return [
        'hotel_name' => $hotelName ?: $originalHotel, 
        'room_type' => $extractedRoom,
        'employee_name' => $extractedEmployee
    ];
}

// Get all hotel records with empty room_type or N/A room_type
$hotels = DB::table('service_fees')
    ->where('service_type', 'hotel')
    ->where(function($q) {
        $q->whereNull('room_type')
          ->orWhere('room_type', '')
          ->orWhere('room_type', 'N/A');
    })
    ->get();

echo "Found " . count($hotels) . " hotels with empty/N/A room type\n\n";

$fixed = 0;
$noChange = 0;

foreach ($hotels as $hotel) {
    $extracted = extractRoomTypeFromHotelName($hotel->hotel_name, null);
    
    $hasChange = false;
    $updates = [];
    
    // Check if room type was extracted
    if (!empty($extracted['room_type'])) {
        $updates['room_type'] = $extracted['room_type'];
        $updates['hotel_name'] = $extracted['hotel_name'];
        $hasChange = true;
    }
    
    // Check if employee name was extracted and current is empty
    if (!empty($extracted['employee_name']) && empty($hotel->employee_name)) {
        $updates['employee_name'] = $extracted['employee_name'];
        $hasChange = true;
    }
    
    if ($hasChange) {
        DB::table('service_fees')
            ->where('id', $hotel->id)
            ->update($updates);
        
        echo "Fixed ID {$hotel->id}:\n";
        echo "  Before: {$hotel->hotel_name}\n";
        echo "  Hotel:  {$extracted['hotel_name']}\n";
        if (!empty($extracted['room_type'])) {
            echo "  Room:   {$extracted['room_type']}\n";
        }
        if (!empty($extracted['employee_name'])) {
            echo "  Emp:    {$extracted['employee_name']}\n";
        }
        echo "\n";
        $fixed++;
    } else {
        $noChange++;
    }
}

echo "\n=== Summary ===\n";
echo "Fixed: {$fixed}\n";
echo "No change: {$noChange}\n";

// Also check for records with employee names in hotel_name but room_type already set
echo "\n=== Checking for embedded employee names ===\n";

$hotelsWithEmployee = DB::table('service_fees')
    ->where('service_type', 'hotel')
    ->whereNotNull('room_type')
    ->where('room_type', '!=', '')
    ->where('room_type', '!=', 'N/A')
    ->whereNull('employee_name')
    ->get();

$empFixed = 0;
foreach ($hotelsWithEmployee as $hotel) {
    // Check if hotel name ends with ALL CAPS name
    if (preg_match('/\s+([A-Z]{2,}(?:\s+[A-Z]{1,}){1,4})$/u', $hotel->hotel_name, $empMatches)) {
        $potentialName = trim($empMatches[1]);
        $roomKeywords = ['DELUXE', 'SUPERIOR', 'STANDARD', 'EXECUTIVE', 'SUITE', 'KING', 'QUEEN', 'TWIN', 'DOUBLE', 'SINGLE', 'BED', 'ROOM', 'HOTEL'];
        $isRoomKeyword = false;
        foreach ($roomKeywords as $keyword) {
            if (stripos($potentialName, $keyword) !== false) {
                $isRoomKeyword = true;
                break;
            }
        }
        if (!$isRoomKeyword && strlen($potentialName) > 5) {
            $cleanHotel = trim(str_replace($potentialName, '', $hotel->hotel_name));
            DB::table('service_fees')
                ->where('id', $hotel->id)
                ->update([
                    'hotel_name' => $cleanHotel,
                    'employee_name' => $potentialName
                ]);
            echo "Fixed employee ID {$hotel->id}: {$potentialName}\n";
            $empFixed++;
        }
    }
}

echo "\nEmployee names extracted: {$empFixed}\n";
echo "\nDone!\n";
