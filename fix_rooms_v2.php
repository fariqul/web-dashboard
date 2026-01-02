<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ServiceFee;

// Get all hotels with empty room_type
$hotels = ServiceFee::where('service_type', 'hotel')
    ->where(function($q) {
        $q->whereNull('room_type')
          ->orWhere('room_type', '')
          ->orWhere('room_type', 'N/A');
    })
    ->get();

echo "=== Fixing Hotels with Empty Room Type ===\n";
echo "Total to fix: " . $hotels->count() . "\n\n";

$fixed = 0;

foreach ($hotels as $hotel) {
    $hotelName = $hotel->hotel_name;
    $originalHotel = $hotelName;
    $extractedRoom = null;
    
    // Try all patterns
    $patterns = [
        // "Superior 1 Double Bed", "Deluxe 1 King Bed"
        '/\b(Superior|Deluxe|Standard|Executive|Privilege|Premium|Premier)\s+(\d+\s+(?:Double|King|Queen|Twin|Single)\s+Beds?)\s*$/i',
        
        // "Superior With 1 Double Bed", "Deluxe With 2 Single Beds"
        '/\b(Superior|Deluxe|Standard)\s+With\s+(\d+\s+(?:Double|King|Queen|Twin|Single)(?:\s*-?\s*Size)?\s+Beds?)\s*$/i',
        
        // "Superior With One Double Bed"
        '/\b(Superior|Deluxe|Standard)\s+With\s+(One|Two)\s+(?:Double|King|Queen|Twin|Single)\s+Beds?\s*$/i',
        
        // "Standard 1 King - Size Bed 2 A"
        '/\b(Standard|Superior|Deluxe)\s+\d+\s+\w+\s*-\s*Size\s+Bed.*$/i',
        
        // "Standard 1 Double And 1 Bunk Bed"
        '/\b(Standard|Superior|Deluxe)\s+\d+\s+\w+\s+And\s+\d+\s+\w+\s+Bed\s*$/i',
        
        // "Deluxe 1 King Bed With Sofa Bed"
        '/\b(Deluxe|Superior|Standard)\s+\d+\s+\w+\s+Bed\s+With\s+Sofa\s+Bed\s*$/i',
        
        // "1 King Bed", "2 Twin Beds"
        '/\b(\d+\s+(?:King|Queen|Twin|Double|Single)\s+Beds?)\s*$/i',
        
        // "Standard 1 Queen Bed"
        '/\b(Standard)\s+(\d+\s+Queen\s+Bed)\s*$/i',
        
        // "Condotel 2 Bedroom"
        '/\b(Condotel)\s+\d+\s+Bedroom\s*$/i',
        
        // "Superior With Double Bed"
        '/\b(Superior|Deluxe|Standard)\s+With\s+(Double|King|Queen|Twin|Single)\s+Bed\s*$/i',
        
        // "Deluxe Double Or Twin", "Executive Royal Double Or Twin"
        '/\b(Deluxe|Executive\s+Royal|Superior)\s+(Double|Twin)\s+Or\s+(Twin|Double)\s*$/i',
        
        // "Deluxe Twin 2 Tn", "Superior Twin 1"
        '/\b(Deluxe|Superior|Standard|Executive|Privilege)\s+(Double|Twin|Single|King|Queen)(?:\s+\d+)?(?:\s+Tn)?\s*$/i',
        
        // "Deluxe Bed", "Superior Bed"
        '/\b(Deluxe|Superior)\s+Bed\s*$/i',
        
        // "Double - 2 People"
        '/\b(Double|Twin)\s*-\s*\d+\s*People\s*$/i',
        
        // "Twin Superior Non-Smoking"
        '/\b(Twin|Double)\s+Superior\s+Non[- ]?Smoking\s*$/i',
        
        // "Deluxe Kingbed", "Deluxe Queen Bf", "Happiness Doublebed"
        '/\b(Deluxe|Superior|Standard|Happiness)\s+(Kingbed|Queen\s+Bf|Doublebed)\s*$/i',
        
        // "Double Superior Queen Bed Non Balcony"
        '/\b(Double\s+Superior\s+Queen\s+Bed(?:\s+Non\s+Balcony)?)\s*$/i',
        
        // "Premier King", "Premier Hollywood"
        '/\b(Premier|Executive)\s+(King|Queen|Hollywood)\s*$/i',
        
        // "Deluxe King Bed", "Executive Queen Bed", "Deluxe Twin Bed"
        '/\b(Deluxe|Executive|Premium|Classic)\s+(King|Queen|Twin|Double)\s+Bed\s*$/i',
        
        // "Deluxe Premier", "Deluxe Family", "Deluxe Business", "Deluxe Balcony"
        '/\b(Deluxe|Superior)\s+(Premier|Family|Business|Balcony)\s*$/i',
        
        // "Classic Braga View"
        '/\b(Classic)\s+\w+\s+View\s*$/i',
        
        // "Executive Cabin"
        '/\b(Executive)\s+(Cabin)\s*$/i',
        
        // "Smart Hollywood"
        '/\b(Smart)\s+(Hollywood)\s*$/i',
        
        // "Harris Unique", "Harris" alone at end
        '/\b(Harris)(?:\s+Unique)?\s*$/i',
        
        // "Ra Twin Bed"
        '/\b(Ra)\s+(Twin|Double|King)\s+Bed\s*$/i',
        
        // "Family Ro" (truncated)
        '/\bFamily\s+Ro\s*$/i',
        
        // "Juno Skyline View"
        '/\b(Juno)\s+Skyline\s+View\s*$/i',
        
        // "Yello Monas"
        '/\b(Yello)\s+Monas\s*$/i',
        
        // "Champs Hollywood"
        '/\b(Champs)\s+Hollywood\s*$/i',
        
        // "Comfy"
        '/\b(Comfy)\s*$/i',
        
        // "Warmth"
        '/\b(Warmth)\s*$/i',
        
        // "Vip"
        '/\b(Vip|VIP)\s*$/i',
        
        // "Premiere"
        '/\b(Premiere|Premierre)\s*$/i',
        
        // "Villa 2"
        '/\b(Villa)\s+\d+\s*$/i',
        
        // "Apartment"
        '/\b(Apartment)\s*$/i',
        
        // "2 Bed" at end
        '/\b(\d+\s+Bed)\s*$/i',
        
        // "Max Happiness Double Superior Grand"
        '/\b(Max\s+Happiness\s+Double\s+Superior\s+Grand)\s*$/i',
        
        // "Standard - 1 Double Bed"
        '/\b(Standard|Superior|Deluxe)\s*-\s*\d+\s+(Double|King|Queen|Twin|Single)\s+Bed\s*$/i',
        
        // "Superior With 1 New" (incomplete)
        '/\b(Superior|Deluxe)\s+With\s+\d+\s+New\s*$/i',
        
        // "Executive 3 A"
        '/\b(Executive|Superior|Deluxe|Standard|Premium|Privilege|Club)\s+\d+(?:\s+[A-Z])?\s*$/i',
        
        // Room number "205", "101"
        '/\s+(\d{3})\s*$/',
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $hotelName, $m)) {
            $extractedRoom = trim($m[0]);
            $hotelName = trim(substr($hotelName, 0, -strlen($m[0])));
            break;
        }
    }
    
    // Handle special cases
    if (!$extractedRoom) {
        // "Family Ro" -> Family Room
        if (preg_match('/Family\s+Ro\s*$/i', $originalHotel)) {
            $extractedRoom = 'Family Room';
            $hotelName = preg_replace('/Family\s+Ro\s*$/i', '', $originalHotel);
        }
    }
    
    // Room number pattern
    if (!$extractedRoom && preg_match('/\s+(\d{3})\s*$/', $originalHotel, $m)) {
        $extractedRoom = 'Room ' . trim($m[1]);
        $hotelName = trim(substr($originalHotel, 0, -strlen($m[0])));
    }
    
    // Clean up hotel name
    $hotelName = preg_replace('/\s+\d+\s*$/', '', $hotelName);
    $hotelName = rtrim($hotelName, ' -,');
    $hotelName = preg_replace('/\s+/', ' ', $hotelName);
    
    if ($extractedRoom) {
        $hotel->room_type = $extractedRoom;
        $hotel->hotel_name = trim($hotelName) ?: $originalHotel;
        $hotel->save();
        $fixed++;
        echo "Fixed ID {$hotel->id}: Room = '{$extractedRoom}'\n";
    } else {
        echo "COULD NOT FIX ID {$hotel->id}: {$originalHotel}\n";
    }
}

echo "\n=== Summary ===\n";
echo "Fixed: $fixed\n";
echo "Remaining: " . ($hotels->count() - $fixed) . "\n";
