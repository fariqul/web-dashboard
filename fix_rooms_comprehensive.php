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

echo "=== Fixing Hotels with Empty Room Type ===\n";
echo "Total to fix: " . $unfixed->count() . "\n\n";

$fixed = 0;

foreach ($unfixed as $hotel) {
    $result = extractRoomTypeFromHotelName($hotel->hotel_name);
    
    if ($result['room_type']) {
        $hotel->room_type = $result['room_type'];
        if ($result['hotel_name'] && $result['hotel_name'] !== $hotel->hotel_name) {
            $hotel->hotel_name = $result['hotel_name'];
        }
        if ($result['employee_name'] && empty($hotel->employee_name)) {
            $hotel->employee_name = $result['employee_name'];
        }
        $hotel->save();
        echo "Fixed ID {$hotel->id}: Room = '{$result['room_type']}'\n";
        $fixed++;
    } else {
        echo "UNFIXED ID {$hotel->id}: {$hotel->hotel_name}\n";
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

echo "\n=== Results ===\n";
echo "Fixed: $fixed records\n";
echo "Remaining empty: $remaining records\n";

/**
 * Extract room type and employee name from hotel name
 */
function extractRoomTypeFromHotelName($hotelName)
{
    $originalHotel = $hotelName;
    $extractedEmployee = null;
    $extractedRoom = null;

    // Step 1: Extract employee name
    
    // Pattern: lowercase name at end "mohamad sulthan", duplicated "Yusdi Yusdi"
    if (preg_match('/\s+([a-z]+\s+[a-z]+)$/i', $hotelName, $m)) {
        $name = trim($m[1]);
        if (preg_match('/^[a-z]/', $name) || preg_match('/^(\w+)\s+\1$/i', $name)) {
            $extractedEmployee = $name;
            $hotelName = trim(substr($hotelName, 0, -strlen($m[0])));
        }
    }
    
    // Pattern: Single letter at end like "2 A", "1 I" - remove it
    if (preg_match('/\s+(\d+)\s+([A-Z])$/u', $hotelName, $m)) {
        $hotelName = trim(substr($hotelName, 0, -strlen($m[0]))) . ' ' . $m[1];
    }
    
    // Pattern: number followed by single name "2 Ibrahim", "1 Fauzan"
    if (!$extractedEmployee && preg_match('/\s+(\d+)\s+([A-Z][a-z]{2,})$/u', $hotelName, $matches)) {
        $potentialName = trim($matches[2]);
        $keywords = ['Hotel', 'Resort', 'Inn', 'Bed', 'Room', 'Double', 'Twin', 'King', 'Queen', 'Size', 'Bunk', 'Single', 'Superior', 'Deluxe', 'Standard'];
        $isKeyword = false;
        foreach ($keywords as $kw) {
            if (strcasecmp($potentialName, $kw) === 0) {
                $isKeyword = true;
                break;
            }
        }
        if (!$isKeyword) {
            $extractedEmployee = $potentialName;
            $hotelName = trim(substr($hotelName, 0, -strlen($matches[0]))) . ' ' . $matches[1];
        }
    }
    
    // Pattern: number followed by mixed case name "4 Arie Pratama"
    if (!$extractedEmployee && preg_match('/\s+(\d+)\s+([A-Z][a-z]+(?:\s+[A-Za-z][a-z]*){1,3})$/u', $hotelName, $matches)) {
        $potentialName = trim($matches[2]);
        $keywords = ['Hotel', 'Resort', 'Inn', 'Bed', 'Room', 'Double', 'Twin', 'King', 'Queen', 'Size', 'Bunk'];
        $isKeyword = false;
        foreach ($keywords as $kw) {
            if (stripos($potentialName, $kw) !== false) {
                $isKeyword = true;
                break;
            }
        }
        if (!$isKeyword) {
            $extractedEmployee = $potentialName;
            $hotelName = trim(substr($hotelName, 0, -strlen($matches[0]))) . ' ' . $matches[1];
        }
    }
    
    // Pattern: ALL CAPS name at end
    if (!$extractedEmployee && preg_match('/\s+([A-Z]{2,}(?:\s+[A-Z]{1,}){1,4})$/u', $hotelName, $empMatches)) {
        $potentialName = trim($empMatches[1]);
        $roomKeywords = ['DELUXE', 'SUPERIOR', 'STANDARD', 'EXECUTIVE', 'SUITE', 'KING', 'QUEEN', 'TWIN', 'DOUBLE', 'SINGLE', 'BED', 'ROOM', 'HOTEL', 'PALACE', 'CONVENTION', 'SOPPENG', 'THAMRIN', 'JAKARTA', 'IHG', 'SIZE'];
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

    // Step 2: Extract room type using comprehensive patterns
    $roomPatterns = [
        // "Superior 1 Double Bed", "Deluxe 1 King Bed"
        '/\b(Superior|Deluxe|Standard|Executive|Privilege|Premium|Premier)\s+\d+\s+(?:Double|King|Queen|Twin|Single)\s+Beds?\s*$/i',
        // "Superior With 1 Double Bed", "Deluxe With 2 Single Beds", "Standard With 1 Double Bed"
        '/\b(Superior|Deluxe|Standard|Privilege)\s+With\s+\d+\s+(?:Double|King|Queen|Twin|Single)(?:\s*-?\s*Size)?\s+Beds?\s*$/i',
        // "Superior With One Double Bed"
        '/\b(Superior|Deluxe|Standard)\s+With\s+(?:One|Two)\s+(?:Double|King|Queen|Twin|Single)\s+Beds?\s*$/i',
        // "Standard 1 King - Size Bed" / "Superior With 1 King - Size Bed"
        '/\b(Standard|Superior|Deluxe|Privilege)\s+(?:With\s+)?\d+\s+\w+\s*-\s*Size\s+Bed\s*$/i',
        // "Standard 1 Double And 1 Bunk Bed"
        '/\b(Standard|Superior|Deluxe)\s+\d+\s+\w+\s+And\s+\d+\s+\w+\s+Bed\s*$/i',
        // "Deluxe 1 King Bed With Sofa Bed"
        '/\b(Deluxe|Superior|Standard)\s+\d+\s+\w+\s+Bed\s+With\s+Sofa\s+Bed\s*$/i',
        // "1 King Bed", "2 Twin Beds"
        '/\b\d+\s+(?:King|Queen|Twin|Double|Single)\s+Beds?\s*$/i',
        // "Standard 1 Queen Bed"
        '/\b(Standard)\s+\d+\s+Queen\s+Bed\s*$/i',
        // "Condotel 2 Bedroom"
        '/\b(Condotel)\s+\d+\s+Bedroom\s*$/i',
        // "Superior With Double Bed"
        '/\b(Superior|Deluxe|Standard)\s+With\s+(?:Double|King|Queen|Twin|Single)\s+Bed\s*$/i',
        // "Deluxe Double Or Twin", "Executive Royal Double Or Twin"
        '/\b(?:Deluxe|Executive\s+Royal|Superior)\s+(?:Double|Twin)\s+Or\s+(?:Twin|Double)\s*$/i',
        // "Superior Double Bed", "Deluxe Single Bed", "Standard King Bed"
        '/\b(?:Superior|Deluxe|Standard|Executive|Premium|Classic)\s+(?:Double|King|Queen|Twin|Single)\s+Bed\s*$/i',
        // "Deluxe Single 2", "Deluxe Single" (at end with optional number)
        '/\b(Deluxe|Superior|Standard)\s+(Single|Double|Twin)(?:\s+\d+)?\s*$/i',
        // "Deluxe Twin 2 Tn", "Superior Twin 1" 
        '/\b(Deluxe|Superior|Standard|Executive|Privilege)\s+(Double|Twin|Single|King|Queen)(?:\s+\d+)?(?:\s+Tn)?\s*$/i',
        // "Deluxe Bed", "Superior Bed"
        '/\b(Deluxe|Superior)\s+Bed\s*$/i',
        // "Double - 2 People", "Sorowako Double - 2 People"
        '/\b(?:Sorowako|Soroako\s+)?(?:Double|Twin)\s*-\s*\d+\s*People\s*$/i',
        // "Twin Superior Non-Smoking", "Sorowako Twin Superior Non-Smoking"
        '/\b(?:Sorowako|Soroako\s+)?(?:Twin|Double)\s+Superior\s+Non[- ]?Smoking\s*$/i',
        // "Deluxe Kingbed", "Deluxe Queen Bf", "Happiness Doublebed"
        '/\b(?:Deluxe|Superior|Standard|Happiness)\s+(?:Kingbed|Queen\s+Bf|Doublebed)\s*$/i',
        // "Double Superior Queen Bed Non Balcony"
        '/\bDouble\s+Superior\s+Queen\s+Bed(?:\s+Non\s+Balcony)?\s*$/i',
        // "Premier King", "Premier Hollywood"
        '/\b(?:Premier|Executive)\s+(?:King|Queen|Hollywood)\s*$/i',
        // "Deluxe King Bed", "Executive Queen Bed"
        '/\b(?:Deluxe|Executive|Premium|Classic)\s+(?:King|Queen|Twin|Double)\s+Bed\s*$/i',
        // "Deluxe Premier", "Deluxe Family", "Deluxe Business", "Deluxe Balcony"
        '/\b(?:Deluxe|Superior)\s+(?:Premier|Family|Business|Balcony)\s*$/i',
        // "Classic Braga View"
        '/\b(Classic)\s+\w+\s+View\s*$/i',
        // "Executive Cabin"
        '/\b(Executive)\s+(?:Cabin)\s*$/i',
        // "Smart Hollywood"
        '/\b(Smart)\s+(?:Hollywood)\s*$/i',
        // "Hollywood" alone at end
        '/\bHollywood\s*$/i',
        // "Harris Unique", "Harris" alone at end
        '/\bHarris(?:\s+Unique)?\s*$/i',
        // "Ra Twin Bed"
        '/\bRa\s+(?:Twin|Double|King)\s+Bed\s*$/i',
        // "Juno Skyline View"
        '/\bJuno\s+Skyline\s+View\s*$/i',
        // "Yello Monas"
        '/\bYello\s+Monas\s*$/i',
        // "Champs Hollywood"
        '/\bChamps\s+Hollywood\s*$/i',
        // "Comfy"
        '/\bComfy\s*$/i',
        // "Warmth"
        '/\bWarmth\s*$/i',
        // "Vip"
        '/\b(?:Vip|VIP)\s*$/i',
        // "Premiere"
        '/\b(?:Premiere|Premierre)\s*$/i',
        // "Villa 2"
        '/\bVilla\s+\d+\s*$/i',
        // "Apartment"
        '/\bApartment\s*$/i',
        // "2 Bed" at end
        '/\b\d+\s+Bed\s*$/i',
        // "Max Happiness Double Superior Grand"
        '/\bMax\s+Happiness\s+Double\s+Superior\s+Grand\s*$/i',
        // "Standard - 1 Double Bed"
        '/\b(?:Standard|Superior|Deluxe)\s*-\s*\d+\s+(?:Double|King|Queen|Twin|Single)\s+Bed\s*$/i',
        // "Superior With 1 New" (incomplete)
        '/\b(?:Superior|Deluxe)\s+With\s+\d+\s+New\s*$/i',
        // "Executive 3 A"
        '/\b(?:Executive|Superior|Deluxe|Standard|Premium|Privilege|Club)\s+\d+(?:\s+[A-Z])?\s*$/i',
        // "Family Ro" (truncated)
        '/\bFamily\s+Ro\s*$/i',
        // Room number "205", "101"
        '/\s+(\d{3})\s*$/',
        // "Kamar Sedang 1"
        '/\bKamar\s+Sedang\s*\d*\s*$/i',
        // "1 Bedroom Executive Twin 1"
        '/\b\d+\s+Bedroom\s+Executive\s+Twin\s+\d+\s*$/i',
        // "Deluxe Kingbed" - MaxOne format
        '/\b(?:Deluxe|Superior)\s+(?:Kingbed|Queenbed|Doublebed|Twinbed)\s*$/i',
        // "Privilege 1 King Bed"
        '/\bPrivilege\s+\d+\s+\w+\s+Bed\s*$/i',
    ];
    
    foreach ($roomPatterns as $pattern) {
        if (preg_match($pattern, $hotelName, $m)) {
            // Special case for Family Ro -> Family Room
            if (stripos($m[0], 'Family Ro') !== false) {
                $extractedRoom = 'Family Room';
            }
            // Special case for room number
            elseif (preg_match('/^\s*(\d{3})\s*$/', $m[0], $roomNum)) {
                $extractedRoom = 'Room ' . trim($roomNum[1]);
            } else {
                $extractedRoom = trim($m[0]);
            }
            $hotelName = trim(substr($hotelName, 0, -strlen($m[0])));
            break;
        }
    }

    // Clean up
    $hotelName = preg_replace('/\s+\d+\s*$/', '', $hotelName);
    $hotelName = rtrim($hotelName, ' -,');
    $hotelName = preg_replace('/\s+/', ' ', $hotelName);

    return [
        'hotel_name' => trim($hotelName) ?: $originalHotel, 
        'room_type' => $extractedRoom,
        'employee_name' => $extractedEmployee
    ];
}
