<?php
/**
 * Fix all hotel data - comprehensive
 * Run: php fix_hotel_data_v3.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Fix Hotel Data v3 (Comprehensive) ===\n\n";

// Get ALL hotel records
$hotels = DB::table('service_fees')
    ->where('service_type', 'hotel')
    ->get();

echo "Processing " . count($hotels) . " hotel records\n\n";

$fixed = 0;

foreach ($hotels as $hotel) {
    $updates = [];
    $hotelName = $hotel->hotel_name;
    $roomType = $hotel->room_type;
    $employeeName = $hotel->employee_name;
    $changed = false;

    // 1. Extract employee name from hotel_name if it ends with ALL CAPS words
    if (preg_match('/\s+([A-Z]{2,}(?:\s+[A-Z]{1,}){1,4})$/u', $hotelName, $empMatches)) {
        $potentialName = trim($empMatches[1]);
        // Not a room keyword
        $roomKeywords = ['DELUXE', 'SUPERIOR', 'STANDARD', 'EXECUTIVE', 'SUITE', 'KING', 'QUEEN', 'TWIN', 'DOUBLE', 'SINGLE', 'BED', 'ROOM', 'HOTEL', 'SIZE', 'PRIVILEGE'];
        $isRoomKeyword = false;
        foreach ($roomKeywords as $keyword) {
            if ($potentialName === $keyword || preg_match('/^' . preg_quote($keyword) . '$/i', $potentialName)) {
                $isRoomKeyword = true;
                break;
            }
        }
        if (!$isRoomKeyword && strlen($potentialName) > 5 && !preg_match('/^\d+$/', $potentialName)) {
            // It's likely a name
            $hotelName = trim(str_replace($potentialName, '', $hotelName));
            if (empty($employeeName)) {
                $employeeName = $potentialName;
            }
            $changed = true;
        }
    }

    // 2. If room_type is empty or N/A, try to extract from hotel_name
    if (empty($roomType) || $roomType === 'N/A') {
        $roomTypeKeywords = [
            'Privilege', 'Deluxe', 'Superior', 'Standard', 'Executive', 'Premier', 'Suite', 
            'Junior Suite', 'Grand', 'Classic', 'Business', 'Premium', 'Comfort',
            'Family', 'Smart', 'Hollywood', 'Super', 'Warmth', 'Harris', 'Zest',
            'Max Happiness', 'New'
        ];

        $keywordPattern = implode('|', array_map('preg_quote', $roomTypeKeywords));
        
        // Pattern with dash: "Standard - 1 Double Bed 1"
        if (preg_match('/\b(' . $keywordPattern . ')\s*-?\s*(\d*\s*(?:Single|Double|Twin|King|Queen|Hollywood)?(?:\s*-?\s*Size)?\s*(?:Bed)?\s*\d*)\s*$/i', $hotelName, $matches)) {
            $roomType = trim($matches[0]);
            $hotelName = trim(substr($hotelName, 0, -strlen($matches[0])));
            $changed = true;
        }
        // Pattern: "Privilege 1 King Bed 1"
        elseif (preg_match('/\b(' . $keywordPattern . ')(\s+\d*\s*(?:King|Queen|Twin|Double|Single)?(?:\s*-?\s*Size)?\s*(?:Bed)?\s*\d*)\s*$/i', $hotelName, $matches)) {
            $roomType = trim($matches[0]);
            $hotelName = trim(substr($hotelName, 0, -strlen($matches[0])));
            $changed = true;
        }
        // Simple pattern at end
        elseif (preg_match('/\b(' . $keywordPattern . ')\s*\d*\s*$/i', $hotelName, $matches)) {
            $roomType = trim($matches[0]);
            $hotelName = trim(substr($hotelName, 0, -strlen($matches[0])));
            $changed = true;
        }
    }

    // Clean up trailing numbers or dashes
    $hotelName = preg_replace('/\s+\d+\s*$/', '', $hotelName);
    $hotelName = rtrim($hotelName, ' -');
    
    if ($hotelName !== $hotel->hotel_name) {
        $changed = true;
    }

    if ($changed) {
        $updates['hotel_name'] = $hotelName;
        $updates['room_type'] = $roomType;
        if (!empty($employeeName) && empty($hotel->employee_name)) {
            $updates['employee_name'] = $employeeName;
        }
        
        DB::table('service_fees')
            ->where('id', $hotel->id)
            ->update($updates);
        
        echo "Fixed ID {$hotel->id}:\n";
        echo "  Before: {$hotel->hotel_name}\n";
        echo "  Hotel:  {$hotelName}\n";
        if ($roomType !== $hotel->room_type) {
            echo "  Room:   {$roomType}\n";
        }
        if (!empty($employeeName) && empty($hotel->employee_name)) {
            echo "  Emp:    {$employeeName}\n";
        }
        echo "\n";
        $fixed++;
    }
}

echo "\n=== Summary ===\n";
echo "Fixed: {$fixed} records\n";
echo "\nDone!\n";
